<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ODBC Database Adapter Class
 */
class DBOdbcDriver extends DBComponent
{

    var $dbdriver = 'odbc';

    // the character used to excape - not necessary for ODBC
    var $_escape_char = '';

    // clause and character used for LIKE escape sequences
    var $_like_escape_str = " {escape '%s'} ";
    var $_like_escape_chr = '!';

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = "SELECT COUNT(*) AS ";
    var $_random_keyword;

    public function __construct($params)
    {
        parent::__construct($params);

        $this->_random_keyword = ' RND('.time().')'; // database specific random keyword
    }

    /**
     * Non-persistent database connection
     * @return resource
     */
    public function connect()
    {
        return @odbc_connect($this->hostname, $this->username, $this->password);
    }

    /**
     * Persistent database connection
     * @return resource
     */
    public function pconnect()
    {
        return @odbc_pconnect($this->hostname, $this->username, $this->password);
    }

    /**
     * Reconnect
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     * @return void
     */
    public function reconnect()
    {
        // not implemented in odbc
    }

    /**
     * Select the database
     * @return resource
     */
    public function SelectDb($dataName = '')
    {
        // Not needed for ODBC
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
    protected function _version()
    {
        return "SELECT version() AS ver";
    }

    /**
     * Get version
     * @return string
     */
    public function getVersion()
    {
        if ($this->db_debug) {
            return $this->displayError('db_unsuported_feature');
        }
        return '';
    }

    /**
     * Execute the query
     * @param string $sql an SQL query
     * @return resource
     */
    protected function _execute($sql)
    {
        $sql = $this->_prepQuery($sql);
        return @odbc_exec($this->conn_id, $sql);
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

        return odbc_autocommit($this->conn_id, false);
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

        $ret = odbc_commit($this->conn_id);
        odbc_autocommit($this->conn_id, true);
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

        $ret = odbc_rollback($this->conn_id);
        odbc_autocommit($this->conn_id, true);
        return $ret;
    }

    /**
     * Escape String
     * @param string
     * @param bool $str whether or not the string will be used in a LIKE condition
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

        // ODBC doesn't require escaping
        $str = StringHelper::removeInvisibleCharacters($str);

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
        $result = $this->getResultInstance();
        if ($result) {
            return @odbc_num_rows($result);
        }
        return 0;
    }

    /**
     * Insert ID
     * @return integer
     */
    function getInsertId()
    {
        if ($this->db_debug) {
            return $this->displayError('db_unsuported_feature');
        }
        return 0;
    }

    /**
     * "Count All" query
     * Generates a platform-specific query string that counts all records in
     * the specified database
     * @param string
     * @return string
     */
    public function countAll($table = '')
    {
        if ($table == '') {
            return 0;
        }

        $query = $this->query($this->_count_string.$this->_protectIdentifiers('numrows')." FROM ".$this->_protectIdentifiers($table, true, false, false));

        if (!$query) {
            return 0;
        }

        $row = $query->row();
        if (empty($row)) {
            return 0;
        }
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
        return false; //"SHOW COLUMNS FROM ".$table;
    }

    /**
     * Field data query
     * @param string $table the table name
     * @return object
     */
    protected function _fieldData($table)
    {
        return false; //"SELECT TOP 1 FROM ".$table;
    }

    /**
     * The error message string
     * @return string
     */
    protected function _getErrorMessage()
    {
        return odbc_errormsg($this->conn_id);
    }

    /**
     * The error message number
     * @return integer
     */
    protected function _getErrorNumber()
    {
        return odbc_error($this->conn_id);
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

        return ' '.implode(', ', $tables).' ';
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
     * Generates a platform-specific truncate string from the supplied data
     * If the database does not support the truncate() command
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
        // Does ODBC doesn't use the LIMIT clause?
        return $sql;
    }

    /**
     * Close DB Connection
     * @param resource
     * @return void
     */
    protected function _close($conn_id)
    {
        @odbc_close($conn_id);
    }
}