<?php
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/kpi_history.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

try {
    $kpi = new Kpi();
    $kpiHistory = new kpiHistory();

    $formData = json_decode(file_get_contents('php://input'), true);
    $arFilter = array(
        'ms_list_id' => $formData['ms_list_id'],
        'user_id' => $formData['user_id'],
        'stage_id' => $formData['stage_id']
    );
    $data = [
        'user_id' => $formData['user_id'],
        'stage_id' => $formData['stage_id'],
        'ms_list_id' => $formData['ms_list_id'],
        'year' => $formData['year'],
        'kpi' => json_encode($formData['kpi'], JSON_UNESCAPED_UNICODE)
    ];

    $result = $kpi->GetList(array(), $arFilter);
    $res = null;
    if (count($result) > 0) {
        $res = $kpi->Update($result[0]['id'], $data);
    } else {
        $res = $kpi->Add($data);
    }

    if ($res && is_array($formData['old_kpi'])) {
        $arr = [
            'kpi_id' => $res,
            'stage_id' => $formData['stage_id'],
            'old_kpi' => json_encode($formData['old_kpi'], JSON_UNESCAPED_UNICODE),
            'is_temporary' => true,
        ];
        $kpiHistory->Add($arr);
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
