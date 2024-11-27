<?php
class EmailTemplates
{
    private $appUrl;
    private $env;

    public function __construct($env, $appUrl)
    {
        $this->appUrl = $appUrl;
        $this->env = $env;
    }

    public function getTemplate($type, $requestData)
    {
        $templateMap = [
            'review' => 'getRequestReviewTemplate',
            'review_kpi' => 'getRequestReviewKpiTemplate',
            'ms_review_kpi' => 'getRequestMSReviewKpiTemplate',
            'approval' => 'getApprovalTemplate',
            'rejection' => 'getRejectionTemplate',
            'approval_notification' => 'getApprovalNotificationTemplate',
            'rejection_notification' => 'getRejectionNotificationTemplate',
        ];

        foreach ($this->env['send_mail_to'] as $key => $item) {
            $templateMap[$key] = 'get' . ucfirst(str_replace('_', '', $key)) . 'Template';
        }

        if (isset($templateMap[$type])) {
            return $this->{$templateMap[$type]}($requestData);
        } else {
            throw new Exception("Unknown email template type: " . $type);
        }
    }

    private function getRequestReviewTemplate($requestData)
    {
        $proposeList = '';
        if (is_array($requestData['propose']) && count($requestData['propose']) > 0) {
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
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo yêu cầu xét duyệt mới</h2>
               
               <p>Kính gửi Anh/Chị {$requestData['department_name']},</p>
               <p>Chúng tôi xin thông báo rằng nhân viên {$requestData['user_name']} đã gửi yêu cầu xét duyệt mới. Dưới đây là thông tin chi tiết:</p>
               
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
        
               <p>Vui lòng xét duyệt trực tiếp qua hệ thống Bitrix tại: 
                        <a href='{$this->appUrl}/form/list/?tab=1&id={$requestData['id']}'>
                            {$this->appUrl}/form/list/?tab=1&id={$requestData['id']}
                        </a>
                    </p>
           </div>
        ";

        $footer = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <p>Mọi thông tin chi tiết vui lòng liên hệ trực tiếp hoặc gửi email về bộ phận liên quan để được giải đáp.</p>
            <p>Trân trọng,</p>
            <p>DCC Team</p>
            <p>Hệ thống Bitrix Esuhai</p>
        </div>
    ";

        return $template . $footer;
    }

    private function getRequestReviewKpiTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo yêu cầu xét duyệt KPIs</h2>
               
               <p>Kính gửi Anh/Chị {$requestData['department_name']},</p>
               <p>Chúng tôi xin thông báo rằng các KPIs được đặt ra cho MS  {$requestData['user_name']} đã không được MS phê duyệt. Dưới đây là thông tin chi tiết:</p>
               
               <h3>Thông tin nhân viên:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
               </ul>

               <h3>Chi tiết KPIs:</h3>
                <p>Chi tiết KPIs ban đầu:</p>
                <table border='1' cellpadding='5' cellspacing='0'>
                    <tr>
                        <th>Chương trình</th>
                        " . $this->getMonthHeaders() . "
                    </tr>
                    " . $this->getKpiRows($requestData['old_kpi']) . "
                </table>
                
                <p>Đề xuất mới từ MS:</p>
                <table border='1' cellpadding='5' cellspacing='0'>
                    <tr>
                        <th>Chương trình</th>
                        " . $this->getMonthHeaders() . "
                    </tr>
                    " . $this->getKpiRows($requestData['new_kpi']) . "
                </table>
               
                <p>
                Để đảm bảo tiến độ và hiệu quả công việc, kính đề nghị MSA và Phòng Nhân sự xem xét lại KPIs theo đề xuất mới từ MS. 
                Quý phòng ban vui lòng đưa ra ý kiến phản hồi hoặc phê duyệt đề xuất này trước ngày [Hạn chót xét duyệt].
                </p>

               <p>Vui lòng phản hồi trực tiếp qua hệ thống Bitrix tại: 
                    <a href='{$this->appUrl}/form/list/?tab=1&id={$requestData['id']}'>
                        {$this->appUrl}/form/list/?tab=1&id={$requestData['id']}
                    </a>
                </p>
           </div>
        ";

        $footer = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <p>Mọi thông tin chi tiết vui lòng liên hệ trực tiếp hoặc gửi email về bộ phận liên quan để được giải đáp.</p>
            <p>Trân trọng,</p>
            <p>DCC Team</p>
            <p>Hệ thống Bitrix Esuhai</p>
        </div>
    ";

        return $template . $footer;
    }

    private function getRequestMSReviewKpiTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo yêu cầu xác nhận KPIs</h2>
               
               <p>Kính gửi Anh/Chị {$requestData['user_name']},</p>
               <p>Chúng tôi xin thông báo rằng các KPIs đã được phê duyệt bởi các phòng ban liên quan.</p>
               
               <h3>Thông tin nhân viên:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
               </ul>
               
               <p>
               Để đảm bảo tiến độ và hiệu quả công việc, kính đề nghị Anh/Chị xác nhận các KPIs đã được phê duyệt này trước ngày [Hạn chót xác nhận].
               </p>
    
               <p>Vui lòng phản hồi trực tiếp qua hệ thống Bitrix tại: 
                    <a href='{$this->appUrl}/form/list/?tab=1&id={$requestData['id']}'>
                        {$this->appUrl}/form/list/?tab=1&id={$requestData['id']}
                    </a>
               </p>
           </div>
        ";
    
        $footer = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <p>Mọi thông tin chi tiết vui lòng liên hệ trực tiếp hoặc gửi email về bộ phận liên quan để được giải đáp.</p>
            <p>Trân trọng,</p>
            <p>DCC Team</p>
            <p>Hệ thống Bitrix Esuhai</p>
        </div>
    ";
    
        return $template . $footer;
    }

    private function getApprovalTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo đăng ký MS thành công</h2>
               
               <p>Kính gửi {$requestData['user_name']},</p>
               <p>Chúng tôi xin thông báo rằng yêu cầu đăng ký MS của bạn đã được xác nhận thành công.</p>
               
               <h3>Thông tin chi tiết:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
               </ul>
               
               <p>Bạn đã được gửi các yêu cầu đến các phòng ban liên quan:</p>
               <ol>
                   <li>Truy cập CRM (DCC)</li>
                   <li>Dashboard MS (DCC)</li>
                   <li>Liune tổng đài (ICT)</li>
                   <li>Gia nhập nhóm email MS (Nhân sự)</li>
                   <li>Add department và Ad team MS bitrx (Nhân sự)</li>
                   <li>Hướng dẫn hội nhập (team đào tạo MSA, MSL)</li>
                   <li>Add vào nhóm MS trao đổi thông tin (MSA)</li>
               </ol>
               
               <p>Vui lòng truy cập hệ thống Bitrix Esuhai qua đường link dưới đây để kiểm tra và xác nhận thông tin KPIs:</p>
               <p>
                   <a href='{$this->appUrl}/form/list/?tab=2&id={$requestData['id']}'>
                       {$this->appUrl}/form/list/?tab=2&id={$requestData['id']}
                   </a>
               </p>
               
               <p>Nếu bạn có bất kỳ thắc mắc hoặc cần hỗ trợ thêm, vui lòng liên hệ bộ phận DCC. HR, MSA liên quan đến được hỗ trợ.</p>
               <p>Chúc anh chị vượt chỉ tiêu thời gian tới.</p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Trân trọng,</p>
               <p>DCC Team</p>
               <p>Hệ thống Bitrix Esuhai</p>
           </div>
       ";

        return $template . $footer;
    }

    private function getRejectionTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo từ chối yêu cầu</h2>
               
               <p>Kính gửi {$requestData['user_name']},</p>
               <p>Chúng tôi xin thông báo rằng yêu cầu của bạn đã bị từ chối. Dưới đây là thông tin chi tiết:</p>
               
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
               <p>Xem thông tin chi tiết trực tiếp qua hệ thống Bitrix tại:
                    <a href='{$this->appUrl}/form/list/?tab=3&id={$requestData['id']}'>
                        {$this->appUrl}/form/list/?tab=3&id={$requestData['id']}
                    </a>
                </p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Mọi thông tin chi tiết vui lòng liên hệ trực tiếp hoặc gửi email về bộ phận liên quan để được giải đáp.</p>
               <p>Trân trọng,</p>
               <p>DCC Team</p>
               <p>Hệ thống Bitrix Esuhai</p>
           </div>
       ";

        return $template . $footer;
    }

    private function getSupportTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Đăng ký MS thành công - Yêu cầu hỗ trợ MS gia nhập hoạt động tạo nguồn</h2>
               
               <p>Kính gửi các phòng ban liên quan,</p>
               <p>Nhằm đảm bảo hỗ trợ nhân viên mới đăng ký MS tham gia các hoạt động chạy nguồn, các phòng ban vui lòng phối hợp thực hiện các công việc liên quan. Dưới đây là thông tin chi tiết của nhân viên:</p>
               
               <h3>Thông tin nhân viên:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
               </ul>
               
               <p>Yêu cầu thực hiện:</p>
               <ol>
                   <li>Bộ phận DCC:
                       <ul>
                           <li>Cấp quyền truy cập CRM.</li>
                           <li>Thêm nhân viên vào nhóm email MS.</li>
                           <li>Cập nhật thông tin vào danh sách MS.</li>
                       </ul>
                   </li>
                   <li>Bộ phận Nhân sự:
                       <ul>
                           <li>Xác minh thông tin nhân viên và cập nhật hệ thống nội bộ.</li>
                           <li>Đảm bảo nhân viên nhận được tài liệu hướng dẫn liên quan đến hoạt động chạy nguồn.</li>
                       </ul>
                   </li>
                   <li>Bộ phận Đào tạo MS:
                       <ul>
                           <li>Sắp xếp lịch đào tạo ban đầu về các hoạt động MS.</li>
                           <li>Cung cấp tài liệu và hỗ trợ nhân viên trong quá trình chạy nguồn.</li>
                       </ul>
                   </li>
                   <li>Bộ phận MSA:
                       <ul>
                           <li>Add vào nhóm MS trao đổi thông tin.</li>
                       </ul>
                   </li>
               </ol>
               
               <p>Vui lòng hoàn thành các nhiệm vụ trên trước ngày [Hạn chót] để đảm bảo nhân viên có thể bắt đầu tham gia hoạt động một cách hiệu quả.</p>
               <p>Và phản hồi mail cho MS tại Email: {$requestData['user_email']} khi hoàn thành để đảo bảo thông tin được xuyên suốt.</p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Trân trọng,</p>
               <p>Admin Team</p>
               <p>Hệ thống Bitrix Esuhai</p>
           </div>
       ";

        return $template . $footer;
    }

    private function getApprovalNotificationTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo đăng ký MS thành công</h2>
               
               <p>Kính gửi các phòng ban liên quan,</p>
               <p>Chúng tôi xin thông báo rằng yêu cầu đăng ký MS của nhân viên {$requestData['user_name']} đã được xác nhận thành công.</p>
               
               <h3>Thông tin chi tiết:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
               </ul>
                <p>Xem thông tin chi tiết trực tiếp qua hệ thống Bitrix tại:
                    <a href='{$this->appUrl}/form/list/?tab=2&id={$requestData['id']}'>
                        {$this->appUrl}/form/list/?tab=2&id={$requestData['id']}
                    </a>
                </p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Trân trọng,</p>
               <p>Admin Team</p>
               <p>Hệ thống Bitrix Esuhai</p>
           </div>
       ";

        return $template . $footer;
    }

    private function getRejectionNotificationTemplate($requestData)
    {
        $template = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Đây là email thông báo từ Admin của hệ thống Bitrix Esuhai. Vui lòng không trả lời email này!</p>
               <h2>Thông báo đăng ký MS bị từ chối</h2>
               
               <p>Kính gửi các phòng ban liên quan,</p>
               <p>Chúng tôi xin thông báo rằng yêu cầu đăng ký MS của nhân viên {$requestData['user_name']} đã bị từ chối.</p>
               
               <h3>Thông tin chi tiết:</h3>
               <ul>
                   <li>Họ tên: {$requestData['user_name']}</li>
                   <li>Email: {$requestData['user_email']}</li>
                   <li>Mã nhân viên: {$requestData['employee_id']}</li>
                   <li>Phòng ban: {$requestData['department']}</li>
                   <li>Team MS: {$requestData['team_ms']}</li>
                   <li>Phân loại MS: {$requestData['type_ms']}</li>
                   <li>Người từ chối yêu cầu: {$requestData['reviewer']}</li>
               </ul>
               
               <p style='color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>
                   Lý do từ chối: {$requestData['comments']}
               </p>
                <p>Xem thông tin chi tiết trực tiếp qua hệ thống Bitrix tại:
                    <a href='{$this->appUrl}/form/list/?tab=3&id={$requestData['id']}'>
                        {$this->appUrl}/form/list/?tab=3&id={$requestData['id']}
                    </a>
                </p>
           </div>
       ";

        $footer = "
           <div style='font-family: Arial, sans-serif; padding: 20px;'>
               <p>Trân trọng,</p>
               <p>Admin Team</p>
               <p>Hệ thống Bitrix Esuhai</p>
           </div>
       ";

        return $template . $footer;
    }

    private function getMonthHeaders()
    {
        $headers = "";
        for ($i = 1; $i <= 12; $i++) {
            $headers .= "<th>T$i</th>";
        }
        return $headers;
    }

    private function getKpiRows($kpiData)
    {
        $rows = "";
        foreach ($kpiData as $kpi) {
            $rows .= "<tr>";
            $rows .= "<td>{$kpi['program']}</td>";
            for ($i = 1; $i <= 12; $i++) {
                $rows .= "<td>" . (isset($kpi['m' . $i]) && $kpi['m' . $i] !== "" ? $kpi['m' . $i] : 0) . "</td>";
            }
            $rows .= "</tr>";
        }
        return $rows;
    }
}
