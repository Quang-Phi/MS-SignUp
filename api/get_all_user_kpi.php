<?php
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
require_once $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/env.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi_history.php';

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $connection = Application::getConnection();
    $sqlHelper = $connection->getSqlHelper();

    $kpi = new Kpi();
    $msSignupList = new MsSignupList($config);

    $arFilter = array(
        'completed' => 1,
    );
    $data = $msSignupList->GetList(array(), $arFilter);

    $year = date('Y');
    $latestData = array_reduce($data['items'], function ($carry, $item) use ($year) {
        $userId = $item['user_id'];
        if (substr($item['join_date'], 0, 4) == $year) {
            if (!isset($carry[$userId])) {
                $carry[$userId] = $item;
            } else {
                $carry[$userId] = ($carry[$userId]['join_date'] > $item['join_date']) ? $carry[$userId] : $item;
            }
        }
        return $carry;
    }, []);
    $latestIds = array_column($latestData, 'id');

    $arFilter = array(
        'stage_id' => 3,
        'ms_list_id' => $latestIds
    );
    if (isset($_GET['year'])) {
        $arFilter['year'] = $_GET['year'];
    }
    
    $data = $kpi->GetList(array(), $arFilter);
    foreach ($data as $key => $value) {
        $user = CUser::GetByID($value['user_id'])->Fetch();
        $userFullName = htmlspecialchars($user["LAST_NAME"]) . " " . htmlspecialchars($user["NAME"]);
        $data[$key]['user_name'] = $userFullName;
    }

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
