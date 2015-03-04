<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * oci8 Database Adapter Class
 * This is a modification of the DB_driver class to
 * permit access to oracle databases
 */
class DBOci8Driver extends DBComponent
{

    var $dbdriver = 'oci8';

    // The character used for excaping
    var $_escape_char = '"';

    // clause and character used for LIKE escape sequences
    var $_like_escape_str = " escape '%s' ";
    var $_like_escape_chr = '!';

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = "SELECT COUNT(1) AS ";
    var $_random_keyword = ' ASC'; // not currently supported

    // Set "auto commit" by default
    var $_commit = OCI_COMMIT_ON_SUCCESS;

    // need to track statement id and cursor id
    var $stmt_id;
    var $curs_id;

    // if we use a limit, we will add a field that will
    // throw off num_fields later
    var $limit_used;

    /**
     * Non-persistent database connection
     * @return resource
     */
    public function connect()
    {
        return @oci_connect($this->username, $this->password, $this->hostname, $this->char_set);
    }

    /**
     * Persistent database connection
     * @return resource
     */
    public function pconnect()
    {
        return @oci_pconnect($this->username, $this->password, $this->hostname, $this->char_set);
    }

    /**
     * Reconnect
     * Keep / reestablish the db connection if no queries have been
     * sent for a length of time exceeding the server's idle timeout
     * @return void
     */
    public function reconnect()
    {
        // not implemented in oracle
        return;
    }

    /**
     * Select the database
     * @return resource
     */
    public function selectDb()
    {
        // Not in Oracle - schemas are actually usernames
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
        return oci_server_version($this->conn_id);
    }

    /**
     * Execute the query
     * @param string $sql an SQL query
     * @return resource
     */
    protected function _execute($sql)
    {
        // oracle must parse the query before it is run. All of the actions with
        // the query are based on the statement id returned by ociparse
        $this->stmt_id = false;
        $this->_setStmtId($sql);
        oci_set_prefetch($this->stmt_id, 1000);
        return @oci_execute($this->stmt_id, $this->_commit);
    }

    /**
     * Generate a statement ID
     * @param string $sql an SQL query
     * @return void
     */
    private function _setStmtId($sql)
    {
        if (!is_resource($this->stmt_id)) {
            $this->stmt_id = oci_parse($this->conn_id, $this->_prepQuery($sql));
        }
    }

    /**
     * Prep the query
     * If needed, each database adapter can prep the query string
     * @param string $sql an SQL query
     * @return string
     */
    private function _prepQuery($sql)
    {
        return $sql;
    }

    /**
     * getCursor.  Returns a cursor from the datbase
     * @return object
     */
    public function getCursor()
    {
        $this->curs_id = oci_new_cursor($this->conn_id);
        return $this->curs_id;
    }

    /**
     * Stored Procedure.  Executes a stored procedure
     * @param $package package stored procedure is in
     * @param $procedure stored procedure to execute
     * @param array $params array of parameters
     * @return array
     *
     * params array keys
     *
     * KEY      OPTIONAL    NOTES
     * name        no        the name of the parameter should be in :<param_name> format
     * value    no        the value of the parameter.  If this is an OUT or IN OUT parameter,
     *                    this should be a reference to a variable
     * type        yes        the type of the parameter
     * length    yes        the max size of the parameter
     */
    public function storedProcedure($package, $procedure, $params)
    {
        if ($package == '' OR $procedure == '' OR !is_array($params)) {
            if ($this->db_debug) {
                Fly::log('error', 'Invalid query: '.$package.'.'.$procedure);
                return $this->displayError('db_invalid_query');
            }
            return false;
        }

        // build the query string
        $sql = "begin $package.$procedure(";

        $have_cursor = false;
        foreach ($params as $param) {
            $sql .= $param['name'].",";

            if (array_key_exists('type', $param) && ($param['type'] === OCI_B_CURSOR)) {
                $have_cursor = true;
            }
        }
        $sql = trim($sql, ",")."); end;";

        $this->stmt_id = false;
        $this->_setStmtId($sql);
        $this->_bindParams($params);
        $this->query($sql, false, $have_cursor);
    }

    /**
     * Bind parameters
     * @return none
     */
    private function _bindParams($params)
    {
        if (!is_array($params) OR !is_resource($this->stmt_id)) {
            return;
        }

        foreach ($params as $param) {
            foreach (array('name', 'value', 'type', 'length') as $val) {
                if (!isset($param[$val])) {
                    $param[$val] = '';
                }
            }

            oci_bind_by_name($this->stmt_id, $param['name'], $param['value'], $param['length'], $param['type']);
        }
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

        $this->_commit = OCI_DEFAULT;
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

        $ret = oci_commit($this->conn_id);
        $this->_commit = OCI_COMMIT_ON_SUCCESS;
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

        $ret = oci_rollback($this->conn_id);
        $this->_commit = OCI_COMMIT_ON_SUCCESS;
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
        return @oci_num_rows($this->stmt_id);
    }

    /**
     * Insert ID
     * @return integer
     */
    public function getInsertId()
    {
        // not supported in oracle
        return $this->displayError('db_unsupported_function');
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

        $query = $this->query($this->_count_string." numrows "." FROM ".$this->_protectIdentifiers($table, true, false, false));

        if ($query == false) {
            return 0;
        }

        $row = $query->row();
        $this->_resetSelect();
        return (int)$row->NUMROWS;
    }

    /**
     * Show table query
     * Generates a platform-specific query string so that the table names can be fetched
     * @param boolean
     * @return string
     */
    protected function _listTables($prefix_limit = false)
    {
        $sql = "SELECT TABLE_NAME FROM ALL_TABLES";

        if ($prefix_limit !== false AND $this->dbprefix != '') {
            $sql .= " WHERE TABLE_NAME LIKE '".$this->escapeLikeString($this->dbprefix)."%' ".sprintf($this->_like_escape_str, $this->_like_escape_chr);
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
        return "SELECT COLUMN_NAME FROM all_tab_columns WHERE table_name = '".$this->_protectIdentifiers($table, true, false)."'";
    }

    /**
     * Field data query
     * Generates a platform-specific query so that the column data can be retrieved
     * @param string $table the table name
     * @return object
     */
    protected function _fieldData($table)
    {
        return "SELECT * FROM ".$table." where rownum = 1";
    }

    /**
     * The error message string
     * @return string
     */
    protected function _getErrorMessage()
    {
        // If the error was during connection, no conn_id should be passed
        $error = is_resource($this->conn_id) ? oci_error($this->conn_id) : oci_error();
        return $error['message'];
    }

    /**
     * The error message number
     * @return integer
     */
    protected function _getErrorNumber()
    {
        // Same as _error_message()
        $error = is_resource($this->conn_id) ? oci_error($this->conn_id) : oci_error();
        return $error['code'];
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
     * Insert_batch statement
     * Generates a platform-specific insert string from the supplied data
     * @param string $table the table name
     * @param array $keys the insert keys
     * @param array $values the insert values
     * @return string
     */
    protected function _insertBatch($table, $keys, $values)
    {
        $keys = implode(', ', $keys);
        $sql = "INSERT ALL\n";

        for ($i = 0, $c = count($values); $i < $c; $i++) {
            $sql .= '	INTO '.$table.' ('.$keys.') VALUES '.$values[$i]."\n";
        }

        $sql .= 'SELECT * FROM dual';

        return $sql;
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
        return "TRUNCATE TABLE ".$table;
    }

    /**
     * Delete statement
     * Generates a platform-specific delete string from the supplied data
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
     * Generates a platform-specific LIMIT clause
     * @param string $sql the sql query string
     * @param integer $limit the number of rows to limit the query to
     * @param integer $offset the offset value
     * @return string
     */
    protected function _limit($sql, $limit, $offset)
    {
        $limit = $offset + $limit;
        $newsql = "SELECT * FROM (select inner_query.*, rownum rnum FROM ($sql) inner_query WHERE rownum < $limit)";

        if ($offset != 0) {
            $newsql .= " WHERE rnum >= $offset";
        }

        // remember that we used limits
        $this->limit_used = true;

        return $newsql;
    }

    /**
     * Close DB Connection
     * @param resource
     * @return void
     */
    protected function _close($conn_id)
    {
        @oci_close($conn_id);
    }
}
