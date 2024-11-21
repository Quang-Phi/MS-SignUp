<?php
require_once $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/kpi.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $kpi = new Kpi();

    $arFilter = array(
        'ms_list_id' => $_GET['ms_list_id'] ?? null,
        'user_id' => $_GET['user_id'] ?? null,
        'stage_id' => $_GET['stage_id'] ?? null,
        'year' => $_GET['year'] ?? null
    );
    $data = $kpi->GetList(array(), $arFilter);

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
