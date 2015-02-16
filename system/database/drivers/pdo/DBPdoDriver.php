<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com
 * @since		Version 2.1.2
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * PDO Database Adapter Class
 *
 * Note: _DB is an extender class that the app controller
 * creates dynamically based on whether the active record
 * class is being used or not.
 *
 * @package		CodeIgniter
 * @subpackage	Drivers
 * @category	Database
 * @author		EllisLab Dev Team
 * @link		http://codeigniter.com/user_guide/database/
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
		if (strpos($this->hostname, 'mysql') !== FALSE) {
			$this->_like_escape_str = '';
			$this->_like_escape_chr = '';

			//Prior to this version, the charset can't be set in the dsn
			if(Fly::isPhp('5.3.6')) {
				$this->hostname .= ";charset={$this->char_set}";
			}

			//Set the charset with the connection options
			$this->options['PDO::MYSQL_ATTR_INIT_COMMAND'] = "SET NAMES {$this->char_set}";
		} else if (strpos($this->hostname, 'odbc') !== FALSE) {
			$this->_like_escape_str = " {escape '%s'} ";
			$this->_like_escape_chr = '!';
		} else {
			$this->_like_escape_str = " ESCAPE '%s' ";
			$this->_like_escape_chr = '!';
		}

		empty($this->database) OR $this->hostname .= ';dbname='.$this->database;

		$this->trans_enabled = FALSE;

		$this->_random_keyword = ' RND('.time().')'; // database specific random keyword
	}

	/**
	 * Non-persistent database connection
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	public function connect()
	{
		$this->options['PDO::ATTR_ERRMODE'] = PDO::ERRMODE_SILENT;

		return new PDO($this->hostname, $this->username, $this->password, $this->options);
	}

	/**
	 * Persistent database connection
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	public function pconnect()
	{
		$this->options['PDO::ATTR_ERRMODE'] = PDO::ERRMODE_SILENT;
		$this->options['PDO::ATTR_PERSISTENT'] = TRUE;
	
		return new PDO($this->hostname, $this->username, $this->password, $this->options);
	}

	/**
	 * Reconnect
	 *
	 * Keep / reestablish the db connection if no queries have been
	 * sent for a length of time exceeding the server's idle timeout
	 *
	 * @access	public
	 * @return	void
	 */
	public function reconnect()
	{
		if ($this->db->db_debug) {
			return $this->db->display_error('db_unsuported_feature');
		}
		return FALSE;
	}

	/**
	 * Select the database
	 *
	 * @access	private called by the base class
	 * @return	resource
	 */
	public function selectDb($dbName = '')
	{
		// Not needed for PDO
		return TRUE;
	}

	/**
	 * Set client character set
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	resource
	 */
	public function setCharset($charset, $collation)
	{
		// @todo - add support if needed
		return TRUE;
	}

	/**
	 * Version number query string
	 *
	 * @access	public
	 * @return	string
	 */
	public function getVersion()
	{
		return $this->conn_id->getAttribute(PDO::ATTR_CLIENT_VERSION);
	}

	/**
	 * Execute the query
	 *
	 * @access	private called by the base class
	 * @param	string	an SQL query
	 * @return	object
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
	 *
	 * If needed, each database adapter can prep the query string
	 *
	 * @access	private called by execute()
	 * @param	string	an SQL query
	 * @return	string
	 */
	protected function _prepQuery($sql)
	{
		return $sql;
	}

	/**
	 * Begin Transaction
	 *
	 * @access	public
	 * @return	bool
	 */
	public function transactionBegin($test_mode = FALSE)
	{
		if ( ! $this->trans_enabled) {
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0) {
			return TRUE;
		}

		// Reset the transaction failure flag.
		// If the $test_mode flag is set to TRUE transactions will be rolled back
		// even if the queries produce a successful result.
		$this->_trans_failure = (bool) ($test_mode === TRUE);

		return $this->conn_id->beginTransaction();
	}

	/**
	 * Commit Transaction
	 *
	 * @access	public
	 * @return	bool
	 */
	public function transactionCommit()
	{
		if ( ! $this->trans_enabled) {
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0) {
			return TRUE;
		}

		$ret = $this->conn->commit();
		return $ret;
	}

	/**
	 * Rollback Transaction
	 *
	 * @access	public
	 * @return	bool
	 */
	public function transactionRollBack()
	{
		if ( ! $this->trans_enabled) {
			return TRUE;
		}

		// When transactions are nested we only begin/commit/rollback the outermost ones
		if ($this->_trans_depth > 0) {
			return TRUE;
		}

		$ret = $this->conn_id->rollBack();
		return $ret;
	}

	/**
	 * Escape String
	 *
	 * @access	public
	 * @param	string
	 * @param	bool	whether or not the string will be used in a LIKE condition
	 * @return	string
	 */
	public function escapeString($str, $like = FALSE)
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
		if ($like === TRUE) {
			$str = str_replace(	array('%', '_', $this->_like_escape_chr),
								array($this->_like_escape_chr.'%', $this->_like_escape_chr.'_', $this->_like_escape_chr.$this->_like_escape_chr),
								$str);
		}

		return $str;
	}

	/**
	 * Affected Rows
	 *
	 * @access	public
	 * @return	integer
	 */
	public function affectedRows()
	{
		return $this->affect_rows;
	}

	/**
	 * Insert ID
	 * 
	 * @access	public
	 * @return	integer
	 */
	public function getInsertId($name=NULL)
	{
		//Convenience method for postgres insertid
		if (strpos($this->hostname, 'pgsql') !== FALSE) {
			$v = $this->_version();

			$table	= func_num_args() > 0 ? func_get_arg(0) : NULL;

			if ($table == NULL && $v >= '8.1') {
				$sql='SELECT LASTVAL() as ins_id';
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
	 *
	 * Generates a platform-specific query string that counts all records in
	 * the specified database
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function countAll($table = '')
	{
		if ($table == '') {
			return 0;
		}

		$query = $this->query($this->_count_string . $this->_protectIdentifiers('numrows') . " FROM " . $this->_protectIdentifiers($table, TRUE, NULL, FALSE));

		if ($query->getRowsCount() == 0) {
			return 0;
		}

		$row = $query->row();
		$this->_resetSelect();
		return (int) $row->numrows;
	}

	/**
	 * Show table query
	 *
	 * Generates a platform-specific query string so that the table names can be fetched
	 *
	 * @access	private
	 * @param	boolean
	 * @return	string
	 */
	protected function _listTables($prefix_limit = FALSE)
	{
		$sql = "SHOW TABLES FROM `".$this->database."`";
		if ($prefix_limit !== FALSE AND $this->dbprefix != '') {
			//$sql .= " LIKE '".$this->escape_like_str($this->dbprefix)."%' ".sprintf($this->_like_escape_str, $this->_like_escape_chr);
			return FALSE; // not currently supported
		}

		return $sql;
	}

	/**
	 * Show column query
	 *
	 * Generates a platform-specific query string so that the column names can be fetched
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	string
	 */
	protected function _listColumns($table = '')
	{
		return "SHOW COLUMNS FROM ".$this->_protectIdentifiers($table, TRUE, NULL, FALSE);
	}

	/**
	 * Field data query
	 *
	 * Generates a platform-specific query so that the column data can be retrieved
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	object
	 */
	protected function _fieldData($table)
	{
		return "SELECT * FROM ".$table." LIMIT 1";
	}

	/**
	 * Escape the SQL Identifiers
	 *
	 * This function escapes column and table names
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	protected function _escapeIdentifiers($item)
	{
		if ($this->_escape_char == '') {
			return $item;
		}

		foreach ($this->_reserved_identifiers as $id) {
			if (strpos($item, '.'.$id) !== FALSE) {
				$str = $this->_escape_char. str_replace('.', $this->_escape_char.'.', $item);

				// remove duplicates if the user already included the escape
				return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
			}
		}

		if (strpos($item, '.') !== FALSE) {
			$str = $this->_escape_char.str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $item).$this->_escape_char;
			
		} else {
			$str = $this->_escape_char.$item.$this->_escape_char;
		}

		// remove duplicates if the user already included the escape
		return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
	}

	/**
	 * From Tables
	 *
	 * This function implicitly groups FROM tables so there is no confusion
	 * about operator precedence in harmony with SQL standards
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	protected function _fromTables($tables)
	{
		if ( ! is_array($tables)) {
			$tables = array($tables);
		}

		return (count($tables) == 1) ? $tables[0] : '('.implode(', ', $tables).')';
	}

	/**
	 * Insert statement
	 *
	 * Generates a platform-specific insert string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the insert keys
	 * @param	array	the insert values
	 * @return	string
	 */
	protected function _insert($table, $keys, $values)
	{
		return "INSERT INTO ".$table." (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
	}

	/**
	 * Insert_batch statement
	 *
	 * Generates a platform-specific insert string from the supplied data
	 *
	 * @access  public
	 * @param   string  the table name
	 * @param   array   the insert keys
	 * @param   array   the insert values
	 * @return  string
	 */
	protected function _insertBatch($table, $keys, $values)
	{
		return "INSERT INTO ".$table." (".implode(', ', $keys).") VALUES ".implode(', ', $values);
	}

	/**
	 * Update statement
	 *
	 * Generates a platform-specific update string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the update data
	 * @param	array	the where clause
	 * @param	array	the orderby clause
	 * @param	array	the limit clause
	 * @return	string
	 */
	protected function _update($table, $values, $where, $orderby = array(), $limit = FALSE)
	{
		foreach ($values as $key => $val) {
			$valstr[] = $key." = ".$val;
		}

		$limit = ( ! $limit) ? '' : ' LIMIT '.$limit;

		$orderby = (count($orderby) >= 1)?' ORDER BY '.implode(", ", $orderby):'';

		$sql = "UPDATE ".$table." SET ".implode(', ', $valstr);

		$sql .= ($where != '' AND count($where) >=1) ? " WHERE ".implode(" ", $where) : '';

		$sql .= $orderby.$limit;

		return $sql;
	}

	/**
	 * Update_Batch statement
	 *
	 * Generates a platform-specific batch update string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the update data
	 * @param	array	the where clause
	 * @return	string
	 */
	protected function _updateBatch($table, $values, $index, $where = NULL)
	{
		$ids = array();
		$where = ($where != '' AND count($where) >=1) ? implode(" ", $where).' AND ' : '';

		foreach ($values as $key => $val) {
			$ids[] = $val[$index];

			foreach (array_keys($val) as $field) {
				if ($field != $index) {
					$final[$field][] =  'WHEN '.$index.' = '.$val[$index].' THEN '.$val[$field];
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
	 *
	 * Generates a platform-specific truncate string from the supplied data
	 * If the database does not support the truncate() command
	 * This function maps to "DELETE FROM table"
	 *
	 * @access	public
	 * @param	string	the table name
	 * @return	string
	 */
	protected function _truncate($table)
	{
		return $this->_delete($table);
	}

	/**
	 * Delete statement
	 *
	 * Generates a platform-specific delete string from the supplied data
	 *
	 * @access	public
	 * @param	string	the table name
	 * @param	array	the where clause
	 * @param	string	the limit clause
	 * @return	string
	 */
	protected function _delete($table, $where = array(), $like = array(), $limit = FALSE)
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

		$limit = ( ! $limit) ? '' : ' LIMIT '.$limit;

		return "DELETE FROM ".$table.$conditions.$limit;
	}

	/**
	 * Limit string
	 *
	 * Generates a platform-specific LIMIT clause
	 *
	 * @access	public
	 * @param	string	the sql query string
	 * @param	integer	the number of rows to limit the query to
	 * @param	integer	the offset value
	 * @return	string
	 */
	protected function _limit($sql, $limit, $offset)
	{
		if (strpos($this->hostname, 'cubrid') !== FALSE || strpos($this->hostname, 'sqlite') !== FALSE) {
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
	 *
	 * @access	public
	 * @param	resource
	 * @return	void
	 */
	protected function _close($conn_id)
	{
		$this->conn_id = null;
	}

    /**
     * Return Available Drivers
     *
     * @return array|bool|string
     */
    public function getAvailableDrivers()
    {
        return Pdo::getAvailableDrivers();
    }

    /**
     * Set Database connection attribute.
     *
     * @param $attribute
     * @param $value
     */
    public function setAttribute($attribute, $value)
    {
        $this->conn_id->setAttribute($attribute, $value);
    }

    /**
     * Get Database connection attribute.
     *
     * @param $attribute
     * @param $value
     */
    public function getAttribute($attribute)
    {
        $this->conn_id->getAttribute($attribute);
    }


    /**
     * Return Error Message.
     *
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
     *
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
     *
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
     *
     * @param $sql
     * @return mixed
     */
    public function exec($sql)
    {
        return $this->conn_id->exec($sql);
    }

}