<?php
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/services/api_services.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/env.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $apiService = new ApiService($config);

    $userId = $_GET['user_id'] ?? null;
    $departments = $apiService->getListTeamMS($userId);
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
