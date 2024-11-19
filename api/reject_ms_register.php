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
    $mailService = new MailService($config);

    $jsonData = file_get_contents("php://input");
    $post = json_decode($jsonData, true);

    if (isset($post["id"])) {
        $currentData = $msSignupList->GetById($post["id"]);
        if (
            ($currentData && $currentData["stage_id"] !== $post["stage_id"]) ||
            $currentData["status"] === "error"
        ) {
            http_response_code(200);
            $errorMessage =
                $currentData["status"] === "error"
                    ? "This record has already been rejected"
                    : "This record has been processed to different stage. Please refresh the page";

            echo json_encode([
                "success" => false,
                "error" => $errorMessage,
                "code" => "STAGE_MISMATCH",
            ]);
            exit();
        }
    }

    $arFields = [
        "status" => "error",
        "comments" => $post["comments"],
    ];

    $arrFields2 = [
        "ms_list_id" => $post["id"],
    ];

    $res = $msSignupList->Update($post["id"], $arFields);

    if ($res) {
        $stage = intval($post["stage_id"]);
        if ($stage === 1) {
            $reviewerIds = $post["user_id"];
        } else {
            $list = $reviewerStage->GetList([], $arrFields2);
            $reviewerIds = array_column(
                array_filter($list, function ($item) use ($stage) {
                    return $item["stage_id"] < $stage;
                }),
                "reviewer_id"
            );
            $reviewerIds = array_unique(array_merge($reviewerIds, [$post["user_id"]]));
        }

        $requestData = [
            "id" => $post["id"],
            "user_name" => $post["user_name"],
            "user_email" => $post["user_email"],
            "employee_id" => $post["employee_id"],
            "department" => $post["department"],
            "type_ms" => $post["type_ms"],
            "team_ms" => $post["team_ms"],
            "reviewer" => $post["reviewer"],
            "comments" => $post["comments"],
        ];

        if (!empty($reviewerIds)) {
            try {
                $mailResult = $mailService->sendRequestNotification(
                    "rejection",
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
