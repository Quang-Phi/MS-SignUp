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

  public function GetList($arOrder = array(), $arFilter = array())
  {
    global $USER;
    $userID = $USER->GetID();
    $admin = $USER->IsAdmin();
    if ($admin) {
      $query = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl";
    } else {
      $query = "SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
                     WHERE msl.user_id = '$userID'
                     UNION
                     SELECT msl.* FROM " . $this->QUERY_TABLE . " msl
                     INNER JOIN " . self::SCHEMA . ".reviewer r ON msl.id = r.ms_list_id
                     WHERE r.reviewer_id = '$userID'
                     AND r.stage_id = msl.stage_id";
    }

    // Thêm điều kiện filter nếu có
    if (!empty($arFilter)) {
      if (!$admin) {
        $query = "SELECT * FROM ($query) as filtered_msl WHERE ";
      } else {
        $query .= " WHERE ";
      }

      $whereClauses = array();
      foreach ($arFilter as $field => $value) {
        $whereClauses[] = "$field = '$value'";
      }
      $query .= implode(' AND ', $whereClauses);
    }

    // Thêm order nếu có
    if (!empty($arOrder)) {
      if (!$admin && !empty($arFilter)) {
        $query .= " ORDER BY ";
        foreach ($arOrder as $field => $direction) {
          $query .= "$field $direction, ";
        }
      } else {
        $query .= " ORDER BY ";
        foreach ($arOrder as $field => $direction) {
          $arRes['reviewers'] = $this->GetReviewers($arRes['id']);
          $query .= "msl.$field $direction, ";
        }
      }
      $query = rtrim($query, ', ');
    }

    $dbRes = $this->db->Query($query);
    $arResult = array();
    while ($arRes = $dbRes->Fetch()) {
      $arRes['reviewers'] = $this->GetReviewers($arRes['id']);
      $arResult[] = $arRes;
    }
    return $arResult;
  }

  //   private function GetReviewers($msListId)
  //   {
  //       $query = "SELECT rs.reviewer_id, rs.stage_id, s.require_kpi
  //                 FROM " . self::SCHEMA . ".reviewer_stage rs
  //                 LEFT JOIN " . self::SCHEMA . ".stage s ON rs.stage_id = s.stage_id
  //                 WHERE rs.ms_list_id = '" . intval($msListId) . "'
  //                 ORDER BY rs.stage_id ASC";

  //       $dbRes = $this->db->Query($query);
  //       $reviewers = array();

  //       while ($reviewer = $dbRes->Fetch()) {
  //           $reviewers[] = array(
  //               'reviewer_id' => intval($reviewer['reviewer_id']),
  //               'stage_id' => intval($reviewer['stage_id']),
  //               'require_kpi' => (bool)$reviewer['require_kpi']
  //           );
  //       }

  //       return $reviewers;
  //   }


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
    // $query = "SELECT COUNT(*) as count
    //           FROM " . self::SCHEMA . ".kpi
    //           WHERE ms_list_id = '" . intval($msListId) . "'
    //           AND user_id = '" . intval($userId) . "'
    //           AND stage_id = '" . intval($stageId) . "'";
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
