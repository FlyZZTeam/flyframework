<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * MySQL Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 */
class DBMysqlDriver extends DBComponent
{

    var $dbdriver = 'mysql';

    // The character used for escaping
    var $_escape_char = '`';

    // clause and character used for LIKE escape sequences - not used in MySQL
    var $_like_escape_str = '';
    var $_like_escape_chr = '';

    /**
     * Whether to use the MySQL "delete hack" which allows the number
     * of affected rows to be shown. Uses a preg_replace when enabled,
     * adding a bit more processing to all queries.
     */
    var $delete_hack = true;

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = 'SELECT COUNT(*) AS ';
    var $_random_keyword = ' RAND()'; // database specific random keyword

    // whether SET NAMES must be used to set the character set
    var $use_set_names;

    /**
     * Non-persistent database connection
     * @return resource
     */
    public function connect()
    {
        if ($this->port != '') {
            $this->hostname .= ':'.$this->port;
        }

        return @mysql_connect($this->hostname, $this->username, $this->password, true);
    }

    /**
     * Persistent database connection
     * @return resource
     */
    public function pconnect()
    {
        if ($this->port != '') {
            $this->hostname .= ':'.$this->port;
        }

        return @mysql_pconnect($this->hostname, $this->username, $this->password);
    }

    /**
     * Reconnect
     *
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     * @return void
     */
    public function reconnect()
    {
        if (mysql_ping($this->conn_id) === false) {
            $this->conn_id = false;
        }
    }

    /**
     * Select the database
     * @return resource
     */
    public function selectDb($dbName = '')
    {
        if (!is_string($dbName) || $dbName === '') {
            $dbName = $this->database;
        } else {
            $this->database = $dbName;
        }
        return @mysql_select_db($dbName, $this->conn_id);
    }

    /**
     * Set client character set
     * @param string
     * @param string
     * @return resource
     */
    public function setCharset($charset, $collation)
    {
        if (!isset($this->use_set_names)) {
            // mysql_set_charset() requires PHP >= 5.2.3 and MySQL >= 5.0.7, use SET NAMES as fallback
            $this->use_set_names = (version_compare(PHP_VERSION, '5.2.3', '>=') && version_compare(mysql_get_server_info(), '5.0.7', '>=')) ? false : true;
        }

        if ($this->use_set_names === true) {
            return @mysql_query("SET NAMES '".$this->escapeString($charset)."' COLLATE '".$this->escapeString($collation)."'", $this->conn_id);
        } else {
            return @mysql_set_charset($charset, $this->conn_id);
        }
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
    function _execute($sql)
    {
        $sql = $this->_prepQuery($sql);
        return @mysql_query($sql, $this->conn_id);
    }

    /**
     * Prep the query
     *
     * If needed, each database adapter can prep the query string
     * @param string $sql an SQL query
     * @return string
     */
    private function _prepQuery($sql)
    {
        // "DELETE FROM TABLE" returns 0 affected rows This hack modifies
        // the query so that it returns the number of affected rows
        if ($this->delete_hack === true) {
            if (preg_match('/^\s*DELETE\s+FROM\s+(\S+)\s*$/i', $sql)) {
                $sql = preg_replace("/^\s*DELETE\s+FROM\s+(\S+)\s*$/", "DELETE FROM \\1 WHERE 1=1", $sql);
            }
        }

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

        $this->_realQuery('SET AUTOCOMMIT=0');
        $this->_realQuery('START TRANSACTION'); // can also be BEGIN or BEGIN WORK
        return true;
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

        $this->_realQuery('COMMIT');
        $this->_realQuery('SET AUTOCOMMIT=1');
        return true;
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

        $this->_realQuery('ROLLBACK');
        $this->_realQuery('SET AUTOCOMMIT=1');
        return true;
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

        if (function_exists('mysql_real_escape_string') AND is_resource($this->conn_id)) {
            $str = mysql_real_escape_string($str, $this->conn_id);
        } else if (function_exists('mysql_escape_string')) {
            $str = mysql_escape_string($str);
        } else {
            $str = addslashes($str);
        }

        // escape LIKE condition wildcards
        if ($like === true) {
            $str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
        }

        return $str;
    }

    /**
     * Affected Rows
     * @return integer
     */
    public function affectedRows()
    {
        return @mysql_affected_rows($this->conn_id);
    }

    /**
     * Insert ID
     * @return integer
     */
    public function getInsertId()
    {
        return @mysql_insert_id($this->conn_id);
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

        $query = $this->query($this->_count_string.$this->_protectIdentifiers('numrows')." FROM ".$this->_protectIdentifiers($table, true, null, false));

        if ($query->getRowsCount() == 0) {
            return 0;
        }

        $row = $query->row();
        $this->_resetSelect();
        return (int)$row->numrows;
    }

    /**
     * List table query
     * Generates a platform-specific query string so that the table names can be fetched
     * @param boolean
     * @return string
     */
    protected function _listTables($prefix_limit = false)
    {
        $sql = "SHOW TABLES FROM ".$this->_escape_char.$this->database.$this->_escape_char;

        if ($prefix_limit !== false AND $this->dbprefix != '') {
            $sql .= " LIKE '".$this->escapeLikeString($this->dbprefix)."%'";
        }

        return $sql;
    }

    /**
     * Show column query
     * Generates a platform-specific query string so that the column names can be fetched
     * @param string $table the table name
     * @return string
     */
    protected function _listColumns($table = '')
    {
        return "SHOW COLUMNS FROM ".$this->_protectIdentifiers($table, true, null, false);
    }

    /**
     * Field data query
     * Generates a platform-specific query so that the column data can be retrieved
     * @param string $table the table name
     * @return object
     */
    function _fieldData($table)
    {
        return "DESCRIBE ".$table;
    }

    /**
     * The error message string
     * @return string
     */
    protected function _getErrorMessage()
    {
        return mysql_error($this->conn_id);
    }

    /**
     * The error message number
     * @return integer
     */
    protected function _getErrorNumber()
    {
        return mysql_errno($this->conn_id);
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

        return '('.implode(', ', $tables).')';
    }

    /**
     * Insert statement
     * Generates a platform-specific insert string from the supplied data
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
     * Replace statement
     * Generates a platform-specific replace string from the supplied data
     * @param string $table the table name
     * @param array $key the insert keys
     * @param array $values the insert values
     * @return string
     */
    protected function _replace($table, $keys, $values)
    {
        return "REPLACE INTO ".$table." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
    }

    /**
     * Insert_batch statement
     * Generates a platform-specific insert string from the supplied data
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
     * Generates a platform-specific update string from the supplied data
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
            $valstr[] = $key.' = '.$val;
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
     * Generates a platform-specific batch update string from the supplied data
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
     * Generates a platform-specific truncate string from the supplied data
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     * @param string $table the table name
     * @return string
     */
    protected function _truncate($table)
    {
        return "TRUNCATE ".$table;
    }

    /**
     * Delete statement
     * Generates a platform-specific delete string from the supplied data
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
     * Generates a platform-specific LIMIT clause
     * @param string $sql the sql query string
     * @param integer $limit the number of rows to limit the query to
     * @param integer $offset the offset value
     * @return string
     */
    protected function _limit($sql, $limit, $offset)
    {
        if ($offset == 0) {
            $offset = '';
        } else {
            $offset .= ", ";
        }

        return $sql."LIMIT ".$offset.$limit;
    }

    /**
     * Close DB Connection
     * @param resource
     * @return void
     */
    protected function _close($conn_id)
    {
        @mysql_close($conn_id);
    }
}