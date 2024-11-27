<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $teamMsId = intval($_GET['team_ms_id']);

    $arFilter = array('UF_DEPARTMENT' => $teamMsId);
    $rsUsers = CUser::GetList(($by = "NAME"), ($order = "ASC"), $arFilter);

    $arUsersResult = [];
    while ($arUser = $rsUsers->Fetch()) {
        $arUsersResult[] = $arUser;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $arUsersResult,
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