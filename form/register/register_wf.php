<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

header("Content-Type: application/json");

// Include required modules
if (!CModule::IncludeModule("lists") || !CModule::IncludeModule("bizproc")) {
    echo json_encode([
        "success" => false,
        "message" => "Required modules are not loaded",
    ]);
    exit();
}

function create_wf($PROP, $userID, $IBLOCK_ID, $workflowTemplateId, $post)
{
    try {
        $el = new CIBlockElement();

        $arLoadProductArray = [
            "MODIFIED_BY" => $userID,
            "IBLOCK_ID" => $IBLOCK_ID,
            "PROPERTY_VALUES" => $PROP,
            "NAME" => "Đăng ký làm MS " . $post["name"],
            "ACTIVE" => "Y",
            "team_ms" => $post["teamMSName"],
            "head_dpm" => "user_" . $post["headDepartmentId"],
            "ms_lead" => "user_" . $post["headMSId"],
        ];

        $PRODUCT_ID = $el->Add($arLoadProductArray, true, true);

        if (!$PRODUCT_ID) {
            throw new Exception("Không thể tạo element: " . $el->LAST_ERROR);
        }

        $arParams["IBLOCK_TYPE_ID"] = "bitrix_processes";
        $arSecFilter = [
            "PRODUCT_ID" => $PRODUCT_ID,
        ];

        $arErrorsTmp = [];
        $wfId = CBPDocument::StartWorkflow(
            $workflowTemplateId,
            \BizProcDocument::getDocumentComplexId(
                $arParams["IBLOCK_TYPE_ID"],
                $arSecFilter["PRODUCT_ID"]
            ),
            array_merge($arLoadProductArray, [
                "TargetUser" => "user_" . intval($GLOBALS["USER"]->GetID()),
            ]),
            $arErrorsTmp
        );

        if (count($arErrorsTmp) > 0) {
            $errorMessage = "";
            foreach ($arErrorsTmp as $e) {
                $errorMessage .= "[" . $e["code"] . "] " . $e["message"] . " ";
            }
            throw new Exception($errorMessage);
        }

        return [
            "success" => true,
            "message" => "Đăng ký làm MS thành công",
            "data" => [
                "productId" => $PRODUCT_ID,
                "workflowId" => $wfId
            ]
        ];
    } catch (Exception $e) {
        return [
            "success" => false,
            "message" => $e->getMessage()
        ];
    }
}

function init_prop($post)
{
    $PROP = [];
    $PROP["HO_VA_TEN_C_MS"] = $post["name"];
    $PROP["USER_ID_C_MS"] = $post["userId"];
    $PROP["TEAM_MS_C_MS"] = $post["teamMSName"];
    $PROP["PHONG_BAN_C_MS"] = $post["department"];
    $PROP["XAC_NHAN_C_MS"] = $post["agreement"];
    $PROP["TRANG_THAI_C_MS"] = "Chờ xét duyệt";
    return $PROP;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $userID = $USER->GetID();
        if (!$userID) {
            throw new Exception("User not authenticated");
        }

        $PROP = init_prop($_POST);
        $result = create_wf($PROP, $userID, 112, 470, $_POST);
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}
?>