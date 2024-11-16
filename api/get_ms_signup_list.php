<?php
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/model/ms_signup_list.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $msSignupList = new MsSignupList();

    $arFilter = $_GET['filter'] ?? null;
    $list = $msSignupList->GetList(array(), $arFilter);
    foreach ($list as $key => $value) {
        $teamMsId = $value['team_ms_id'];

        $res = CIBlockSection::GetList(array(), array("ID" => $teamMsId));
        $typeMSLabel = '';

        while ($ar = $res->Fetch()) {
            $list[$key]['team_ms'] = $ar["NAME"];
        }

        // $msId = $value['department_id'];
        // $enumList = CUserFieldEnum::GetList(array(), array('ID' => $msId));
        // $typeMSLabel = '';

        // while ($enum = $enumList->Fetch()) {
        //     if ($enum['ID'] == $msId) {
        //         $list[$key]['department'] = $enum['VALUE'];
        //         break;
        //     }
        // }

        $rsDepartments = CIBlockSection::GetList(array(), array("ID" => json_decode($value['department_id'], true)));
        $departmentLabels = [];

        while ($arDepartment = $rsDepartments->Fetch()) {
            $departmentLabels[] = $arDepartment["NAME"];
        }
        $list[$key]['department'] = implode(', ', $departmentLabels);
    }

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
`