<?php
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/services/mail_service.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/model/ms_signup_list.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/model/reviewer_stage.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

try {
    $msSignupList = new MsSignupList();
    $reviewerStage = new ReviewerStage();

    $jsonData = file_get_contents("php://input");
    $post = json_decode($jsonData, true);
    echo var_export($post, true);
    
    if (isset($post["id"])) {
        $currentData = $msSignupList->GetById($post["ms_list_id"]);
        if (
            ($currentData && $currentData["stage_id"] !== $post["stage_id"]) ||
            $currentData["status"] !== "pending"
        ) {
            http_response_code(200);
            echo json_encode([
                "success" => false,
                "error" =>
                    "This record has been processed to different stage. Please refresh the page.",
                "code" => "STAGE_MISMATCH",
            ]);
            exit();
        }
    }

    $arFields = [
        "status" => "success",
    ];

    $res = $msSignupList->Update($post["ms_list_id"], $arFields);

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "data" => $res,
        "timestamp" => time(),
    ]);
} catch (ApiException $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "code" => $e->getErrorCode(),
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Internal Server Error",
        "message" => $e->getMessage(),
    ]);
}
