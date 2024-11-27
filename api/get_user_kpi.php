<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/env.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $kpi = new Kpi();
    $msSignupList = new MsSignupList(env: $config);
    $arFilter = array(
        'user_id' => $_GET['user_id'] ?? null,
        'stage_id' => $_GET['stage_id'] ?? null
    );

    if (!$_GET['ms_list_id']) {
        $arr = array(
            'user_id' => $_GET['user_id'],
        );
        $order = array(
            'created_at' => 'asc'
        );

        $list = $msSignupList->GetList($order, $arr);
        if (count($list) > 0) {
            $msListId = $list['items'][0]['id'];
        }

        $arFilter['ms_list_id'] = $msListId;
    } else {
        $arFilter['ms_list_id'] = $_GET['ms_list_id'];
    }
    if ($_GET['year']) {
        $arFilter['year'] = $_GET['year'];
    }
    $data = $kpi->GetList(array(), $arFilter);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'arr' => $arFilter,
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
