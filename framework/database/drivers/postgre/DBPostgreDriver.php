<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Postgre Database Adapter Class
 */
class DBPostgreDriver extends DBComponent
{

    var $dbdriver = 'postgre';

    var $_escape_char = '"';

    // clause and character used for LIKE escape sequences
    var $_like_escape_str = " ESCAPE '%s' ";
    var $_like_escape_chr = '!';

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = "SELECT COUNT(*) AS ";
    var $_random_keyword = ' RANDOM()'; // database specific random keyword

    /**
     * Connection String
     * @return string
     */
    protected function _connectString()
    {
        $components = array(
            'hostname' => 'host',
            'port' => 'port',
            'database' => 'dbname',
            'username' => 'user',
            'password' => 'password'
        );

        $connect_string = "";
        foreach ($components as $key => $val) {
            if (isset($this->$key) && $this->$key != '') {
                $connect_string .= " $val=".$this->$key;
            }
        }
        return trim($connect_string);
    }

    /**
     * Non-persistent database connection
     * @return resource
     */
    public function connect()
    {
        return @pg_connect($this->_connectString());
    }

    /**
     * Persistent database connection
     * @return resource
     */
    public function pconnect()
    {
        return @pg_pconnect($this->_connectString());
    }

    /**
     * Reconnect
     * @return void
     */
    public function reconnect()
    {
        if (pg_ping($this->conn_id) === false) {
            $this->conn_id = false;
        }
    }

    /**
     * Select the database
     * @return resource
     */
    public function selectDb($dbName = '')
    {
        // Not needed for Postgre so we'll return TRUE
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
    public function _version()
    {
        return "SELECT version() AS ver";
    }

    /**
     * Execute the query
     * @param string $sql an SQL query
     * @return resource
     */
    protected function _execute($sql)
    {
        $sql = $this->_prepQuery($sql);
        return @pg_query($this->conn_id, $sql);
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
        $this->_trans_failure = ($test_mode === true) ? true : false;

        return @pg_exec($this->conn_id, "begin");
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

        return @pg_exec($this->conn_id, "commit");
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

        return @pg_exec($this->conn_id, "rollback");
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

        $str = pg_escape_string($str);

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
        return @pg_affected_rows($this->result_id);
    }

    /**
     * Insert ID
     * @return integer
     */
    public function getInsertId()
    {
        $v = 0;
        if (function_exists('pg_version')) {
            $info = pg_version($this->conn_id);
            if (is_array($info) && isset($info['server'])) {
                $v = (double)$info['server'];
            }
        }

        $table = func_num_args() > 0 ? func_get_arg(0) : null;
        $column = func_num_args() > 1 ? func_get_arg(1) : null;

        if ($table == null && $v >= '8.1') {
            $sql = 'SELECT LASTVAL() as ins_id';
        } else if ($table != null && $column != null && $v >= '8.0') {
            $sql = sprintf("SELECT pg_get_serial_sequence('%s','%s') as seq", $table, $column);
            $query = $this->query($sql);
            $row = $query->row();
            $sql = sprintf("SELECT CURRVAL('%s') as ins_id", $row->seq);
        } else if ($table != null) {
            // seq_name passed in table parameter
            $sql = sprintf("SELECT CURRVAL('%s') as ins_id", $table);
        } else {
            return pg_last_oid($this->result_id);
        }
        $query = $this->query($sql);
        $row = $query->row();
        return $row->ins_id;
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
        $row = $query->row();
        $this->_resetSelect();
        if (empty($row)) {
            return 0;
        }
        return (int)$row->numrows;
    }

    /**
     * Show table query
     * @param boolean
     * @return string
     */
    protected function _listTables($prefix_limit = false)
    {
        $sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";

        if ($prefix_limit !== false AND $this->dbprefix != '') {
            $sql .= " AND table_name LIKE '".$this->escapeLikeString($this->dbprefix)."%' ".sprintf($this->_like_escape_str, $this->_like_escape_chr);
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

        return "SELECT column_name FROM information_schema.columns WHERE table_name ='".$this->_protectIdentifiers($table, true, false, false)."'";
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
     * The error message string
     * @return string
     */
    protected function _getErrorMessage()
    {
        return pg_last_error($this->conn_id);
    }

    /**
     * The error message number
     * @return integer
     */
    protected function _getErrorNumber()
    {
        return '';
    }

    /**
     * Escape the SQL Identifiers
     * This function escapes column and table names
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

        return implode(', ', $tables);
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
     * @param array $keys the insert keys
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
     * Truncate statement
     * @param string $table the table name
     * @return string
     */
    protected function _truncate($table)
    {
        return "TRUNCATE ".$table;
    }

    /**
     * Delete statement
     * @param string $table the table name
     * @param array $where the where clause
     * @param string $limit the limit clause
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
        $sql .= "LIMIT ".$limit;

        if ($offset > 0) {
            $sql .= " OFFSET ".$offset;
        }

        return $sql;
    }

    /**
     * Close DB Connection
     * @param resource
     * @return void
     */
    protected function _close($conn_id)
    {
        @pg_close($conn_id);
    }
}