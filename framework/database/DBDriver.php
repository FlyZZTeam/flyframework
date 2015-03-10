<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Database Driver Class
 *
 * This is the platform-independent base DB implementation class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 */
class DBDriver
{

    var $username;
    var $password;
    var $hostname;
    var $database;
    var $dbdriver = 'mysql';
    var $dbprefix = '';
    var $char_set = 'utf8';
    var $dbcollat = 'utf8_general_ci';
    var $autoinit = true; // Whether to automatically initialize the DB
    var $swap_pre = '';
    var $port = '';
    var $pconnect = false;
    var $conn_id = false;
    var $result_id = false;
    var $db_debug = false;
    var $benchmark = 0;
    var $query_count = 0;
    var $bind_marker = '?';
    var $save_queries = true;
    var $queries = array();
    var $query_times = array();
    var $data_cache = array();
    var $trans_enabled = true;
    var $trans_strict = true;
    var $_trans_depth = 0;
    var $_trans_status = true; // Used with transactions to determine if a rollback should occur
    var $cache_on = false;
    var $cachedir = '';
    var $cache_autodel = false;
    var $CACHE; // The cache class object

    // Private variables
    protected $_protect_identifiers = true;
    var $_reserved_identifiers = array('*'); // Identifiers that should NOT be escaped
    protected $_query_options = array();

    // These are use with Oracle
    var $stmt_id;
    var $curs_id;
    var $limit_used;

    /**
     * Constructor.  Accepts one parameter containing the database
     * connection settings.
     *
     * @param array
     */
    public function __construct($params)
    {
        if (is_array($params)) {
            foreach ($params as $key => $val) {
                $this->$key = $val;
            }
        }

        Fly::log('debug', 'Database Driver Class Initialized');
    }

    /**
     * Initialize Database Settings
     *
     * @param mixed
     * @return void
     */
    public function initialize()
    {
        // If an existing connection resource is available
        // there is no need to connect and select the database
        if (is_resource($this->conn_id) || is_object($this->conn_id)) {
            return true;
        }

        // ----------------------------------------------------------------

        // Connect to the database and set the connection ID
        $this->conn_id = ($this->pconnect == false) ? $this->connect() : $this->pconnect();

        // No connection resource?  Throw an error
        if (!$this->conn_id) {
            Fly::log('error', 'Unable to connect to the database');

            if ($this->db_debug) {
                $this->displayError('db_unable_to_connect');
            }
            return false;
        }

        // ----------------------------------------------------------------

        // Select the DB... assuming a database name is specified in the config file
        if ($this->database != '') {
            if (!$this->selectDb()) {
                Fly::log('error', 'Unable to select database: '.$this->database);

                if ($this->db_debug) {
                    $this->displayError('db_unable_to_select', $this->database);
                }
                return false;
            } else {
                // We've selected the DB. Now we set the character set
                if (!$this->setCharset($this->char_set, $this->dbcollat)) {
                    return false;
                }

                return true;
            }
        }

        return true;
    }

    /**
     * Return Driver Instance
     *
     * @return mixed
     */
    public function getDriverInstance()
    {
        return $this->conn_id;
    }

    /**
     * Return Result Instance
     *
     * @return mixed
     */
    public function getResultInstance()
    {
        return $this->result_id;
    }

    /**
     * Set client character set
     *
     * @param string
     * @param string
     * @return resource
     */
    public function setCharset($charset, $collation)
    {
        if (!$this->_setCharset($this->char_set, $this->dbcollat)) {
            Fly::log('error', 'Unable to set database connection charset: '.$this->char_set);

            if ($this->db_debug) {
                $this->displayError('db_unable_to_set_charset', $this->char_set);
            }

            return false;
        }

        return true;
    }

    /**
     * The name of the platform in use (mysql, mssql, etc...)
     *
     * @return string
     */
    public function getPlatform()
    {
        return $this->dbdriver;
    }

    /**
     * Database Version Number.  Returns a string containing the
     * version of the database being used
     *
     * @return string
     */
    public function getVersion()
    {
        if (false === ($sql = $this->_version())) {
            if ($this->db_debug) {
                return $this->displayError('db_unsupported_function');
            }
            return false;
        }

        // Some DBs have functions that return the version, and don't run special
        // SQL queries per se. In these instances, just return the result.
        $driver_version_exceptions = array('oci8', 'sqlite', 'cubrid');

        if (in_array($this->dbdriver, $driver_version_exceptions)) {
            return $sql;
        } else {
            $query = $this->query($sql);
            return $query->row('ver');
        }
    }

    /**
     * Return Available Drivers
     *
     * @return array|bool|string
     */
    public function getAvailableDrivers()
    {
        if ($this->db_debug) {
            return $this->displayError('db_unsupported_function');
        }
        return false;
    }

    /**
     * Set Database connection attribute.
     *
     * @param $attribute
     * @param $value
     */
    public function setAttribute($attribute, $value)
    {
        if ($this->db_debug) {
            $this->displayError('db_unsupported_function');
        }
    }

    /**
     * Get Database connection attribute.
     *
     * @param $attribute
     * @param $value
     */
    public function getAttribute($attribute)
    {
        if ($this->db_debug) {
            $this->displayError('db_unsupported_function');
        }
    }

    /**
     * Execute the query
     *
     * Accepts an SQL string as input and returns a result object upon
     * successful execution of a "read" type query.  Returns boolean TRUE
     * upon successful execution of a "write" type query. Returns boolean
     * FALSE upon failure, and if the $db_debug variable is set to TRUE
     * will raise an error.
     *
     * @param string $sql An SQL query string
     * @param array $binds An array of binding data
     * @return mixed
     */
    public function query($sql, $binds = false, $return_object = true)
    {
        if ($sql == '') {
            if ($this->db_debug) {
                Fly::log('error', 'Invalid query: '.$sql);
                return $this->displayError('db_invalid_query');
            }
            return false;
        }

        // Verify table prefix and replace if necessary
        if (($this->dbprefix != '' && $this->swap_pre != '') && ($this->dbprefix != $this->swap_pre)) {
            $sql = preg_replace("/(\W)".$this->swap_pre."(\S+?)/", "\\1".$this->dbprefix."\\2", $sql);
        }

        // Compile binds if needed
        if ($binds !== false) {
            $sql = $this->compileBinds($sql, $binds);
        }

        // Is query caching enabled?  If the query is a "read type"
        // we will load the caching class and return the previously
        // cached query if it exists
        if ($this->cache_on == true && stristr($sql, 'SELECT')) {
            if ($this->_initCache()) {
                //$this->load_rdriver();
                if (false !== ($cache = $this->CACHE->read($sql))) {
                    return $cache;
                }
            }
        }

        // Save the  query for debugging
        if ($this->save_queries == true) {
            $this->queries[] = $sql;
        }

        // Start the Query Timer
        $time_start = list($sm, $ss) = explode(' ', microtime());

        // Run the Query
        if (false === ($this->result_id = $this->_realQuery($sql))) {
            if ($this->save_queries == true) {
                $this->query_times[] = 0;
            }

            // This will trigger a rollback if transactions are being used
            $this->_trans_status = false;

            if ($this->db_debug) {
                // grab the error number and message now, as we might run some
                // additional queries before displaying the error
                $error_no = $this->getErrorNumber();
                $error_msg = $this->getErrorMessage();

                // We call this function in order to roll-back queries
                // if transactions are enabled.  If we don't call this here
                // the error message will trigger an exit, causing the
                // transactions to remain in limbo.
                $this->transactionComplete();

                // Log and display errors
                Fly::log('error', 'Query error: '.$error_msg);
                return $this->displayError(
                    array(
                        'Error Number: '.$error_no,
                        $error_msg,
                        $sql
                    )
                );
            }

            return false;
        }

        // Stop and aggregate the query time results
        $time_end = list($em, $es) = explode(' ', microtime());
        $this->benchmark += ($em + $es) - ($sm + $ss);

        if ($this->save_queries == true) {
            $this->query_times[] = ($em + $es) - ($sm + $ss);
        }

        // Increment the query counter
        $this->query_count++;

        // Was the query a "write" type?
        // If so we'll simply return true
        if ($this->isWriteType($sql) === true) {
            // If caching is enabled we'll auto-cleanup any
            // existing files related to this particular URI
            if ($this->cache_on == true && $this->cache_autodel == true && $this->_initCache()) {
                $this->CACHE->delete();
            }

            return true;
        }

        // Return TRUE if we don't need to create a result object
        // Currently only the Oracle driver uses this when stored
        // procedures are used
        if ($return_object !== true) {
            return true;
        }

        // Load and instantiate the result driver

        $driver = $this->loadResultDriver();
        $RES = new $driver();
        $RES->conn_id = $this->conn_id;
        $RES->result_id = $this->result_id;

        if ($this->dbdriver == 'oci8') {
            $RES->stmt_id = $this->stmt_id;
            $RES->curs_id = null;
            $RES->limit_used = $this->limit_used;
            $this->stmt_id = false;
        }

        // oci8 vars must be set before calling this
        //$RES->num_rows	= $RES->getRowsCount();

        // Is query caching enabled?  If so, we'll serialize the
        // result object and save it to a cache file.
        if ($this->cache_on == true AND $this->_initCache()) {
            // We'll create a new instance of the result object
            // only without the platform specific driver since
            // we can't use it with cached data (the query result
            // resource ID won't be any good once we've cached the
            // result object, so we'll have to compile the data
            // and save it)
            $CR = new DBResult();
            //$CR->num_rows		= $RES->getRowsCount();
            $CR->result_object = $RES->resultObject();
            $CR->result_array = $RES->resultArray();

            // Reset these since cached objects can not utilize resource IDs.
            $CR->conn_id = null;
            $CR->result_id = null;

            $this->CACHE->write($sql, $CR);
        }

        return $RES;
    }

    /**
     * Returns the table name
     * @param $name
     * @return mixed
     */
    public function getTableName($name)
    {
        return $this->protectIdentifiers($name, true);
    }

    /**
     * Load the result drivers
     *
     * @return string the name of the result class
     */
    public function loadResultDriver()
    {
        $driver = 'DB'.ucfirst($this->dbdriver).'Result';
        return $driver;
    }

    /**
     * real Query
     *
     * @param string $sql the sql query
     * @return mixed
     */
    protected function _realQuery($sql)
    {
        if (!$this->conn_id) {
            $this->initialize();
        }

        return $this->_execute($sql);
    }

    /**
     * This is a simplified version of the query() function.  Internally
     * we only use it when running transaction commands since they do
     * not require all the features of the main query() function.
     *
     * @param string the sql query
     * @return mixed
     */
    public function exec($sql)
    {
        return $this->_realQuery($sql);
    }

    /**
     * Disable Transactions
     * This permits transactions to be disabled at run-time.
     *
     * @return void
     */
    public function setTransactionOff()
    {
        $this->trans_enabled = false;
    }

    /**
     * Enable/disable Transaction Strict Mode
     * When strict mode is enabled, if you are running multiple groups of
     * transactions, if one group fails all groups will be rolled back.
     * If strict mode is disabled, each group is treated autonomously, meaning
     * a failure of one group will not affect any others
     *
     * @return void
     */
    public function setTransactionStrict($mode = true)
    {
        $this->trans_strict = is_bool($mode) ? $mode : true;
    }

    /**
     * Start Transaction
     *
     * @return void
     */
    public function transactionStart($test_mode = false)
    {
        if (!$this->trans_enabled) {
            return false;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 0) {
            $this->_trans_depth += 1;
            return;
        }

        $this->transactionBegin($test_mode);
    }

    /**
     * Complete Transaction
     *
     * @return bool
     */
    public function transactionComplete()
    {
        if (!$this->trans_enabled) {
            return false;
        }

        // When transactions are nested we only begin/commit/rollback the outermost ones
        if ($this->_trans_depth > 1) {
            $this->_trans_depth -= 1;
            return true;
        }

        // The query() function will set this flag to FALSE in the event that a query failed
        if ($this->_trans_status === false) {
            $this->transactionRollBack();

            // If we are NOT running in strict mode, we will reset
            // the _trans_status flag so that subsequent groups of transactions
            // will be permitted.
            if ($this->trans_strict === false) {
                $this->_trans_status = true;
            }

            Fly::log('debug', 'DB Transaction Failure');
            return false;
        }

        $this->transactionCommit();
        return true;
    }

    /**
     * Lets you retrieve the transaction flag to determine if it has failed
     *
     * @return bool
     */
    public function getTransactionStatus()
    {
        return $this->_trans_status;
    }

    /**
     * Compile Bindings
     *
     * @param string $sql the sql statement
     * @param array $binds an array of bind data
     * @return string
     */
    public function compileBinds($sql, $binds)
    {
        if (strpos($sql, $this->bind_marker) === false) {
            return $sql;
        }

        if (!is_array($binds)) {
            $binds = array($binds);
        }

        // Get the sql segments around the bind markers
        $segments = explode($this->bind_marker, $sql);

        // The count of bind should be 1 less then the count of segments
        // If there are more bind arguments trim it down
        if (count($binds) >= count($segments)) {
            $binds = array_slice($binds, 0, count($segments) - 1);
        }

        // Construct the binded query
        $result = $segments[0];
        $i = 0;
        foreach ($binds as $bind) {
            $result .= $this->escape($bind);
            $result .= $segments[++$i];
        }

        return $result;
    }

    /**
     * Determines if a query is a "write" type.
     *
     * @param string $sql An SQL query string
     * @return boolean
     */
    public function isWriteType($sql)
    {
        if (!preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql)) {
            return false;
        }
        return true;
    }

    /**
     * Calculate the aggregate query elapsed time
     *
     * @param integer $decimals The number of decimal places
     * @return integer
     */
    public function elapsedTime($decimals = 6)
    {
        return number_format($this->benchmark, $decimals);
    }

    /**
     * Returns the total number of queries
     *
     * @access public
     * @return integer
     */
    public function getTotalQueries()
    {
        return $this->query_count;
    }

    /**
     * Returns the last query that was executed
     *
     * @access public
     * @return void
     */
    public function getLastQuery()
    {
        return end($this->queries);
    }

    /**
     * "Smart" Escape String
     *
     * Escapes data based on type
     * Sets boolean and null types
     *
     * @access public
     * @param string
     * @return mixed
     */
    public function escape($str)
    {
        if (is_string($str)) {
            $str = "'".$this->escapeString($str)."'";
        } else if (is_bool($str)) {
            $str = ($str === false) ? 0 : 1;
        } else if (is_null($str)) {
            $str = 'NULL';
        }
        return $str;
    }

    /**
     * Escape LIKE String
     *
     * Calls the individual driver for platform
     * specific escaping for LIKE conditions
     *
     * @access public
     * @param string
     * @return mixed
     */
    public function escapeLikeString($str)
    {
        return $this->escapeString($str, true);
    }

    /**
     * Primary
     *
     * Retrieves the primary key.  It assumes that the row in the first
     * position is the primary key
     *
     * @access public
     * @param string $table the table name
     * @return string
     */
    public function primary($table = '')
    {
        $fields = $this->fetchFields($table);

        if (!is_array($fields)) {
            return false;
        }
        return current($fields);
    }

    /**
     * Returns an array of table names
     *
     * @access public
     * @return array
     */
    public function fetchTables($constrain_by_prefix = false)
    {
        // Is there a cached result?
        if (isset($this->data_cache['table_names'])) {
            return $this->data_cache['table_names'];
        }

        if (false === ($sql = $this->_listTables($constrain_by_prefix))) {
            if ($this->db_debug) {
                return $this->displayError('db_unsupported_function');
            }
            return false;
        }

        $retval = array();
        $query = $this->query($sql);
        if ($query && $query->getRowsCount() > 0) {
            foreach ($query->resultArray() as $row) {
                if (isset($row['TABLE_NAME'])) {
                    $retval[] = $row['TABLE_NAME'];
                } else {
                    $retval[] = array_shift($row);
                }
            }
        }
        $this->data_cache['table_names'] = $retval;
        return $this->data_cache['table_names'];
    }

    /**
     * Determine if a particular table exists
     * @access public
     * @return boolean
     */
    public function isTableExists($table_name)
    {
        return (!in_array($this->_protectIdentifiers($table_name, true, false, false), $this->fetchTables())) ? false : true;
    }

    /**
     * Fetch MySQL Field Names
     *
     * @access public
     * @param string $table the table name
     * @return array
     */
    public function fetchFields($table = '')
    {
        // Is there a cached result?
        if (isset($this->data_cache['field_names'][$table])) {
            return $this->data_cache['field_names'][$table];
        }

        if ($table == '') {
            if ($this->db_debug) {
                return $this->displayError('db_field_param_missing');
            }
            return false;
        }

        if (false === ($sql = $this->_listColumns($table))) {
            if ($this->db_debug) {
                return $this->displayError('db_unsupported_function');
            }
            return false;
        }

        $query = $this->query($sql);

        $retval = array();
        foreach ($query->resultArray() as $row) {
            if (isset($row['COLUMN_NAME'])) {
                $retval[] = $row['COLUMN_NAME'];
            } else {
                $retval[] = current($row);
            }
        }

        $this->data_cache['field_names'][$table] = $retval;
        return $this->data_cache['field_names'][$table];
    }

    /**
     * Determine if a particular field exists
     * @param string
     * @param string
     * @return boolean
     */
    public function isFieldExists($field_name, $table_name)
    {
        return (!in_array($field_name, $this->fetchFields($table_name))) ? false : true;
    }

    /**
     * Returns an object with field data
     * @param string $table the table name
     * @return object
     */
    public function getFieldData($table = '')
    {
        if ($table == '') {
            if ($this->db_debug) {
                return $this->displayError('db_field_param_missing');
            }
            return false;
        }

        $query = $this->query($this->_fieldData($this->_protectIdentifiers($table, true, null, false)));
        if (!$query) {
            return array();
        }
        return $query->getFieldData();
    }

    /**
     * Generate an insert string
     * @param string $table the table upon which the query will be performed
     * @param array $data an associative array data of key/values
     * @return string
     */
    public function insertString($table, $data)
    {
        $fields = array();
        $values = array();

        foreach ($data as $key => $val) {
            $fields[] = $this->_escapeIdentifiers($key);
            $values[] = $this->escape($val);
        }

        return $this->_insert($this->_protectIdentifiers($table, true, null, false), $fields, $values);
    }

    /**
     * Generate an update string
     * @param string $table the table upon which the query will be performed
     * @param array $data an associative array data of key/values
     * @param mixed $where the "where" statement
     * @return string
     */
    public function updateString($table, $data, $where)
    {
        if ($where == '') {
            return false;
        }

        $fields = array();
        foreach ($data as $key => $val) {
            $fields[$this->_protectIdentifiers($key)] = $this->escape($val);
        }

        if (!is_array($where)) {
            $dest = array($where);
        } else {
            $dest = array();
            foreach ($where as $key => $val) {
                $prefix = (count($dest) == 0) ? '' : ' AND ';

                if ($val !== '') {
                    if (!$this->_hasOperator($key)) {
                        $key .= ' =';
                    }

                    $val = ' '.$this->escape($val);
                }

                $dest[] = $prefix.$key.$val;
            }
        }

        return $this->_update($this->_protectIdentifiers($table, true, null, false), $fields, $dest);
    }

    /**
     * Tests whether the string has an SQL operator
     * @param string
     * @return bool
     */
    protected function _hasOperator($str)
    {
        $str = trim($str);
        if (!preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str)) {
            return false;
        }

        return true;
    }

    /**
     * Enables a native PHP function to be run, using a platform agnostic wrapper.
     * @param string $function the function name
     * @return mixed
     */
    public function callFunction($function)
    {
        $driver = ($this->dbdriver == 'postgre') ? 'pg_' : $this->dbdriver.'_';

        if (false === strpos($driver, $function)) {
            $function = $driver.$function;
        }

        if (!function_exists($function)) {
            if ($this->db_debug) {
                return $this->displayError('db_unsupported_function');
            }
            return false;
        } else {
            $args = (func_num_args() > 1) ? array_splice(func_get_args(), 1) : null;
            if (is_null($args)) {
                return call_user_func($function);
            } else {
                return call_user_func_array($function, $args);
            }
        }
    }

    /**
     * Set Cache Directory Path
     * @param string $path the path to the cache directory
     * @return void
     */
    public function setCachePath($path = '')
    {
        $this->cachedir = $path;
    }

    /**
     * Enable Query Caching
     * @return boolean
     */
    public function cacheOn()
    {
        $this->cache_on = true;
        return true;
    }

    /**
     * Disable Query Caching
     * @return boolean
     */
    public function cacheOff()
    {
        $this->cache_on = false;
        return false;
    }

    /**
     * Delete the cache files associated with a particular URI
     * @return boolean
     */
    public function deleteCache($segment_one = '', $segment_two = '')
    {
        if (!$this->_initCache()) {
            return false;
        }
        return $this->CACHE->delete($segment_one, $segment_two);
    }

    /**
     * Delete All cache files
     * @return void
     */
    public function deleteAllCache()
    {
        if (!$this->_initCache()) {
            return false;
        }

        return $this->CACHE->deleteAll();
    }

    /**
     * Initialize the Cache Class
     * @return boolean
     */
    protected function _initCache()
    {
        if (is_object($this->CACHE) && class_exists('DBCache')) {
            return true;
        }
        $this->CACHE = new DBCache($this); // pass db object to support multiple db connections and returned db objects
        return true;
    }

    /**
     * Close DB Connection
     * @return void
     */
    public function close()
    {
        if (is_resource($this->conn_id) OR is_object($this->conn_id)) {
            $this->_close($this->conn_id);
        }
        $this->conn_id = false;
    }

    /**
     * Display an error message
     * @param string $error the error message
     * @param string $swap an "swap" values
     * @param boolean $native whether to localize the message
     */
    public function displayError($error = '', $swap = '', $native = false)
    {
        $heading = Fly::t('db', 'db_error_heading');

        if ($native == true) {
            $message = $error;
        } else {
            $message = (!is_array($error)) ? array(str_replace('%s', $swap, Fly::t('db', $error))) : $error;
        }
        $message = implode("\n", (!is_array($message)) ? array($message) : $message);
        throw new FlyException($message);
    }

    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @param string
     * @return string
     */
    public function escapeIdentifiers($item)
    {
        return $this->_escapeIdentifiers($item);
    }

    /**
     * Protect Identifiers
     *
     * This function adds backticks if appropriate based on db type
     *
     * @param mixed the item to escape
     * @return mixed the item with backticks
     */
    public function protectIdentifiers($item, $prefix_single = false, $protect_identifiers = null, $field_exists = true)
    {
        return $this->_protectIdentifiers($item, $prefix_single, $protect_identifiers, $field_exists);
    }

    /**
     * Protect Identifiers
     *
     * This function is used extensively by the Active Record class, and by
     * a couple functions in this class.
     * It takes a column or table name (optionally with an alias) and inserts
     * the table prefix onto it.  Some logic is necessary in order to deal with
     * column names that include the path.  Consider a query like this:
     *
     * SELECT * FROM hostname.database.table.column AS c FROM hostname.database.table
     *
     * Or a query with aliasing:
     *
     * SELECT m.member_id, m.member_name FROM members AS m
     *
     * Since the column name can include up to four segments (host, DB, table, column)
     * or also have an alias prefix, we need to do a bit of work to figure this out and
     * insert the table prefix (if it exists) in the proper position, and escape only
     * the correct identifiers.
     *
     * @param string
     * @param bool
     * @param mixed
     * @param bool
     * @return string
     */
    protected function _protectIdentifiers($item, $prefix_single = false, $protect_identifiers = null, $field_exists = true)
    {
        if (!is_bool($protect_identifiers)) {
            $protect_identifiers = $this->_protect_identifiers;
        }

        if (is_array($item)) {
            $escaped_array = array();

            foreach ($item as $k => $v) {
                $escaped_array[$this->_protectIdentifiers($k)] = $this->_protectIdentifiers($v);
            }

            return $escaped_array;
        }

        // Convert tabs or multiple spaces into single spaces
        $item = preg_replace('/[\t ]+/', ' ', $item);

        // If the item has an alias declaration we remove it and set it aside.
        // Basically we remove everything to the right of the first space
        if (strpos($item, ' ') !== false) {
            $alias = strstr($item, ' ');
            $item = substr($item, 0, -strlen($alias));
        } else {
            $alias = '';
        }

        // This is basically a bug fix for queries that use MAX, MIN, etc.
        // If a parenthesis is found we know that we do not need to
        // escape the data or add a prefix.  There's probably a more graceful
        // way to deal with this, but I'm not thinking of it -- Rick
        if (strpos($item, '(') !== false) {
            return $item.$alias;
        }

        // Break the string apart if it contains periods, then insert the table prefix
        // in the correct location, assuming the period doesn't indicate that we're dealing
        // with an alias. While we're at it, we will escape the components
        if (strpos($item, '.') !== false) {
            $parts = explode('.', $item);

            // Does the first segment of the exploded item match
            // one of the aliases previously identified?  If so,
            // we have nothing more to do other than escape the item
            if (in_array($parts[0], $this->ar_aliased_tables)) {
                if ($protect_identifiers === true) {
                    foreach ($parts as $key => $val) {
                        if (!in_array($val, $this->_reserved_identifiers)) {
                            $parts[$key] = $this->_escapeIdentifiers($val);
                        }
                    }

                    $item = implode('.', $parts);
                }
                return $item.$alias;
            }

            // Is there a table prefix defined in the config file?  If not, no need to do anything
            if ($this->dbprefix != '') {
                // We now add the table prefix based on some logic.
                // Do we have 4 segments (hostname.database.table.column)?
                // If so, we add the table prefix to the column name in the 3rd segment.
                if (isset($parts[3])) {
                    $i = 2;
                }
                // Do we have 3 segments (database.table.column)?
                // If so, we add the table prefix to the column name in 2nd position
                else if (isset($parts[2])) {
                    $i = 1;
                }
                // Do we have 2 segments (table.column)?
                // If so, we add the table prefix to the column name in 1st segment
                else {
                    $i = 0;
                }

                // This flag is set when the supplied $item does not contain a field name.
                // This can happen when this function is being called from a JOIN.
                if ($field_exists == false) {
                    $i++;
                }

                // Verify table prefix and replace if necessary
                if ($this->swap_pre != '' && strncmp($parts[$i], $this->swap_pre, strlen($this->swap_pre)) === 0) {
                    $parts[$i] = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $parts[$i]);
                }

                // We only add the table prefix if it does not already exist
                if (substr($parts[$i], 0, strlen($this->dbprefix)) != $this->dbprefix) {
                    $parts[$i] = $this->dbprefix.$parts[$i];
                }

                // Put the parts back together
                $item = implode('.', $parts);
            }

            if ($protect_identifiers === true) {
                $item = $this->_escapeIdentifiers($item);
            }
            return $item.$alias;
        }

        // Is there a table prefix?  If not, no need to insert it
        if ($this->dbprefix != '') {
            // Verify table prefix and replace if necessary
            if ($this->swap_pre != '' && strncmp($item, $this->swap_pre, strlen($this->swap_pre)) === 0) {
                $item = preg_replace("/^".$this->swap_pre."(\S+?)/", $this->dbprefix."\\1", $item);
            }

            // Do we prefix an item with no segments?
            if ($prefix_single == true && substr($item, 0, strlen($this->dbprefix)) != $this->dbprefix) {
                $item = $this->dbprefix.$item;
            }
        }

        if ($protect_identifiers === true && !in_array($item, $this->_reserved_identifiers)) {
            $item = $this->_escapeIdentifiers($item);
        }

        return $item.$alias;
    }

    /**
     * Dummy method that allows Active Record class to be disabled
     *
     * This function is used extensively by every db driver.
     *
     * @return void
     */
    protected function _resetSelect()
    {
    }

    /**
     * Return Error Message.
     *
     * @return mixed
     */
    public function getErrorMessage($returnResultError = true)
    {
        return $this->_getErrorMessage();
    }

    /**
     * Return Error Number.
     *
     * @return mixed
     */
    public function getErrorNumber($returnResultError = true)
    {
        return $this->_getErrorNumber();
    }

    /**
     * Return Error Info(number,message,and so on).
     *
     * @return array
     */
    public function getErrorInfo($returnResultError = true)
    {
        $info = array(
            '00000',
            $this->_getErrorNumber(),
            $this->_getErrorMessage(),
        );
        return $info;
    }

    /**
     * Set query options
     *
     * @return array
     */
    public function setQueryOptions($opts)
    {
        $this->_query_options = $opts;
    }

    /**
     * Ret set query options
     *
     * @return array
     */
    public function resetQueryOptions()
    {
        $this->_query_options = array();
    }

    /**
     * Set Protect Identifiers
     * Some database need set it false
     *
     * @param $val
     */
    public function setProtectIdentifiers($val)
    {
        $this->_protect_identifiers = $val;
    }

    /**
     * Return DBUtility
     *
     * @return mixed
     */
    public function utils()
    {
        return DBUtility::shareInstance($this);
    }
}
