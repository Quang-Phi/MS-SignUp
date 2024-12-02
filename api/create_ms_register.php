<?
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/ms_signup_list.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/reviewer_stage.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/model/stage.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/services/mail_service.php';
require $_SERVER["DOCUMENT_ROOT"] . '/page-custom/ms-manage/env.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

try {
  $formData = json_decode(file_get_contents('php://input'), true);

  $msSignupList = new MsSignupList($config);
  $reviewerStage = new ReviewerStage();
  $stage = new Stage();

  $listStage = $stage->GetList();
  $stageId = 1;
  $stageLabel = '';
  foreach ($listStage as $item) {
    if ($item['stage_id'] == $stageId) {
      $stageLabel = $item['label'];
      break;
    }
  }
  $maxStage = count($listStage);
  $data = [
    'employee_id' => $formData['employee_id'],
    'user_id' => $formData['user_id'],
    'user_name' => $formData['user_name'],
    'user_email' => $formData['user_email'],
    'stage_id' => $stageId,
    'max_stage' => $maxStage,
    'status' => $formData['status'],
    'department_id' => $formData['department_id'],
    'team_ms_id' => intval($formData['team_ms_id']),
    'type_ms_id' => intval($formData['type_ms_id']),
    'list_propose' => $formData['list_propose'],
    'confirmation' => $formData['confirmation'],
    'comments' => ''
  ];

  $res = $msSignupList->Add($data);
  $arr = [
    [
      'stage_id' => 2,
      'reviewer_id' => intval($formData['msl_id']),
      'ms_list_id' => $res
    ],
    [
      'stage_id' => 1,
      'reviewer_id' => intval($formData['manager']),
      'ms_list_id' => $res
    ],
    [
      'stage_id' => 5,
      'reviewer_id' => $formData['user_id'],
      'ms_list_id' => $res
    ]
  ];

  if (!empty($config['hr_ids'])) {
    foreach ($config['hr_ids'] as $key => $id) {
      $arr[] = [
        'stage_id' => 4,
        'reviewer_id' => $id,
        'ms_list_id' => $res
      ];
    }
  }

  $arrMsaId = array_unique(array_merge([intval($formData['msa_id'])], $config['msa_ids']));
  if (!empty($arrMsaId)) {
    foreach ($arrMsaId as $key => $id) {
      $arr[] = [
        'stage_id' => 3,
        'reviewer_id' => $id,
        'ms_list_id' => $res
      ];
    }
  }

  foreach ($arr as $key => $value) {
    $reviewerStage->Add($value);
  }

  $requestData = [
    "id" => $res,
    "user_name" => $formData["user_name"],
    "user_email" => $formData["user_email"],
    "employee_id" => $formData["employee_id"],
    "department" => $formData["department"],
    "type_ms" => $formData["type_ms"],
    "team_ms" => $formData["team_ms"],
    "department_name" => $stageLabel,
    "propose" => json_decode($formData["list_propose"], true),
  ];

  $mailService = new MailService($config);

  $currentReviewerIds = array_map(function ($reviewer) use ($stageId) {
    if ($reviewer['stage_id'] == $stageId) {
      return $reviewer['reviewer_id'];
    }
  }, $arr);

  $currentReviewerIds = array_filter($currentReviewerIds);

  if (!empty($currentReviewerIds)) {
    try {
      $mailResult = $mailService->sendRequestNotification(
        'review',
        $currentReviewerIds,
        $requestData
      );
      error_log("Sent notification emails: " . json_encode($mailResult));
    } catch (Exception $e) {
      error_log("Failed to send notification emails: " . $e->getMessage());
    }
  }

  http_response_code(200);
  echo json_encode([
    'success' => true,
    'data' => $res,
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
