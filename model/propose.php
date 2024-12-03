<?
class Propose extends CDBResult
{
    private $db;

    const SCHEMA = 's2config';
    const TABLE_NAME = 'list_propose';
    const ID_FIELD = 'id';

    private $QUERY_TABLE;

    public function __construct()
    {
        global $DB;
        $this->db = $DB;
        $this->QUERY_TABLE = self::SCHEMA . "." . self::TABLE_NAME;    }
    
   public function GetList($arOrder = array(), $arFilter = array())
   {
       $query = "SELECT * FROM " . $this->QUERY_TABLE;
       
       if (!empty($arFilter)) {
           $whereClauses = array();
           foreach ($arFilter as $field => $value) {
               $whereClauses[] = "$field = '$value'";
           }
           $query .= " WHERE " . implode(' AND ', $whereClauses);
       }
       
       if (!empty($arOrder)) {
           $query .= " ORDER BY ";
           foreach ($arOrder as $field => $direction) {
               $query .= "$field $direction, ";
           }
           $query = rtrim($query, ', ');
       }
       
       $dbRes = $this->db->Query($query);
       $arResult = array();
       while ($arRes = $dbRes->Fetch()) {
           $arResult[] = $arRes;
       }
       return $arResult;
   }

    public function GetById($id)
    {
        $dbRes = $this->db->Query("SELECT * FROM " . $this->QUERY_TABLE . " WHERE " . self::ID_FIELD . " = '$id'");
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
