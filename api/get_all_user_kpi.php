<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
require_once $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/env.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/kpi_history.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $kpi = new Kpi();
    $msSignupList = new MsSignupList($config);
    $kpiHistory = new kpiHistory();

    $arFilter = array(
        'completed' => 1,
    );
    $data = $msSignupList->GetList(array(), $arFilter);

    if (count($data['items']) > 0) {
        $ids = array_column($data['items'], 'id');

        $arFilter = array(
            'stage_id' => 3,
            'ms_list_id' => $ids
        );

        $res = $kpi->GetList(array(), $arFilter);

        $result = array();
        $data = array_merge([], $res);
        foreach ($res as $key => $item) {
            if ($item['year'] != $_GET['year']) {
                $arr = [
                    'kpi_id' => $item['id'],
                    'stage_id' => $item['stage_id'],
                    'year' => $_GET['year']
                ];
                $arOrder = ['created_at' => 'DESC'];
                $listHistory = $kpiHistory->GetList($arOrder, $arr);
                if (count($listHistory) > 0) {
                    $data[$key]['kpi'] = $listHistory[0]['old_kpi'];
                    $data[$key]['year'] = $listHistory[0]['year'];
                } else {
                    unset($data[$key]);
                }
                $user_id = $item['user_id'];
                $id = $item['id'];
            }
        }

        foreach ($data as $key => $value) {
            $user_id = $value['user_id'];
            $id = $value['id'];
            if (!isset($result[$user_id]) || $result[$user_id]['id'] < $id) {
                $result[$user_id] = $value;
            }
        }
                
        foreach ($result as $key => $value) {
            $user = CUser::GetByID($value['user_id'])->Fetch();
            $userFullName = htmlspecialchars($user["LAST_NAME"]) . " " . htmlspecialchars($user["NAME"]);
            $result[$key]['user_name'] = $userFullName;
        }
    }
    echo json_encode([
        'success' => true,
        'data' => $result,
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
