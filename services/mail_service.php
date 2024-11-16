<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
require $_SERVER["DOCUMENT_ROOT"] . "/ms-signup/templates/email_templates.php";

use Bitrix\Main\Mail\Mail;

class MailService
{
    private $config;
    private $emailTemplates;

    public function __construct()
    {
        $this->config = [
            'from_email' => 'dac2023@esuhai.com',
            'from_name' => 'Phi',
            'app_url' => 'https://bitrixdev.esuhai.org'
        ];

        $this->emailTemplates = new EmailTemplates($this->config['app_url']);
    }

    public function sendRequestNotification($type = 'request_review', $reviewerIds, $requestData)
    {
        try {
            if (!is_array($reviewerIds)) {
                $reviewerIds = [$reviewerIds];
            }

            $successCount = 0;
            $errors = [];

            foreach ($reviewerIds as $reviewerId) {
                try {
                    $to = $this->getReviewerEmail($reviewerId);
                    if (!$to) {
                        throw new Exception("Email not found for reviewer " . $reviewerId);
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
                    $errors[] = "Error sending to reviewer {$reviewerId}: " . $e->getMessage();
                }
            }

            if ($successCount === 0) {
                throw new Exception("Failed to send all emails. Errors: " . implode(", ", $errors));
            }

            return [
                'success' => true,
                'total' => count($reviewerIds),
                'sent' => $successCount,
                'failed' => count($reviewerIds) - $successCount,
                'errors' => $errors
            ];
        } catch (Exception $e) {
            throw new Exception("Email sending failed: " . $e->getMessage());
        }
    }

    private function getSubjectByType($type, $requestData)
    {
        $subjects = [
            'request_review' => "Thông báo yêu cầu xét duyệt mới từ {$requestData['name']}",
            'request_approved' => "Thông báo yêu cầu đã được phê duyệt",
            'request_rejected' => "Thông báo yêu cầu đã bị từ chối",
        ];

        return isset($subjects[$type]) ? $subjects[$type] : $subjects['request_review'];
    }

    private function getReviewerEmail($reviewerId)
    {
        $user = CUser::GetByID($reviewerId)->Fetch();
        return $user["EMAIL"];
    }
}
