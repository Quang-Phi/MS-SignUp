<?php
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi_history.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

try {
    $kpi = new Kpi();
    $kpiHistory = new kpiHistory();

    $formData = json_decode(file_get_contents('php://input'), true);
    $stageDeal = json_decode($formData["stage_deal"], true);

    $ex = [
        '3' => 'kpi_msa',
        '4' => 'kpi_hr'
    ];

    $old = [
        '3' => 'old_kpi_msa',
        '4' => 'old_kpi_hr'
    ];

    function getCompareYear($formData)
    {
        return ($formData['curr_month'] == 12 ? $formData['next_year'] : $formData['year']);
    }


    $isAddHistory = false;
    if (is_array($stageDeal) && count($stageDeal) > 0) {
        // echo '(luong 1->)';
        $res = null;
        foreach ($ex as $key => $value) {
            $arFilter = array(
                'ms_list_id' => $formData['ms_list_id'],
                'user_id' => $formData['user_id'],
                'stage_id' => $key
            );
            $dataKpi = is_string($formData[$value]) ? json_decode($formData[$value], true) : $formData[$value];
            if (is_array($dataKpi) && count($dataKpi) > 0) {
                // echo '(luong 1.1->)';
                $data = [
                    'user_id' => $formData['user_id'],
                    'stage_id' => $key,
                    'ms_list_id' => $formData['ms_list_id'],
                    'year' => getCompareYear($formData),
                    'kpi' => json_encode($dataKpi, JSON_UNESCAPED_UNICODE)
                ];


                $result = $kpi->GetList(array(), $arFilter);
                if (count($result) > 0) {
                    $res = $kpi->Update($result[0]['id'], $data);
                } else {
                    $res = $kpi->Add($data);
                }
            }
            if ($res || intval($formData['stage_id']) === intval($formData['max_stage'])) {
                // echo '(luong 1.2 ->)';
                foreach ($old as $x => $value) {
                    $userId = $USER->GetID();
                    if ($key == $x) {
                        if ($formData['create_history']) {
                            // echo '(luong 1.2.1 ->)';
                            $data = [
                                'user_id' => $formData['user_id'],
                                'stage_id' => $key,
                                'ms_list_id' => $formData['ms_list_id'],
                                'year' => $formData['year'],
                                'kpi' => json_encode($dataKpi, JSON_UNESCAPED_UNICODE)
                            ];
                            $result = $kpi->GetList(array(), $arFilter);
                            $res = $result[0];
                            $oldKpi = json_decode($res['kpi'], true);
                        } else {
                            // echo '(luong 1.2.2 ->)';
                            if ($res['year'] < getCompareYear($formData)) {
                                $oldKpi = json_decode($res['kpi'], true);
                            } else {
                                $oldKpi = json_decode($formData[$value], true);
                            }
                        }
                        if (is_array($oldKpi) && count($oldKpi) > 0) {
                            $arr = [
                                'modified_by' => $userId,
                                'kpi_id' => $res['id'],
                                'stage_id' => $key,
                                'old_kpi' => json_encode($oldKpi, JSON_UNESCAPED_UNICODE),
                                'is_temporary' => true,
                            ];
                            $kpiHistory->Add($arr);
                            $isAddHistory = true;
                        }
                    }
                }
            }
        }
    }

    if (intval($formData['stage_id']) !== intval($formData['max_stage'])) {
        // echo '(luong 2->)';
        $arFilter = array(
            'ms_list_id' => $formData['ms_list_id'],
            'user_id' => $formData['user_id'],
            'stage_id' => $formData['stage_id']
        );
        $data = [
            'user_id' => $formData['user_id'],
            'stage_id' => $formData['stage_id'],
            'ms_list_id' => $formData['ms_list_id'],
            'year' => getCompareYear($formData),
            'kpi' => json_encode($formData['kpi'], JSON_UNESCAPED_UNICODE)
        ];
        $result = $kpi->GetList(array(), $arFilter);
        $res = null;
        if (count($result) > 0) {
            // echo '(luong 2.1->)';
            $oldData = [
                'kpi_id' => $result[0]['id'],
                'stage_id' => $result[0]['stage_id'],
                'old_kpi' => $result[0]['kpi'],
                'is_temporary' => true
            ];
            $res = $kpi->Update($result[0]['id'], $data);
            if($result[0]['year'] == getCompareYear($formData)){
                $kpiHistory->Add($oldData);
            }
        } else {
            // echo '(luong 2.2->)';
            $res = $kpi->Add($data);
            if ($res && is_array($formData['old_kpi'])) {
                $arr = [
                    'kpi_id' => $res,
                    'stage_id' => $formData['stage_id'],
                    'old_kpi' => json_encode($formData['old_kpi'], JSON_UNESCAPED_UNICODE),
                    'is_temporary' => true,
                ];
                $kpiHistory->Add($arr);
            }
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $res,
        'timestamp' => time()
    ]);
} catch (ApiException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getErrorCode()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal Server Error',
        'message' => $e->getMessage()
    ]);
}
