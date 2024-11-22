<?php
require $_SERVER["DOCUMENT_ROOT"] .
"/bitrix/modules/main/include/prolog_before.php";
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-signup/model/kpi_history.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $kpiHistory = new kpiHistory();
    $kpi = new kpi();

    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    $stageId = isset($_GET['stage_id']) ? (int)$_GET['stage_id'] : null;

    $arOptions = [
        'limit' => $limit,
        'offset' => $offset
    ];

    $arOrder = ['created_at' => 'DESC'];
    $arFilter = array();

    $arFilterKpi = array(
        'ms_list_id' => $_GET['ms_list_id'],
        'user_id' => $_GET['user_id'],
        'stage_id' => $stageId
    );

    $result = $kpi->GetList(array(), $arFilterKpi);
    if (count($result) > 0) {
        $kpiId = $result[0]['id'];
        $arFilter = array(
            'kpi_id' => $kpiId,
            'stage_id' => $stageId,
            'is_temporary' => true
        );
        $list = $kpiHistory->GetList($arOrder, $arFilter, $arOptions);
        $user = CUser::GetByID($_GET['user_id'])->Fetch();
        foreach ($list as $key => $value) {
            $list[$key]['created_at'] = date('d/m/Y H:i', strtotime($value['created_at']));
            $list[$key]['user_id'] =  $_GET['user_id'];
            $list[$key]['user_name'] = htmlspecialchars($user["LAST_NAME"]) . " " . htmlspecialchars($user["NAME"]);
        }
        $total = $kpiHistory->GetCount($arFilter);
    }else{
        $list = [];
        $total = 0;
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $list,
        'total' => $total,
        'hasMore' => ($offset + $limit) < $total
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
