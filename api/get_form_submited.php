<?php
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/ms_signup_list.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $msSignupList = new MsSignupList();

    $arOrder = [
        'created_at' => 'desc'
    ];
    $arFilter = [
        'user_id' => intval($_GET['user_id'])
    ];

    $departments = $msSignupList->GetList($arOrder, $arFilter, array());
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $departments,
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
