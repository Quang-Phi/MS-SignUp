<?php
require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

class ApiService
{
    private $baseUrl = 'https://bitrixdev.esuhai.org/rest/544/3bm30pypmym3jou1';
    private $token;

    public function __construct()
    {
        // Khởi tạo cache nếu cần
        // $this->cache = new Cache();
    }

    /**
     * Lấy danh sách phòng ban
     */
    public function getListTeamMS($userId)
    {
        // Check cache first
        // $cached = $this->cache->get('departments');
        // if ($cached) return $cached;
        if ($userId) {
            $listDepartment = CUser::getById($userId)->fetch()["UF_DEPARTMENT"];
            $teamSelect = array("ID", "NAME");
            $listTeamMS = array();
            foreach ($listDepartment as $idDepartment) {
                $arFilter = array(
                    "IBLOCK_ID" => 16,
                    "PROPERTY_67" => $idDepartment
                );
                $temp_listTeam = CIBlockElement::GetList(
                    array(),
                    $arFilter,
                    false,
                    array("nPageSize" => 50),
                    $teamSelect
                )->arResult;
                if ($temp_listTeam != false) {
                    $listTeamMS[$temp_listTeam[0]["ID"]] = $temp_listTeam[0]["NAME"];
                }
            }
            return $listTeamMS;
        }
        $departments = [];
        $index = 0;

        do {
            $response = $this->makeRequest("/department.get", [
                'start' => $index
            ]);

            if (!$response || !isset($response['result'])) {
                break;
            }

            $departments = array_merge($departments, $response['result']);
            $index = count($departments);
        } while ($response && isset($response['total']) && count($departments) < $response['total']);

        $listTeamMS = $this->filterDepartments($departments);

        // Cache the results
        // $this->cache->set('departments', $listTeamMS, 3600); // cache for 1 hour

        return $listTeamMS;
    }

    /**
     * Tìm kiếm manager
     * @param string $query Từ khóa tìm kiếm
     * @return array Danh sách manager được tìm thấy
     */
    public function searchManager($name = '', $user_id = null)
    {
        try {
            // Validate input
            if (empty($name) && empty($user_id)) {
                return [];
            }

            if ($user_id && empty($name)) {
                $user = CUser::GetByID($user_id)->Fetch();
                $userFullName = htmlspecialchars($user["LAST_NAME"]) . " " . htmlspecialchars($user["NAME"]);
                $name = $userFullName;
            }

            $query = trim($name);
            // Gọi API Bitrix
            $response = $this->makeRequest("/user.search", [
                'FILTER' => [
                    '$FILTER[WORK_POSITION]' => '%Trưởng%',
                    'NAME' => $query
                ]
            ]);

            // Kiểm tra response
            if (!$response || !isset($response['result'])) {
                throw new ApiException('Invalid response from Bitrix API');
            }

            // Transform data
            return array_map(function ($user) {
                $positionLabel = '';
                if (isset($user["UF_USR_1704790919453"])) {
                    $position = $user["UF_USR_1704790919453"];
                    $enumList = CUserFieldEnum::GetList(array(), array('ID' => $position));

                    while ($enum = $enumList->Fetch()) {
                        $positionLabel = $enum['VALUE'];
                    }
                }
                return [
                    'ID' => $user['ID'] ?? '',
                    'NAME' => $user['NAME'] ?? '',
                    'LAST_NAME' => $user['LAST_NAME'] ?? '',
                    'UF_DEPARTMENT' => $user['UF_DEPARTMENT'] ?? [],
                    'WORK_POSITION' => $user['WORK_POSITION'] ?? '',
                    'USER_TYPE' => $user['USER_TYPE'] ?? '',
                    'PERSONAL_PHOTO' => $user['PERSONAL_PHOTO'] ?? '',
                    'POSITION' => $positionLabel ?? '',
                ];
            }, $response['result']);
        } catch (Exception $e) {
            // Log error
            error_log("Error in searchManager: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Make HTTP request to Bitrix24 API using file_get_contents
     */
    private function makeRequest($endpoint, $params = [])
    {
        try {
            // Construct full URL
            $url = $this->baseUrl . $endpoint;

            // Add params to URL if not empty
            if (!empty($params)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            }

            // Set stream context options
            $opts = [
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'Content-Type: application/json',
                        'Accept: application/json'
                    ],
                    'timeout' => 30,
                    'ignore_errors' => true
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ];

            // Add authorization if token exists
            if ($this->token) {
                $opts['http']['header'][] = "Authorization: Bearer " . $this->token;
            }

            // Create stream context
            $context = stream_context_create($opts);

            // Make the request
            $response = file_get_contents($url, false, $context);

            // Check for errors
            if ($response === false) {
                throw new Exception('Failed to get response from API');
            }

            // Get response headers
            $responseHeaders = $http_response_header ?? [];

            // Check HTTP status code
            $statusLine = $responseHeaders[0] ?? '';
            if (strpos($statusLine, '200') === false) {
                throw new Exception('API returned non-200 status: ' . $statusLine);
            }

            // Decode JSON response
            $decodedResponse = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("JSON decode error: " . json_last_error_msg());
            }

            // Handle API errors if any
            if (isset($decodedResponse['error'])) {
                throw new ApiException(
                    $decodedResponse['error_description'] ?? $decodedResponse['error'],
                    $decodedResponse['error_code'] ?? 0
                );
            }

            return $decodedResponse;
        } catch (Exception $e) {
            // Log error
            $this->log("API Request Error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Filter departments starting with 'MS'
     */
    private function filterDepartments($departments)
    {
        return array_filter($departments, function ($dept) {
            return isset($dept['NAME']) && strpos($dept['NAME'], 'MS') === 0;
        });
    }

    /**
     * Handle API errors
     */
    private function handleApiError($response)
    {
        if (isset($response['error'])) {
            throw new Exception("API Error: " . ($response['error_description'] ?? $response['error']));
        }
    }
}

// Optional: Add custom exceptions if needed
class ApiException extends Exception
{
    protected $errorCode;

    public function __construct($message, $code = 0)
    {
        $this->errorCode = $code;
        parent::__construct($message, $code);
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
