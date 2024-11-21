<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
CModule::IncludeModule('socialnetwork');

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/socialnetwork/classes/general/user_group.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/services/mail_service.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/model/ms_signup_list.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/model/reviewer_stage.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/env.php";

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

try {
    $msSignupList = new MsSignupList($config);
    $reviewerStage = new ReviewerStage();
    $userToGroup = new CSocNetUserToGroup();

    $jsonData = file_get_contents("php://input");
    $post = json_decode($jsonData, true);

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

    $msSignupList->Update($post["ms_list_id"], $arFields);

    $user = new CUser();
    $userCurrentDpmIds = json_decode($post["department_ids"], true);

    $dpmIds = array_merge($userCurrentDpmIds, [$post["team_ms_id"]]);
    $user->Update(
        $post["user_id"],
        [
            "UF_DEPARTMENT" => array_unique($dpmIds),
            $config["user_type_ms_field"] => $post["type_ms_id"],
        ],
    );

    $userId = intval($post["user_id"]);
    $workgroup_ids = $config["workgroup_ms_ids"];

    foreach ($workgroup_ids as $workgroup_id) {
        $userToGroup->Add([
            "USER_ID" => $userId,
            "GROUP_ID" => $workgroup_id,
            "ROLE" => SONET_ROLES_USER,
            "INITIATED_BY_TYPE" => SONET_INITIATED_BY_USER,
            "INITIATED_BY_USER_ID" => $userId,
            "MESSAGE" => "",
            "SEND_MAIL" => "N",
            "SEND_MESSAGE" => "N"
        ]);
    };
    $requestData = [];

    $mailService = new MailService($config);
    $mailService->sendRequestNotification(
        'approval',
        $post["user_id"],
        $requestData
    );
    // foreach ($config["send_mail_to"] as $key => $item) {
    //     if (empty($item)) {
    //         continue;
    //     }
    //     if (!empty($item['id'])) {
    //         $mailService->sendRequestNotification(
    //             $key,
    //             $item['id'],
    //             $requestData
    //         );
    //     }
    //     if (!empty($item['email'])) {
    //         $mailService->sendRequestNotification(
    //             $key,
    //             $item['email'],
    //             $requestData
    //         );
    //     }
    // }

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
