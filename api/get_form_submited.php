<?php
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . "/page-custom/ms-signup/env.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $msSignupList = new MsSignupList($config);

    $arOrder = [
        'created_at' => 'DESC'
    ];
    $arFilter = [
        'user_id' => intval($_GET['user_id'])
    ];

    $list = $msSignupList->GetList($arOrder, $arFilter, array(),false);
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $list,
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
