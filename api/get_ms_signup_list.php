<?php
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/ms_signup_list.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $msSignupList = new MsSignupList();

    $arOptions = [
        'limit' => $_GET['limit'],
        'offset' => $_GET['offset']
    ];

    $result = $msSignupList->GetList(array(), $arFilter, $arOptions);
    $list = $result['items'];

    foreach ($list as $key => $value) {
        $teamMsId = $value['team_ms_id'];
        $res = CIBlockSection::GetList(array(), array("ID" => $teamMsId));
        while ($ar = $res->Fetch()) {
            $list[$key]['team_ms'] = $ar["NAME"];
        }

        $rsDepartments = CIBlockSection::GetList(array(), array("ID" => json_decode($value['department_id'], true)));
        $departmentLabels = [];
        while ($arDepartment = $rsDepartments->Fetch()) {
            $departmentLabels[] = $arDepartment["NAME"];
        }
        $list[$key]['department'] = implode(', ', $departmentLabels);

        $enumList = CUserFieldEnum::GetList(array(), array('USER_FIELD_ID' => 966));
        $typeMS = array();
        while ($enum = $enumList->Fetch()) {
            $typeMS[$enum['ID']] = $enum['VALUE'];
        }
        $list[$key]['type_ms'] = $typeMS[$value['type_ms_id']];
    }

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $list,
        'total' => $result['total'],
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
