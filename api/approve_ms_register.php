<?php
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/services/mail_service.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/model/ms_signup_list.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/model/reviewer_stage.php";
require $_SERVER["DOCUMENT_ROOT"] . '/ms-signup/env.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

try {
    $msSignupList = new MsSignupList($config);
    $reviewerStage = new ReviewerStage();

    $jsonData = file_get_contents("php://input");
    $post = json_decode($jsonData, true);

    $checkEdit = $post["change_kpi"];
    $nextStageId = null;

    if (isset($post["id"]) && !$checkEdit) {
        $currentData = $msSignupList->GetById($post["id"]);
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
        $nextStageId = intval($post["stage_id"]) + 1;
    } else {
        $nextStageId = intval($post["stage_id"]);
    }
    $arFields = [
        "stage_id" => $nextStageId,
        "process_deal" => $checkEdit ? true : false,
    ];

    $arrFields2 = [
        "ms_list_id" => $post["id"],
        "stage_id" => $nextStageId,
    ];

    if (!$checkEdit) {
        $res = $msSignupList->Update($post["id"], $arFields);
        if ($res) {
            $list = $reviewerStage->GetList([], $arrFields2);

            $requestData = [
                "id" => $post["id"],
                "user_name" => $post["user_name"],
                "user_email" => $post["user_email"],
                "employee_id" => $post["employee_id"],
                "department" => $post["department"],
                "type_ms" => $post["type_ms"],
                "team_ms" => $post["team_ms"],
                "propose" => $post["propose"],
            ];

            $reviewerIds = array_map(function ($reviewer) {
                return $reviewer["reviewer_id"];
            }, $list);

            $mailService = new MailService($config);

            if (!empty($reviewerIds)) {
                try {
                    $mailResult = $mailService->sendRequestNotification(
                        "review",
                        $reviewerIds,
                        $requestData
                    );
                    error_log(
                        "Email notification result: " . json_encode($mailResult)
                    );
                } catch (Exception $e) {
                    error_log(
                        "Failed to send email notifications: " . $e->getMessage()
                    );
                }
            }
        }
    }

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
