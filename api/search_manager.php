<?php
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/services/api_services.php';
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/env.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Validate input
    if (!isset($_GET['name']) && empty($_GET['user_id'])) {
        throw new InvalidArgumentException('Name parameter is required');
    }

    $user_id = null;
    $name = null;

    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    }
    if (isset($_GET['name'])) {
        $name = trim($_GET['name']);
    }

    $apiService = new ApiService($config);

    $results = $apiService->searchManager($name, $user_id);

    echo json_encode([
        'success' => true,
        'data' => $results,
        'total' => count($results),
        'message' => 'Search completed successfully'
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Validation Error',
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    // Log detailed error
    error_log("Error in search_manager.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server Error',
        'message' => 'An internal server error occurred'
    ]);
}
