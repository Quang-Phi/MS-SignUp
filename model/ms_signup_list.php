<?
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

class MsSignupList extends CDBResult
{
  private $db;

  const SCHEMA = 's2config';
  const TABLE_NAME = 'ms_signup_list';
  const ID_FIELD = 'id';

  private $QUERY_TABLE;

  public function __construct()
  {
    global $DB;
    $this->db = $DB;
    $this->QUERY_TABLE = self::SCHEMA . "." . self::TABLE_NAME;
  }

  public function GetList($arOrder = array(), $arFilter = array(), $arOptions = array())
  {
    global $USER;
    $userID = $USER->GetID();
    $admin = $USER->IsAdmin();

    // Base query
    if ($admin) {
      $baseQuery = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl";
    } else {
      $baseQuery = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
                     WHERE msl.user_id = '$userID'
                     UNION
                     SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
                     INNER JOIN " . self::SCHEMA . ".reviewer r ON msl.id = r.ms_list_id
                     WHERE r.reviewer_id = '$userID'
                     AND r.stage_id = msl.stage_id";
    }

    // Add filters
    if (!empty($arFilter)) {
      if (!$admin) {
        $baseQuery = "SELECT * FROM ($baseQuery) as filtered_msl WHERE ";
      } else {
        $baseQuery .= " WHERE ";
      }

      $whereClauses = array();
      foreach ($arFilter as $field => $value) {
        $whereClauses[] = "$field = '$value'";
      }
      $baseQuery .= implode(' AND ', $whereClauses);
    }

    // Get total count before adding ORDER BY, LIMIT and OFFSET
    $totalQuery = "SELECT COUNT(*) as total FROM ($baseQuery) as total_query";
    $totalRes = $this->db->Query($totalQuery);
    $total = $totalRes->Fetch();

    // Add ORDER BY
    if (!empty($arOrder)) {
      $baseQuery .= " ORDER BY ";
      if (!$admin && !empty($arFilter)) {
        foreach ($arOrder as $field => $direction) {
          $baseQuery .= "$field $direction, ";
        }
      } else {
        foreach ($arOrder as $field => $direction) {
          $baseQuery .= "msl.$field $direction, ";
        }
      }
      $baseQuery = rtrim($baseQuery, ', ');
    }

    // Add LIMIT and OFFSET
    if (!empty($arOptions['limit'])) {
      $baseQuery .= " LIMIT " . intval($arOptions['limit']);
    }
    if (!empty($arOptions['offset'])) {
      $baseQuery .= " OFFSET " . intval($arOptions['offset']);
    }

    // Execute final query
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
    return true;
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
