<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

use Bitrix\Main\Loader;

header("Content-Type: application/json");

function get_head($department_id)
{
    $rsStructure = CIntranetUtils::GetStructure();
    if (is_null($rsStructure["DATA"][$department_id]["UF_HEAD"])) {
        $depth = $rsStructure["DATA"][$department_id]["DEPTH_LEVEL"];
        if ($depth > 1) {
            return get_head($rsStructure["DATA"][$department_id]["IBLOCK_SECTION_ID"]);
        } else {
            return NULL;
        }
    } else {
        return $rsStructure["DATA"][$department_id]["UF_HEAD"];
    }
}

function get_direct_manager($userID)
{
    $dpm = CUser::getById($userID)->fetch()["UF_DEPARTMENT"];
    $rsStructure = CIntranetUtils::GetStructure();

    $lowest_depth = PHP_INT_MAX;
    $direct_department = NULL;

    foreach ($dpm as $department_id) {
        $depth = $rsStructure["DATA"][$department_id]["DEPTH_LEVEL"];
        if ($depth < $lowest_depth) {
            $lowest_depth = $depth;
            $direct_department = $department_id;
        }
    }

    if ($direct_department !== NULL) {
        $head = get_head($direct_department);
        if ($head !== NULL) {
            $managerInfo = CUser::GetByID($head)->Fetch();
            return [
                'id' => $head,
                'managerId' => "user_" . $head,
                'fullName' => $managerInfo['LAST_NAME'] . ' ' . $managerInfo['NAME'],
                'departmentId' => $direct_department // Thêm departmentId
            ];
        }
    }
    return NULL;
}

function get_department_head($departmentId)
{
    $rsStructure = CIntranetUtils::GetStructure();

    if (!isset($rsStructure["DATA"][$departmentId])) {
        return NULL;
    }

    $head = get_head($departmentId);

    if ($head !== NULL) {
        $managerInfo = CUser::GetByID($head)->Fetch();
        return [
            'id' => $head,
            'managerId' => "user_" . $head,
            'fullName' => $managerInfo['LAST_NAME'] . ' ' . $managerInfo['NAME'],
            'departmentId' => $departmentId
        ];
    } else {
        $depth = $rsStructure["DATA"][$departmentId]["DEPTH_LEVEL"];
        if ($depth > 1) {
            $parentDepartmentId = $rsStructure["DATA"][$departmentId]["IBLOCK_SECTION_ID"];
            $result = get_department_head($parentDepartmentId);
            if ($result !== NULL) {
                $result['originalDepartmentId'] = $departmentId;
            }
            return $result;
        }
    }

    return NULL;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    header('Content-Type: application/json');
    try {
        if (!isset($_GET['type']) || !isset($_GET['id'])) {
            throw new Exception("Missing required parameters");
        }

        $type = $_GET['type'];
        $id = intval($_GET['id']);

        if ($id <= 0) {
            throw new Exception("Invalid ID");
        }

        $result = null;
        switch ($type) {
            case 'userId':
                $result = get_direct_manager($id);
                break;
            case 'departmentId':
                $result = get_department_head($id);
                break;
            default:
                throw new Exception("Invalid type parameter");
        }

        if ($result === NULL) {
            throw new Exception("No manager found");
        }

        echo json_encode([
            "success" => true,
            "data" => $result
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
    exit();
}
