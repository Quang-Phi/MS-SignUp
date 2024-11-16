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
        return "
            <div style='font-family: Arial, sans-serif; padding: 20px;'>
                <h2>Thông báo yêu cầu xét duyệt mới</h2>
                <p>Xin chào,</p>
                <p>Bạn có một yêu cầu xét duyệt mới cần được xem xét.</p>
                
                <h3>Chi tiết yêu cầu:</h3>
                <ul>
                    <li>Mã yêu cầu: {$requestData['id']}</li>
                    <li>Người yêu cầu: {$requestData['user_name']}</li>
                    <li>Trạng thái: Chờ xét duyệt</li>
                </ul>
                
                <p>Vui lòng ấn vào link để xem xét yêu cầu: 
                    <a href='{$this->appUrl}/ms-signup/form/list/'>
                        {$this->appUrl}/ms-signup/form/list/
                    </a>
                </p>
                
                <p>Trân trọng,<br>Admin</p>
            </div>
        ";
    }

    private function getApprovalTemplate($requestData)
    {
        return "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2>Thông báo phê duyệt yêu cầu</h2>
                    <p>Xin chào,</p>
                    <p>Yêu cầu của bạn đã được phê duyệt.</p>
                    
                    <h3>Chi tiết yêu cầu:</h3>
                    <ul>
                        <li>Mã yêu cầu: {$requestData['id']}</li>
                        <li>Người yêu cầu: {$requestData['user_name']}</li>
                        <li>Trạng thái: Đã phê duyệt</li>
                    </ul>
                    
                    <p>Vui lòng ấn vào link để xem xét yêu cầu: 
                        <a href='{$this->appUrl}/ms-signup/form/list/'>
                            {$this->appUrl}/ms-signup/form/list/
                        </a>
                    </p>
                    
                    <p>Trân trọng,<br>Admin</p>
                </div>
            ";
    }

    private function getRejectionTemplate($requestData)
    {
        return "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2>Thông báo từ chối yêu cầu</h2>
                    <p>Xin chào,</p>
                    <p>Rất tiếc, yêu cầu của bạn đã bị từ chối.</p>

                    <h3>Chi tiết yêu cầu:</h3>
                    <ul>
                        <li>Mã yêu cầu: {$requestData['id']}</li>
                        <li>Người từ chối yêu cầu: {$requestData['reviewer']}</li>
                        <li>Trạng thái: Đã từ chối</li>
                    </ul>
    
                    <p style='color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px;'>
                        Lý do từ chối: {$requestData['comments']}
                    </p>
                    
                    <p>Vui lòng ấn vào link để xem xét yêu cầu: 
                        <a href='{$this->appUrl}/ms-signup/form/list/'>
                            {$this->appUrl}/ms-signup/form/list/
                        </a>
                    </p>
                    
                    <p>Nếu bạn có thắc mắc, vui lòng liên hệ với chúng tôi để được giải đáp.</p>
                    
                    <p>Trân trọng,<br>Admin</p>
                </div>
            ";
    }
}
