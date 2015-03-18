<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * PDO Database Adapter Class
 */
class DBPdoDriver extends DBComponent
{

    var $dbdriver = 'pdo';

    // the character used to excape - not necessary for PDO
    var $_escape_char = '';
    var $_like_escape_str;
    var $_like_escape_chr;
    var $affect_rows;

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = "SELECT COUNT(*) AS ";
    var $_random_keyword;

    var $options = array();

    public function __construct($params)
    {
        parent::__construct($params);

        // clause and character used for LIKE escape sequences
        if (strpos($this->hostname, 'mysql') !== false) {
            $this->_like_escape_str = '';
            $this->_like_escape_chr = '';
            $this->_escape_char = '`';

            //Prior to this version, the charset can't be set in the dsn
            if (Fly::isPhp('5.3.6')) {
                $this->hostname .= ";charset={$this->char_set}";
            }

            //Set the charset with the connection options
            $this->options['PDO::MYSQL_ATTR_INIT_COMMAND'] = "SET NAMES {$this->char_set}";
        } else if (strpos($this->hostname, 'odbc') !== false) {
            $this->_like_escape_str = " {escape '%s'} ";
            $this->_like_escape_chr = '!';
        } else {
            $this->_like_escape_str = " ESCAPE '%s' ";
            $this->_like_escape_chr = '!';
        }

        empty($this->database) OR $this->hostname .= ';dbname='.$this->database;

        $this->trans_enabled = false;

        $this->_random_keyword = ' RND('.time().')'; // database specific random keyword
    }

    /**
     * Non-persistent database connection
     * @return resource
     */
    public function connect()
    {
        $this->options['PDO::ATTR_ERRMODE'] = PDO::ERRMODE_SILENT;

        return new PDO($this->hostname, $this->username, $this->password, $this->options);
    }

    /**
     * Persistent database connection
     * @return resource
     */
    public function pconnect()
    {
        $this->options['PDO::ATTR_ERRMODE'] = PDO::ERRMODE_SILENT;
        $this->options['PDO::ATTR_PERSISTENT'] = true;

        return new PDO($this->hostname, $this->username, $this->password, $this->options);
    }

    /**
     * Reconnect
     * @return void
     */
    public function reconnect()
    {
        if ($this->db->db_debug) {
            return $this->db->display_error('db_unsuported_feature');
        }
        return false;
    }

    /**
     * Select the database
     * @return resource
     */
    public function selectDb($dbName = '')
    {
        // Not needed for PDO
        return true;
    }

    /**
     * Set client character set
     * @param string
     * @param string
     * @return resource
     */
    public function setCharset($charset, $collation)
    {
        // @todo - add support if needed
        return true;
    }

    /**
     * Version number query string
     * @return string
     */
    public function getVersion()
    {
        return $this->conn_id->getAttribute(PDO::ATTR_CLIENT_VERSION);
    }

    /**
     * Execute the query
     * @param string $sql an SQL query
     * @return object
     */
    protected function _execute($sql)
    {
        $sql = $this->_prepQuery($sql);

        $result_id = $this->conn_id->prepare($sql);

        if (is_object($result_id) && $result_id->execute()) {
            if (!is_numeric(stripos($sql, 'SELECT'))) {
                //$this->affect_rows = count($result_id->fetchAll());
                $this->affect_rows = $result_id->rowCount();
            }
        } else {
            $this->affect_rows = 0;
        }
        return $result_id;
    }

    /**
     * Prep the query
     * If needed, each database adapter can prep the query string
     * @param string $sql an SQL query
     * @return string
     */
    protected function _prepQuery($sql)
    {
        return $sql;
    }

    /**
     * Begin Transaction
     * @return bool
     */
    public function transactionBegin($test_mode = false)
    {
        if (!$this->trans_enabled) {
            return true;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return true;
        }

        // Reset the transaction failure flag.
        // If the $test_mode flag is set to TRUE transactions will be rolled back
        // even if the queries produce a successful result.
        $this->_trans_failure = (bool)($test_mode === true);

        return $this->conn_id->beginTransaction();
    }

    /**
     * Commit Transaction
     * @return bool
     */
    public function transactionCommit()
    {
        if (!$this->trans_enabled) {
            return true;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return true;
        }

        $ret = $this->conn->commit();
        return $ret;
    }

    /**
     * Rollback Transaction
     * @return bool
     */
    public function transactionRollBack()
    {
        if (!$this->trans_enabled) {
            return true;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            return true;
        }

        $ret = $this->conn_id->rollBack();
        return $ret;
    }

    /**
     * Escape String
     * @param string
     * @param bool $like whether or not the string will be used in a LIKE condition
     * @return string
     */
    public function escapeString($str, $like = false)
    {
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = $this->escapeString($val, $like);
            }

            return $str;
        }

        //Escape the string
        $str = $this->conn_id->quote($str);

        //If there are duplicated quotes, trim them away
        if (strpos($str, "'") === 0) {
            $str = substr($str, 1, -1);
        }

        // escape LIKE condition wildcards
        if ($like === true) {
            $str = str_replace(array(
                    '%',
                    '_',
                    $this->_like_escape_chr
                ),
                array(
                    $this->_like_escape_chr.'%',
                    $this->_like_escape_chr.'_',
                    $this->_like_escape_chr.$this->_like_escape_chr
                ),
                $str);
        }

        return $str;
    }

    /**
     * Affected Rows
     * @return integer
     */
    public function affectedRows()
    {
        return $this->affect_rows;
    }

    /**
     * Insert ID
     * @return integer
     */
    public function getInsertId($name = null)
    {
        //Convenience method for postgres insertid
        if (strpos($this->hostname, 'pgsql') !== false) {
            $v = $this->_version();

            $table = func_num_args() > 0 ? func_get_arg(0) : null;

            if ($table == null && $v >= '8.1') {
                $sql = 'SELECT LASTVAL() as ins_id';
            }
            $query = $this->query($sql);
            $row = $query->row();
            return $row->ins_id;
        } else {
            return $this->conn_id->lastInsertId($name);
        }
    }

    /**
     * "Count All" query
     * @param string
     * @return string
     */
    public function countAll($table = '')
    {
        if ($table == '') {
            return 0;
        }

        $query = $this->query($this->_count_string.$this->_protectIdentifiers('numrows')." FROM ".$this->_protectIdentifiers($table, true, null, false));

        if ($query->getRowsCount() == 0) {
            return 0;
        }

        $row = $query->row();
        $this->_resetSelect();
        return (int)$row->numrows;
    }

    /**
     * Show table query
     * @param boolean
     * @return string
     */
    protected function _listTables($prefix_limit = false)
    {
        $sql = "SHOW TABLES FROM `".$this->database."`";
        if ($prefix_limit !== false AND $this->dbprefix != '') {
            //$sql .= " LIKE '".$this->escape_like_str($this->dbprefix)."%' ".sprintf($this->_like_escape_str, $this->_like_escape_chr);
            return false; // not currently supported
        }

        return $sql;
    }

    /**
     * Show column query
     * @param string $table the table name
     * @return string
     */
    protected function _listColumns($table = '')
    {
        return "SHOW COLUMNS FROM ".$this->_protectIdentifiers($table, true, null, false);
    }

    /**
     * Field data query
     * @param string $table the table name
     * @return object
     */
    protected function _fieldData($table)
    {
        return "SELECT * FROM ".$table." LIMIT 1";
    }

    /**
     * Escape the SQL Identifiers
     * @param string
     * @return string
     */
    protected function _escapeIdentifiers($item)
    {
        if ($this->_escape_char == '') {
            return $item;
        }

        foreach ($this->_reserved_identifiers as $id) {
            if (strpos($item, '.'.$id) !== false) {
                $str = $this->_escape_char.str_replace('.', $this->_escape_char.'.', $item);

                // remove duplicates if the user already included the escape
                return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
            }
        }

        if (strpos($item, '.') !== false) {
            $str = $this->_escape_char.str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $item).$this->_escape_char;
        } else {
            $str = $this->_escape_char.$item.$this->_escape_char;
        }

        // remove duplicates if the user already included the escape
        return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
    }

    /**
     * From Tables
     * This function implicitly groups FROM tables so there is no confusion
     * about operator precedence in harmony with SQL standards
     * @param type
     * @return type
     */
    protected function _fromTables($tables)
    {
        if (!is_array($tables)) {
            $tables = array($tables);
        }

        return (count($tables) == 1) ? $tables[0] : '('.implode(', ', $tables).')';
    }

    /**
     * Insert statement
     * @param string $table the table name
     * @param array $keys the insert keys
     * @param array $values the insert values
     * @return string
     */
    protected function _insert($table, $keys, $values)
    {
        return "INSERT INTO ".$table." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
    }

    /**
     * Insert_batch statement
     * @param string $table the table name
     * @param array $table the insert keys
     * @param array $values the insert values
     * @return string
     */
    protected function _insertBatch($table, $keys, $values)
    {
        return "INSERT INTO ".$table." (".implode(', ', $keys).") VALUES ".implode(', ', $values);
    }

    /**
     * Update statement
     * @param string $table the table name
     * @param array $values the update data
     * @param array $where the where clause
     * @param array $orderby the orderby clause
     * @param array $limit the limit clause
     * @return string
     */
    protected function _update($table, $values, $where, $orderby = array(), $limit = false)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key." = ".$val;
        }

        $limit = (!$limit) ? '' : ' LIMIT '.$limit;

        $orderby = (count($orderby) >= 1) ? ' ORDER BY '.implode(", ", $orderby) : '';

        $sql = "UPDATE ".$table." SET ".implode(', ', $valstr);

        $sql .= ($where != '' AND count($where) >= 1) ? " WHERE ".implode(" ", $where) : '';

        $sql .= $orderby.$limit;

        return $sql;
    }

    /**
     * Update_Batch statement
     * @param string $table the table name
     * @param array $values the update data
     * @param array $where the where clause
     * @return string
     */
    protected function _updateBatch($table, $values, $index, $where = null)
    {
        $ids = array();
        $where = ($where != '' AND count($where) >= 1) ? implode(" ", $where).' AND ' : '';

        foreach ($values as $key => $val) {
            $ids[] = $val[$index];

            foreach (array_keys($val) as $field) {
                if ($field != $index) {
                    $final[$field][] = 'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
                }
            }
        }

        $sql = "UPDATE ".$table." SET ";
        $cases = '';

        foreach ($final as $k => $v) {
            $cases .= $k.' = CASE '."\n";
            foreach ($v as $row) {
                $cases .= $row."\n";
            }

            $cases .= 'ELSE '.$k.' END, ';
        }

        $sql .= substr($cases, 0, -2);

        $sql .= ' WHERE '.$where.$index.' IN ('.implode(',', $ids).')';

        return $sql;
    }

    /**
     * Truncate statement
     * This function maps to "DELETE FROM table"
     * @param string $table the table name
     * @return string
     */
    protected function _truncate($table)
    {
        return $this->_delete($table);
    }

    /**
     * Delete statement
     * @param string $table the table name
     * @param array $where the where clause
     * @param string $like the limit clause
     * @return string
     */
    protected function _delete($table, $where = array(), $like = array(), $limit = false)
    {
        $conditions = '';

        if (count($where) > 0 OR count($like) > 0) {
            $conditions = "\nWHERE ";
            $conditions .= implode("\n", $this->ar_where);

            if (count($where) > 0 && count($like) > 0) {
                $conditions .= " AND ";
            }
            $conditions .= implode("\n", $like);
        }

        $limit = (!$limit) ? '' : ' LIMIT '.$limit;

        return "DELETE FROM ".$table.$conditions.$limit;
    }

    /**
     * Limit string
     * @param string $sql the sql query string
     * @param integer $limit the number of rows to limit the query to
     * @param integer $offset the offset value
     * @return string
     */
    protected function _limit($sql, $limit, $offset)
    {
        if (strpos($this->hostname, 'cubrid') !== false || strpos($this->hostname, 'sqlite') !== false) {
            if ($offset == 0) {
                $offset = '';
            } else {
                $offset .= ", ";
            }

            return $sql."LIMIT ".$offset.$limit;
        } else {
            $sql .= "LIMIT ".$limit;

            if ($offset > 0) {
                $sql .= " OFFSET ".$offset;
            }

            return $sql;
        }
    }

    /**
     * Close DB Connection
     * @param resource
     * @return void
     */
    protected function _close($conn_id)
    {
        $this->conn_id = null;
    }

    /**
     * Return Available Drivers
     * @return array|bool|string
     */
    public function getAvailableDrivers()
    {
        return Pdo::getAvailableDrivers();
    }

    /**
     * Set Database connection attribute.
     * @param $attribute
     * @param $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->conn_id->setAttribute($attribute, $value);
    }

    /**
     * Get Database connection attribute.
     * @param $attribute
     * @param $value
     */
    public function getAttribute($attribute)
    {
        $this->conn_id->getAttribute($attribute);
    }

    /**
     * Return Error Message.
     * @return mixed
     */
    public function getErrorMessage($returnResultError = true)
    {
        $info = array();
        if ($returnResultError) {
            if ($this->result_id) {
                $info = $this->result_id->errorInfo();
            }
        } else {
            $info = $this->conn_id->errorInfo();
        }
        return isset($info[2]) ? $info[2] : '';
    }

    /**
     * Return Error Number.
     * @return mixed
     */
    public function getErrorNumber($returnResultError = true)
    {
        $code = '';
        if ($returnResultError) {
            if ($this->result_id) {
                $code = $this->result_id->errorCode();
            }
        } else {
            $code = $this->conn_id->errorCode();
        }
        return $code;
    }

    /**
     * Return Error Info(number,message,and so on).
     * @return array
     */
    public function getErrorInfo($returnResultError = true)
    {
        $info = array();
        if ($returnResultError) {
            if ($this->result_id) {
                $info = $this->result_id->errorInfo();
            }
        } else {
            $info = $this->conn_id->errorInfo();
        }
        return $info;
    }

    /**
     * Execute an SQL statement and return the number of affected rows
     * @param $sql
     * @return mixed
     */
    public function exec($sql)
    {
        return $this->conn_id->exec($sql);
    }
}