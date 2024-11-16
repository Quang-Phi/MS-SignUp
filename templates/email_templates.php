<?php
class EmailTemplates
{
    private $appUrl;

    public function __construct($appUrl)
    {
        $this->appUrl = $appUrl;
    }

    public function getTemplate($type, $requestData)
    {
        switch ($type) {
            case 'request_review':
                return $this->getRequestReviewTemplate($requestData);
            case 'approval':
                return $this->getApprovalTemplate($requestData);
            case 'rejection':
                return $this->getRejectionTemplate($requestData);
            default:
                throw new Exception("Unknown email template type: " . $type);
        }
    }

    private function getRequestReviewTemplate($requestData)
    {
        $proposeList = '';
        if (is_array($requestData['propose'])) {
            foreach ($requestData['propose'] as $index => $item) {
                $proposeList .= "<li>{$item}</li>";
            }
        }

        $proposalSection = '';
        if (!empty($proposeList)) {
            $proposalSection = "
                <p>Dưới đây là các đề xuất cần thiết của nhân viên " . $requestData['user_name'] . ":</p>
                    <ol>
                        {$proposeList}
                    </ol>
                ";
        }

        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không Rep mail này!</p>
               <h2>Thông báo yêu cầu xét duyệt mới</h2>
               
               <h3>Thông tin nhân viên:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
               </ul>
               
               <h3>Đề xuất tham gia:</h3>
               <ul>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
               </ul>
               {$proposalSection}

               <p>Vui lòng ấn vào link để xét duyệt yêu cầu: 
                        <a href='{$this->appUrl}/ms-signup/form/list/'>
                            {$this->appUrl}/ms-signup/form/list/
                        </a>
                    </p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Mọi thông tin chi tiết vui lòng liên hệ trực tiếp hoặc gửi email về bộ phận liên quan để được giải đáp.</p>
               <p>Trân trọng.</p>
           </div>
       ";

        return $template . $footer;
    }

    private function getApprovalTemplate($requestData)
    {
        return "";
    }

    private function getRejectionTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không Rep mail này!</p>
               <h2>Thông báo từ chối yêu cầu</h2>
               
               <h3>Thông tin nhân viên:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
               </ul>
               
               <h3>Chi tiết yêu cầu:</h3>
               <ul>
                   <li>Mã yêu cầu: {$requestData['id']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Người từ chối yêu cầu: {$requestData['reviewer']}</li>
                   <li>Trạng thái: Đã từ chối</li>
               </ul>
       
               <p style='color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>
                   Lý do từ chối: {$requestData['comments']}
               </p>
               <p>Vui lòng ấn vào link để biết thêm thông tin: 
                        <a href='{$this->appUrl}/ms-signup/form/list/'>
                            {$this->appUrl}/ms-signup/form/list/
                        </a>
                    </p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Mọi thông tin chi tiết vui lòng liên hệ trực tiếp hoặc gửi email về bộ phận liên quan để được giải đáp.</p>
               <p>Trân trọng.</p>
           </div>
       ";

        return $template . $footer;
    }
}