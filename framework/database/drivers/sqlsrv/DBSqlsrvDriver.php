<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * SQLSRV Database Adapter Class
 */
class  DBSqlsrvDriver extends DBComponent
{

    var $dbdriver = 'sqlsrv';

    // The character used for escaping
    var $_escape_char = '';

    // clause and character used for LIKE escape sequences
    var $_like_escape_str = " ESCAPE '%s' ";
    var $_like_escape_chr = '!';

    /**
     * The syntax to count rows is slightly different across different
     * database engines, so this string appears in each driver and is
     * used for the count_all() and count_all_results() functions.
     */
    var $_count_string = "SELECT COUNT(*) AS ";
    var $_random_keyword = ' ASC'; // not currently supported

    /**
     * Non-persistent database connection
     * @return resource
     */
    public function connect($pooling = false)
    {
        // Check for a UTF-8 charset being passed as CI's default 'utf8'.
        $character_set = (0 === strcasecmp('utf8', $this->char_set)) ? 'UTF-8' : $this->char_set;

        $connection = array(
            'UID' => empty($this->username) ? '' : $this->username,
            'PWD' => empty($this->password) ? '' : $this->password,
            'Database' => $this->database,
            'ConnectionPooling' => $pooling ? 1 : 0,
            'CharacterSet' => $character_set,
            'ReturnDatesAsStrings' => 1
        );

        // If the username and password are both empty, assume this is a
        // 'Windows Authentication Mode' connection.
        if (empty($connection['UID']) && empty($connection['PWD'])) {
            unset($connection['UID'], $connection['PWD']);
        }

        return sqlsrv_connect($this->hostname, $connection);
    }

    /**
     * Persistent database connection
     * @return resource
     */
    public function pconnect()
    {
        $this->connect(true);
    }

    /**
     * Reconnect
     * @return void
     */
    public function reconnect()
    {
        // not implemented in MSSQL
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
        return $this->_execute('USE '.$this->database);
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
     * Execute the query
     * @param string $sql an SQL query
     * @return resource
     */
    protected function _execute($sql)
    {
        $sql = $this->_prepQuery($sql);
        /*array(
            'Scrollable'				=> SQLSRV_CURSOR_STATIC,
            'SendStreamParamsAtExec'	=> true
        )*/
        $res = sqlsrv_query($this->conn_id, $sql, null, $this->_query_options);
        $this->resetQueryOptions();
        return $res;
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

        return sqlsrv_begin_transaction($this->conn_id);
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

        return sqlsrv_commit($this->conn_id);
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

        return sqlsrv_rollback($this->conn_id);
    }

    /**
     * Escape String
     * @param string
     * @param bool $like whether or not the string will be used in a LIKE condition
     * @return string
     */
    public function escapeString($str, $like = false)
    {
        // Escape single quotes
        return str_replace("'", "''", $str);
    }

    /**
     * Affected Rows
     * @return integer
     */
    public function affectedRows()
    {
        return @sqlsrv_rows_affected($this->getResultInstance());
    }

    /**
     * Insert ID
     * Returns the last id created in the Identity column.
     * @return integer
     */
    public function getInsertId()
    {
        return $this->query('select @@IDENTITY as insert_id')->row('insert_id');
    }

    /**
     * Parse major version
     * Grabs the major version number from the
     * database server version string passed in.
     * @param string $version
     * @return int major version number
     */
    protected function _parseMajorVersion($version)
    {
        preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $ver_info);
        return $ver_info[1]; // return the major version b/c that's all we're interested in.
    }

    /**
     * Version number query string
     * @return string
     */
    protected function _version()
    {
        $info = sqlsrv_server_info($this->conn_id);
        return sprintf("select '%s' as ver", $info['SQLServerVersion']);
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
        if ($table == '')
            return '0';

        $query = $this->query("SELECT COUNT(*) AS numrows FROM ".$this->_protectIdentifiers($table, true, null, false));

        if ($query->getRowsCount() == 0) {
            return '0';
        }

        $row = $query->row();
        $this->_resetSelect();
        return $row->numrows;
    }

    /**
     * List table query
     * @param boolean
     * @return string
     */
    protected function _listTables($prefix_limit = false)
    {
        return "SELECT name FROM sysobjects WHERE type = 'U' ORDER BY name";
    }

    /**
     * List column query
     * @param string $table the table name
     * @return string
     */
    protected function _listColumns($table = '')
    {
        return "SELECT * FROM INFORMATION_SCHEMA.Columns WHERE TABLE_NAME = '".$this->_protectIdentifiers($table, true, true, false)."'";
    }

    /**
     * Field data query
     * @param string $table the table name
     * @return object
     */
    protected function _fieldData($table)
    {
        return "SELECT TOP 1 * FROM ".$this->_escapeTable($table);
    }

    /**
     * The error message string
     * @return string
     */
    protected function _getErrorMessage()
    {
        $error = array_shift(sqlsrv_errors());
        return !empty($error['message']) ? $error['message'] : null;
    }

    /**
     * The error message number
     * @return integer
     */
    protected function _getErrorNumber()
    {
        $error = array_shift(sqlsrv_errors());
        return isset($error['SQLSTATE']) ? $error['SQLSTATE'] : null;
    }

    /**
     * Escape Table Name
     * This function adds backticks if the table name has a period
     * in it. Some DBs will get cranky unless periods are escaped
     * @param string $table the table name
     * @return string
     */
    protected function _escapeTable($table)
    {
        return $table;
    }

    /**
     * Escape the SQL Identifiers
     * This function escapes column and table names
     * @param string
     * @return string
     */
    protected function _escapeIdentifiers($item)
    {
        return $item;
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
        return "INSERT INTO ".$this->_escapeTable($table)." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
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
        return "INSERT INTO ".$this->_escapeTable($table)." (".implode(', ', $keys).") VALUES ".implode(', ', $values);
    }

    /**
     * Update statement
     * @param string $table the table name
     * @param array $values the update data
     * @param array $where the where clause
     * @return string
     */
    protected function _update($table, $values, $where)
    {
        foreach ($values as $key => $val) {
            $valstr[] = $key." = ".$val;
        }

        return "UPDATE ".$this->_escapeTable($table)." SET ".implode(', ', $valstr)." WHERE ".implode(" ", $where);
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
        return "TRUNCATE TABLE ".$table;
    }

    /**
     * Delete statement
     * @param string $table the table name
     * @param array $where the where clause
     * @return string
     */
    protected function _delete($table, $where)
    {
        return "DELETE FROM ".$this->_escapeTable($table)." WHERE ".implode(" ", $where);
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
        $i = $limit + $offset;

        return preg_replace('/(^\SELECT (DISTINCT)?)/i', '\\1 TOP '.$i.' ', $sql);
    }

    /**
     * Close DB Connection
     * @param resource
     * @return void
     */
    protected function _close($conn_id)
    {
        @sqlsrv_close($conn_id);
    }
}