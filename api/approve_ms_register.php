<?php
require $_SERVER["DOCUMENT_ROOT"] . "/page-custom/ms-manage/model/ms_signup_list.php";
require $_SERVER["DOCUMENT_ROOT"] . "/page-custom/ms-manage/model/reviewer_stage.php";
require $_SERVER["DOCUMENT_ROOT"] . "/page-custom/ms-manage/services/mail_service.php";
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/env.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/stage.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/kpi.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/kpi_history.php';


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");

try {
    $msSignupList = new MsSignupList(env: $config);
    $reviewerStage = new ReviewerStage();
    $stage = new Stage();
    $kpi = new Kpi();
    $kpiHistory = new KpiHistory();
    $mailService = new MailService($config);

    $jsonData = file_get_contents("php://input");
    $post = json_decode($jsonData, true);

    $stageDeal = json_decode($post["stage_deal"], true);
    $currentData = $msSignupList->GetById($post["id"]);

    if ($currentData['stage_id'] == 3  &&  $post['tempo_stage'] == 4) {
        array_push($stageDeal, $currentData['stage_id']);
        array_unique($stageDeal);
        sort($stageDeal);
    }

    $currStage = $stageDeal[0];
    $nextStageId = null;

    $arFields = array();

    if ($post['status'] != 'success') {
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

    if (empty($stageDeal)) {
        if ($currentData["process_deal"] && !empty(json_decode($currentData["process_deal"], true))) {
            $arr = json_decode($currentData["process_deal"], true);
            array_unique($arr);
            sort($arr);
            $nextStageId = $arr[0];;
            array_shift($arr);
            $arFields = [
                "process_deal" => json_encode($arr),
                "stage_id" => $nextStageId
            ];
        } else {
            $nextStageId = intval($post["stage_id"]) + 1;
            $arFields["stage_id"] = $nextStageId;
        }
    } else {
        array_push($stageDeal, $post["max_stage"]);
        array_unique($stageDeal);
        sort($stageDeal);
        $nextStageId = $stageDeal[0];
        array_shift($stageDeal);
        $arFields = [
            "process_deal" => json_encode($stageDeal),
            "stage_id" => $nextStageId,
            "status" => "pending"
        ];
    }

    if ($post["flag_edit_3"] == true) {
        $arFields["flag_edit_3"] = true;
    }

    if ($post["flag_edit_4"] == true) {
        $arFields["flag_edit_4"] = true;
    }

    $arrFields2 = [
        "ms_list_id" => $post["id"],
        "stage_id" => $nextStageId,
    ];
    $listStage = $stage->GetList();
    $stageLabel = '';
    foreach ($listStage as $item) {
        if ($item['stage_id'] == $nextStageId) {
            $stageLabel = $item['label'];
            break;
        }
    }
    $res = $msSignupList->Update($post["id"], $arFields);
    if ($res) {
        $list = $reviewerStage->GetList([], $arrFields2);
        $reviewerIds = array_map(function ($reviewer) {
            return $reviewer["reviewer_id"];
        }, $list);


        $requestData = [
            "id" => $post["id"],
            "user_name" => $post["user_name"],
            "user_email" => $post["user_email"],
            "employee_id" => $post["employee_id"],
            "department" => $post["department"],
            "type_ms" => $post["type_ms"],
            "team_ms" => $post["team_ms"],
            "propose" => $post["propose"],
            "department_name" => $stageLabel,
        ];
        if (!$stageDeal && ($res['process_deal'] == null || empty(json_decode($res["process_deal"], true)))) {
            if (!empty($reviewerIds)) {
                $mailResult = $mailService->sendRequestNotification(
                    "review",
                    $reviewerIds,
                    $requestData
                );
            }
        } else if (($stageDeal || !empty(json_decode($res["process_deal"], true))) && $nextStageId != $post["max_stage"]) {
            $arFilter = array(
                'ms_list_id' => $post['id'],
                'user_id' => $post['user_id'],
            );
            if ($stageDeal && !json_decode($res["process_deal"])) {
                $arFilter['stage_id'] = $currStage;
            } else {
                $arFilter['stage_id'] = $nextStageId;
            }

            $res_c = $kpi->GetList(array(), $arFilter);
            $newKpi = json_decode($res_c[0]['kpi'], true);
            if (is_array($newKpi) && count($newKpi) > 0) {
                $requestData['new_kpi'] = $newKpi;
            }

            $arOrder = ['created_at' => 'DESC'];
            $arFilter2 = array(
                'kpi_id' => $res_c[0]['id'],
                'stage_id' => $res_c[0]['stage_id'],
                'is_temporary' => true
            );
            $res_h = $kpiHistory->GetList($arOrder, $arFilter2, array());
            $oldKpi = json_decode($res_h[0]['old_kpi'], true);
            if (is_array($oldKpi) && count($oldKpi) > 0) {
                $requestData['old_kpi'] = $oldKpi;
            }
            $mailService->sendRequestNotification(
                "review_kpi",
                $reviewerIds,
                $requestData
            );
        } else {
            $list = $reviewerStage->GetList([], $arrFields2);
            $reviewerIds = array_map(function ($reviewer) {
                return $reviewer["reviewer_id"];
            }, $list);
            $mailService->sendRequestNotification(
                "ms_review_kpi",
                $reviewerIds,
                $requestData
            );
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
