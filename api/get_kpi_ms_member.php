<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/env.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/kpi_history.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $kpi = new Kpi();
    $msSignupList = new MsSignupList(env: $config);
    $kpiHistory = new kpiHistory();

    $arFilter = array(
        'user_id' => $_GET['user_id'],
        'stage_id' => $_GET['stage_id'],
    );

    $arr = array(
        'user_id' => $_GET['user_id'],
        'completed' => 1
    );

    $order = array(
        'id' => 'desc'
    );

    $list = $msSignupList->GetList($order, $arr);
    if (count($list) > 0) {
        $msListId = $list['items'][0]['id'];
    }

    $arFilter['ms_list_id'] = $msListId;

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
        }
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $data,
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
