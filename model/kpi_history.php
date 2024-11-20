<?
class kpiHistory extends CDBResult
{
    private $db;

    const SCHEMA = 's2config';
    const TABLE_NAME = 'kpi_history';
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
        try {
            $sql = "SELECT * FROM " . $this->QUERY_TABLE . " WHERE 1=1";

            if (!empty($arFilter)) {
                foreach ($arFilter as $field => $value) {
                    if ($value !== null && $value !== '') {
                        $field = $this->db->ForSQL($field);
                        if (is_array($value)) {
                            $values = array_map(array($this->db, 'ForSQL'), $value);
                            $sql .= " AND {$field} IN ('" . implode("','", $values) . "')";
                        } else {
                            $value = $this->db->ForSQL($value);
                            $sql .= " AND {$field} = '$value'";
                        }
                    }
                }
            }

            if (!empty($arOrder)) {
                $orderParts = array();
                foreach ($arOrder as $field => $direction) {
                    $field = $this->db->ForSQL(preg_replace('/[^a-zA-Z0-9_]/', '', $field));
                    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
                    $orderParts[] = "{$field} {$direction}";
                }
                if (!empty($orderParts)) {
                    $sql .= " ORDER BY " . implode(', ', $orderParts);
                }
            }

            if (isset($arOptions['limit']) && isset($arOptions['offset'])) {
                $limit = intval($arOptions['limit']);
                $offset = intval($arOptions['offset']);
                if ($limit > 0) {
                    $sql .= " LIMIT $offset, $limit";
                }
            }

            $dbResult = $this->db->Query($sql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
            if (!$dbResult) {
                throw new Exception("Query failed: " . $this->db->GetErrorMessage());
            }

            $arResult = array();
            while ($row = $dbResult->Fetch()) {
                $arResult[] = $row;
            }
            return $arResult;
        } catch (Exception $e) {
            error_log("Error in GetList: " . $e->getMessage() . "\nSQL: " . $sql);
            return false;
        }
    }

    public function GetCount($arFilter = array())
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM " . $this->QUERY_TABLE . " WHERE 1=1";

            if (!empty($arFilter)) {
                foreach ($arFilter as $field => $value) {
                    if ($value !== null && $value !== '') {
                        $field = $this->db->ForSQL($field);
                        if (is_array($value)) {
                            $values = array_map(array($this->db, 'ForSQL'), $value);
                            $sql .= " AND {$field} IN ('" . implode("','", $values) . "')";
                        } else {
                            $value = $this->db->ForSQL($value);
                            $sql .= " AND {$field} = '$value'";
                        }
                    }
                }
            }

            $dbResult = $this->db->Query($sql, false, "File: " . __FILE__ . "<br>Line: " . __LINE__);
            if (!$dbResult) {
                throw new Exception("Query failed: " . $this->db->GetErrorMessage());
            }

            if ($row = $dbResult->Fetch()) {
                return intval($row['total']);
            }
            return 0;
        } catch (Exception $e) {
            error_log("Error in GetCount: " . $e->getMessage() . "\nSQL: " . $sql);
            return 0;
        }
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
