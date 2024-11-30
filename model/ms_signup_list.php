<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

class MsSignupList extends CDBResult
{
  private $db;

  const SCHEMA = 's2config';
  const TABLE_NAME = 'ms_signup_list';
  const ID_FIELD = 'id';
  private $QUERY_TABLE;
  private $config = [];

  public function __construct($env)
  {
    global $DB;
    $this->db = $DB;
    $this->QUERY_TABLE = self::SCHEMA . "." . self::TABLE_NAME;
    $this->config = $env;
  }

  public function GetList($arOrder = array(), $arFilter = array(), $arOptions = array(), $check = true)
  {
    global $USER;
    $userID = intval($USER->GetID());
    if (in_array($userID, $this->config['admin_users'])) {
      $check = false;
    }
    $check ? $admin = $USER->IsAdmin() : $admin = true;

    $searchQuery = isset($arOptions['search']) ? trim($arOptions['search']) : '';
    $searchQuery = $this->db->ForSQL($searchQuery);

    if ($admin) {
      $baseQuery = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl";
    } else {
      // $baseQuery = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
      //                WHERE msl.user_id = '$userID'
      //                UNION
      //                SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
      //                INNER JOIN " . self::SCHEMA . ".reviewer_stage r ON msl.id = r.ms_list_id
      //                WHERE r.reviewer_id = '$userID'
      //                AND r.stage_id = msl.stage_id";
      $baseQuery = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
                     WHERE msl.user_id = '$userID'
                     UNION
                     SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
                     INNER JOIN " . self::SCHEMA . ".reviewer_stage r ON msl.id = r.ms_list_id
                     WHERE r.reviewer_id = '$userID'";
    }

    $whereParts = array();

    if (!empty($arFilter)) {
      $tableAlias = !$admin ? 'filtered_msl' : 'msl';
      foreach ($arFilter as $field => $value) {
        if ($value !== null && $value !== '') {
          $value = $this->db->ForSQL($value);
          $whereParts[] = "$tableAlias.$field = '$value'";
        }
      }
    }

    if (!empty($searchQuery)) {
      $tableAlias = !$admin ? 'filtered_msl' : 'msl';

      $formattedDate = $this->formatSearchDate($searchQuery);

      $searchConditions = [
        "$tableAlias.user_name LIKE '%$searchQuery%'",
        "$tableAlias.user_email LIKE '%$searchQuery%'",
        "$tableAlias.employee_id LIKE '%$searchQuery%'",
        "$tableAlias.id LIKE '%$searchQuery%'"
      ];

      if ($formattedDate) {
        switch ($formattedDate['type']) {
          case 'day':
            $searchConditions[] = "DAY($tableAlias.created_at) = '{$formattedDate['value']}'";
            break;
          case 'day_month':
            $searchConditions[] = "(MONTH($tableAlias.created_at) = '{$formattedDate['value']['month']}' 
                                         AND DAY($tableAlias.created_at) = '{$formattedDate['value']['day']}')";
            break;
          case 'full':
            $searchConditions[] = "DATE($tableAlias.created_at) = '{$formattedDate['value']}'";
            break;
        }
      }

      $whereParts[] = "(" . implode(" OR ", $searchConditions) . ")";
    }

    if (!empty($whereParts)) {
      if (!$admin) {
        $baseQuery = "SELECT * FROM ($baseQuery) as filtered_msl WHERE " . implode(" AND ", $whereParts);
      } else {
        $baseQuery .= " WHERE " . implode(" AND ", $whereParts);
      }
    }

    if ($admin) {
      $totalQuery = "SELECT COUNT(*) as total FROM ($baseQuery) as total_query";
      try {
        $totalRes = $this->db->Query($totalQuery);
        $total = $totalRes->Fetch();
      } catch (Exception $e) {
        error_log("Error in total count query: " . $e->getMessage());
        return false;
      }
    } else {
      $total = $this->getTotalCount($baseQuery);
    }
    
    if (!empty($arOrder)) {
      if ($admin) {
        $tableAlias = (!$admin && !empty($whereParts)) ? 'filtered_msl' : 'msl';
        $orderParts = array();
        foreach ($arOrder as $field => $direction) {
          $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
          $orderParts[] = "$tableAlias.$field $direction";
        }
        $baseQuery .= " ORDER BY " . implode(", ", $orderParts);
      } else {
        $orderParts = array();
        foreach ($arOrder as $field => $direction) {
          $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
          $orderParts[] = "$field $direction";
        }
        $baseQuery = "SELECT * FROM ($baseQuery) AS temp_table ORDER BY " . implode(", ", $orderParts);
      }
    }

    if (!empty($arOptions['limit'])) {
      $limit = intval($arOptions['limit']);
      $baseQuery .= " LIMIT $limit";

      if (!empty($arOptions['offset'])) {
        $offset = intval($arOptions['offset']);
        $baseQuery .= " OFFSET $offset";
      }
    }

    try {
      $dbRes = $this->db->Query($baseQuery);
      $arResult = array();
      while ($arRes = $dbRes->Fetch()) {
        $arRes['reviewers'] = $this->GetReviewers($arRes['id']);
        $arResult[] = $arRes;
      }

      return array(
        'items' => $arResult,
        'total' => intval($total['total'])
      );
    } catch (Exception $e) {
      error_log("Error in final query: " . $e->getMessage());
      return false;
    }
  }

  private function getTotalCount($baseQuery)
  {
    try {
      $countQuery = "SELECT COUNT(*) as total FROM (
        $baseQuery
      ) as combined_results";
  
      $totalRes = $this->db->Query($countQuery);
      if (!$totalRes) {
        return 0;
      }
  
      $total = $totalRes->Fetch();
      return $total ?: 0;
    } catch (Exception $e) {
      error_log("Error in total count query: " . $e->getMessage());
      return 0;
    }
  }

  private function formatSearchDate($date)
  {
    $date = trim($date);
    $currentDate = new DateTime();
    $currentYear = $currentDate->format('Y');
    $currentMonth = $currentDate->format('m');

    if (preg_match('/^(\d{1,2})$/', $date, $matches)) {
      $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
      if ($day >= 1 && $day <= 31) {
        return [
          "type" => "day",
          "value" => $day
        ];
      }
    }

    if (preg_match('/^(\d{1,2})\/?(\d{1,2})?\/?(\d{4})?$/', $date, $matches)) {
      $day = isset($matches[1]) ? str_pad($matches[1], 2, '0', STR_PAD_LEFT) : null;
      $month = isset($matches[2]) ? str_pad($matches[2], 2, '0', STR_PAD_LEFT) : $currentMonth;
      $year = isset($matches[3]) ? $matches[3] : $currentYear;

      if ($day && !isset($matches[2])) {
        return [
          "type" => "day",
          "value" => $day
        ];
      }

      if ($day && $month && !isset($matches[3])) {
        if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
          return [
            "type" => "day_month",
            "value" => ["month" => $month, "day" => $day]
          ];
        }
      }

      if ($day && $month && $year) {
        if (checkdate($month, $day, $year)) {
          return [
            "type" => "full",
            "value" => "$year-$month-$day"
          ];
        }
      }
    }

    return false;
  }

  private function GetReviewers($msListId)
  {
    $query = "SELECT rs.reviewer_id, rs.stage_id, s.require_kpi, s.label
              FROM " . self::SCHEMA . ".reviewer_stage rs
              LEFT JOIN " . self::SCHEMA . ".stage s ON rs.stage_id = s.stage_id
              WHERE rs.ms_list_id = '" . intval($msListId) . "'
              ORDER BY rs.stage_id ASC";

    $dbRes = $this->db->Query($query);
    $reviewers = array();

    while ($reviewer = $dbRes->Fetch()) {
      $hasKpi = false;
      if ($reviewer['require_kpi']) {
        $hasKpi = $this->CheckKpiExists(
          intval($msListId),
          intval($reviewer['reviewer_id']),
          intval($reviewer['stage_id'])
        );
      }

      $reviewers[] = array(
        'reviewer_id' => intval($reviewer['reviewer_id']),
        'stage_id' => intval($reviewer['stage_id']),
        'require_kpi' => (bool)$reviewer['require_kpi'],
        'stage_label' => $reviewer['label'],
        'has_kpi' => $hasKpi
      );
    }

    return $reviewers;
  }

  private function CheckKpiExists($msListId, $userId, $stageId)
  {
    $query = "SELECT COUNT(*) as count
              FROM " . self::SCHEMA . ".kpi
              WHERE ms_list_id = '" . intval($msListId) . "'
              AND stage_id = '" . intval($stageId) . "'";

    $dbRes = $this->db->Query($query);
    $result = $dbRes->Fetch();

    return $result['count'] > 0;
  }
  public function GetById($id)
  {
    $query = "SELECT * FROM " . $this->QUERY_TABLE . " WHERE " . self::ID_FIELD . " = '$id'";
    $dbRes = $this->db->Query($query);
    return $dbRes->Fetch();
  }

  public function Add($arFields)
  {
    $field = array();
    $value = array();
    foreach ($arFields as $key => $val) {
      $field[] = $key;
      $value[] = "'$val'";
    }
    $query = "INSERT INTO " . self::SCHEMA . "." . self::TABLE_NAME . " (" . implode(', ', $field) . ") VALUES (" . implode(', ', $value) . ")";
    $this->db->Query($query);
    return $this->db->LastID();
  }

  public function Update($id, $arFields)
  {
    $this->db->Query("UPDATE " . $this->QUERY_TABLE . " SET " . $this->GetSQLSetFields($arFields) . " WHERE " . self::ID_FIELD . " = '$id'");
    return $this->GetById($id);
  }

  private function GetSQLSetFields($arFields)
  {
    $setFields = array();
    foreach ($arFields as $field => $value) {
      $setFields[] = "$field = '$value'";
    }
    return implode(', ', $setFields);
  }

  public function Delete($id)
  {
    $this->db->Query("DELETE FROM " . $this->QUERY_TABLE . " WHERE " . self::ID_FIELD . " = '$id'");
    return true;
  }
}
