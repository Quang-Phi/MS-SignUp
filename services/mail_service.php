<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
require $_SERVER["DOCUMENT_ROOT"] . "/page-custom/ms-signup/templates/email_templates.php";

use Bitrix\Main\Mail\Mail;

class MailService
{
    private $config;
    private $emailTemplates;
    private $env;

    public function __construct($env)
    {
        $this->config = [
            'from_email' => $env['from_mail'],
            'app_url' => $env['base_url'] . '/' . $env['root_folder'],
        ];

        $this->env = $env;

        $this->emailTemplates = new EmailTemplates( $this->env, $this->config['app_url']);
    }

    public function sendRequestNotification($type = 'review', $reviewers, $requestData)
    {
        try {
            if (!is_array($reviewers)) {
                $reviewers = [$reviewers];
            }

            $successCount = 0;
            $errors = [];

            foreach ($reviewers as $reviewer) {
                try {
                    if (is_numeric($reviewer)) {
                        $to = $this->getReviewerEmail($reviewer);
                        if (!$to) {
                            throw new Exception("Email not found for reviewer " . $reviewer);
                        }
                    } else {
                        $to = $reviewer;
                    }

                    $subject = $this->getSubjectByType($type, $requestData);

                    $headers = [
                        'From' => $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
                        'Reply-To' => $this->config['from_email'],
                        'MIME-Version' => '1.0',
                        'Content-Type' => 'text/html; charset=UTF-8'
                    ];

                    $result = Mail::send([
                        'TO' => $to,
                        'FROM' => $this->config['from_email'],
                        'SUBJECT' => $subject,
                        'BODY' => $this->emailTemplates->getTemplate($type, $requestData),
                        'CONTENT_TYPE' => 'html',
                        'CHARSET' => 'UTF-8',
                        'HEADER' => $headers
                    ]);

                    if ($result) {
                        $successCount++;
                    } else {
                        throw new Exception("Failed to send email to " . $to);
                    }
                } catch (Exception $e) {
                    $errors[] = "Error sending to reviewer {$reviewer}: " . $e->getMessage();
                }
            }

            if ($successCount === 0) {
                throw new Exception("Failed to send all emails. Errors: " . implode(", ", $errors));
            }

            return [
                'success' => true,
                'total' => count($reviewers),
                'sent' => $successCount,
                'failed' => count($reviewers) - $successCount,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            throw new Exception("Email sending failed: " . $e->getMessage());
        }
    }

    private function getSubjectByType($type, $requestData)
    {
        $subjects = [
            'review' => "Thông báo yêu cầu xét duyệt mới từ {$requestData['user_name']}",
            'review_kpi' => "Thông báo yêu cầu xét duyệt KPIs của {$requestData['user_name']}",
            'ms_review_kpi' => "Thông báo yêu cầu xác nhận KPIs",
            'approval' => "Thông báo yêu cầu đã được phê duyệt",
            'approval_notification' => "Thông báo yêu cầu từ {$requestData['user_name']} đã được phê duyệt",
            'rejection_notification' => "Thông báo yêu cầu từ {$requestData['user_name']} đã bị từ chối",
            'rejection' => "Thông báo yêu cầu đã bị từ chối",
        ];

        return isset($subjects[$type]) ? $subjects[$type] : $subjects['review'];
    }

    private function getReviewerEmail($reviewer)
    {
        $user = CUser::GetByID($reviewer)->Fetch();
        return $user["EMAIL"];
    }
}
