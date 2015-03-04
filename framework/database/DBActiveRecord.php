<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Active Record Class
 * This is the platform-independent base Active Record implementation class.
 */
class DBActiveRecord extends DBDriver
{

    var $ar_select = array();
    var $ar_distinct = false;
    var $ar_from = array();
    var $ar_join = array();
    var $ar_where = array();
    var $ar_like = array();
    var $ar_groupby = array();
    var $ar_having = array();
    var $ar_keys = array();
    var $ar_limit = false;
    var $ar_offset = false;
    var $ar_order = false;
    var $ar_orderby = array();
    var $ar_set = array();
    var $ar_wherein = array();
    var $ar_aliased_tables = array();
    var $ar_store_array = array();

    // Active Record Caching variables
    var $ar_caching = false;
    var $ar_cache_exists = array();
    var $ar_cache_select = array();
    var $ar_cache_from = array();
    var $ar_cache_join = array();
    var $ar_cache_where = array();
    var $ar_cache_like = array();
    var $ar_cache_groupby = array();
    var $ar_cache_having = array();
    var $ar_cache_orderby = array();
    var $ar_cache_set = array();

    var $ar_no_escape = array();
    var $ar_cache_no_escape = array();

    /**
     * Select
     *
     * Generates the SELECT portion of the query
     *
     * @param string
     * @return object
     */
    public function select($select = '*', $escape = null)
    {
        if (is_string($select)) {
            $select = explode(',', $select);
        }

        foreach ($select as $val) {
            $val = trim($val);

            if ($val != '') {
                $this->ar_select[] = $val;
                $this->ar_no_escape[] = $escape;

                if ($this->ar_caching === true) {
                    $this->ar_cache_select[] = $val;
                    $this->ar_cache_exists[] = 'select';
                    $this->ar_cache_no_escape[] = $escape;
                }
            }
        }
        return $this;
    }

    /**
     * Select Max
     *
     * Generates a SELECT MAX(field) portion of a query
     *
     * @param string $select the field
     * @param string $alias an alias
     * @return object
     */
    public function selectMax($select = '', $alias = '')
    {
        return $this->_getMaxMinAvgSum($select, $alias, 'MAX');
    }

    /**
     * Select Min
     *
     * Generates a SELECT MIN(field) portion of a query
     *
     * @param string $select the field
     * @param string $alias an alias
     * @return object
     */
    public function selectMin($select = '', $alias = '')
    {
        return $this->_getMaxMinAvgSum($select, $alias, 'MIN');
    }

    /**
     * Select Average
     *
     * Generates a SELECT AVG(field) portion of a query
     *
     * @param string $select the field
     * @param string $alias an alias
     * @return object
     */
    public function selectAvg($select = '', $alias = '')
    {
        return $this->_getMaxMinAvgSum($select, $alias, 'AVG');
    }

    /**
     * Select Sum
     *
     * Generates a SELECT SUM(field) portion of a query
     *
     * @param string $select the field
     * @param string $alias an alias
     * @return object
     */
    public function selectSum($select = '', $alias = '')
    {
        return $this->_getMaxMinAvgSum($select, $alias, 'SUM');
    }

    /**
     * Processing Function for the four functions above:
     *
     *    selectMax()
     *    selectMin()
     *    selectAvg()
     *    selectSum()
     *
     * @param string $select the field
     * @param string $alias an alias
     * @param string $type Default type. Defaults to 'MAX'.
     * @return object
     */
    protected function _getMaxMinAvgSum($select = '', $alias = '', $type = 'MAX')
    {
        if (!is_string($select) OR $select == '') {
            $this->displayError('db_invalid_query');
        }

        $type = strtoupper($type);

        if (!in_array($type, array('MAX', 'MIN', 'AVG', 'SUM'))) {
            throw new FlyException(Fly::t('fly', 'Invalid function type: '.$type));
        }

        if ($alias == '') {
            $alias = $this->_createAliasFromTable(trim($select));
        }

        $sql = $type.'('.$this->_protectIdentifiers(trim($select)).') AS '.$alias;

        $this->ar_select[] = $sql;

        if ($this->ar_caching === true) {
            $this->ar_cache_select[] = $sql;
            $this->ar_cache_exists[] = 'select';
        }

        return $this;
    }

    /**
     * Determines the alias name based on the table
     *
     * @param string
     * @return string
     */
    protected function _createAliasFromTable($item)
    {
        if (strpos($item, '.') !== false) {
            return end(explode('.', $item));
        }

        return $item;
    }

    /**
     * DISTINCT
     *
     * Sets a flag which tells the query string compiler to add DISTINCT
     *
     * @param bool
     * @return object
     */
    public function distinct($val = true)
    {
        $this->ar_distinct = (is_bool($val)) ? $val : true;
        return $this;
    }

    /**
     * From
     *
     * Generates the FROM portion of the query
     *
     * @param mixed $from can be a string or array
     * @return object
     */
    public function from($from)
    {
        foreach ((array)$from as $val) {
            if (strpos($val, ',') !== false) {
                foreach (explode(',', $val) as $v) {
                    $v = trim($v);
                    $this->_trackAliases($v);

                    $this->ar_from[] = $this->_protectIdentifiers($v, true, null, false);

                    if ($this->ar_caching === true) {
                        $this->ar_cache_from[] = $this->_protectIdentifiers($v, true, null, false);
                        $this->ar_cache_exists[] = 'from';
                    }
                }
            } else {
                $val = trim($val);

                // Extract any aliases that might exist.  We use this information
                // in the _protect_identifiers to know whether to add a table prefix
                $this->_trackAliases($val);

                $this->ar_from[] = $this->_protectIdentifiers($val, true, null, false);

                if ($this->ar_caching === true) {
                    $this->ar_cache_from[] = $this->_protectIdentifiers($val, true, null, false);
                    $this->ar_cache_exists[] = 'from';
                }
            }
        }

        return $this;
    }

    /**
     * Join
     *
     * Generates the JOIN portion of the query
     *
     * @param string $table
     * @param string $cond the join condition
     * @param string $type the type of join
     * @return object
     */
    public function join($table, $cond, $type = '')
    {
        if ($type != '') {
            $type = strtoupper(trim($type));

            if (!in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'))) {
                $type = '';
            } else {
                $type .= ' ';
            }
        }

        // Extract any aliases that might exist.  We use this information
        // in the _protect_identifiers to know whether to add a table prefix
        $this->_trackAliases($table);

        // Strip apart the condition and protect the identifiers
        if (preg_match('/([\w\.]+)([\W\s]+)(.+)/', $cond, $match)) {
            $match[1] = $this->_protectIdentifiers($match[1]);
            $match[3] = $this->_protectIdentifiers($match[3]);

            $cond = $match[1].$match[2].$match[3];
        }

        // Assemble the JOIN statement
        $join = $type.'JOIN '.$this->_protectIdentifiers($table, true, null, false).' ON '.$cond;

        $this->ar_join[] = $join;
        if ($this->ar_caching === true) {
            $this->ar_cache_join[] = $join;
            $this->ar_cache_exists[] = 'join';
        }

        return $this;
    }

    /**
     * Where
     *
     * Generates the WHERE portion of the query. Separates
     * multiple calls with AND
     *
     * @param mixed
     * @param mixed
     * @return object
     */
    public function where($key, $value = null, $escape = null)
    {
        return $this->_where($key, $value, 'AND ', $escape);
    }

    /**
     * OR Where
     *
     * Generates the WHERE portion of the query. Separates
     * multiple calls with OR
     *
     * @param mixed
     * @param mixed
     * @return object
     */
    public function orWhere($key, $value = null, $escape = null)
    {
        return $this->_where($key, $value, 'OR ', $escape);
    }

    /**
     * Where
     *
     * Called by where() or orWhere()
     *
     * @param mixed
     * @param mixed
     * @param string
     * @return object
     */
    protected function _where($key, $value = null, $type = 'AND ', $escape = null)
    {
        if (!is_array($key)) {
            $key = array($key => $value);
        }

        // If the escape value was not set will will base it on the global setting
        if (!is_bool($escape)) {
            $escape = $this->_protect_identifiers;
        }

        foreach ($key as $k => $v) {
            $prefix = (count($this->ar_where) == 0 AND count($this->ar_cache_where) == 0) ? '' : $type;

            if (is_null($v) && !$this->_hasOperator($k)) {
                // value appears not to have been set, assign the test to IS NULL
                $k .= ' IS NULL';
            }

            if (!is_null($v)) {
                if ($escape === true) {
                    $k = $this->_protectIdentifiers($k, false, $escape);
                    $v = ' '.$this->escape($v);
                }

                if (!$this->_hasOperator($k)) {
                    $k .= ' = ';
                }
            } else {
                $k = $this->_protectIdentifiers($k, false, $escape);
            }

            $this->ar_where[] = $prefix.$k.$v;

            if ($this->ar_caching === true) {
                $this->ar_cache_where[] = $prefix.$k.$v;
                $this->ar_cache_exists[] = 'where';
            }
        }

        return $this;
    }

    /**
     * Generates a WHERE field IN ('item', 'item') SQL query joined with
     * AND if appropriate
     *
     * @param string $key The field to search
     * @param array $values The values searched on
     * @return object
     */
    public function whereIn($key = null, $values = null)
    {
        return $this->_whereIn($key, $values);
    }

    /**
     * Generates a WHERE field IN ('item', 'item') SQL query joined with
     * OR if appropriate
     *
     * @param string $key The field to search
     * @param array $values The values searched on
     * @return object
     */
    public function orWhereIn($key = null, $values = null)
    {
        return $this->_whereIn($key, $values, false, 'OR ');
    }

    /**
     * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
     * with AND if appropriate
     *
     * @param string $key The field to search
     * @param array $values The values searched on
     * @return object
     */
    public function whereNotIn($key = null, $values = null)
    {
        return $this->_whereIn($key, $values, true);
    }

    /**
     * Generates a WHERE field NOT IN ('item', 'item') SQL query joined
     * with OR if appropriate
     *
     * @param string $key The field to search
     * @param array $values The values searched on
     * @return object
     */
    public function orWhereNotIn($key = null, $values = null)
    {
        return $this->_whereIn($key, $values, true, 'OR ');
    }

    /**
     * @param string $key The field to search
     * @param array $values The values searched on
     * @param boolean $not If the statement would be IN or NOT IN
     * @param string
     * @return object
     */
    protected function _whereIn($key = null, $values = null, $not = false, $type = 'AND ')
    {
        if ($key === null OR $values === null) {
            return;
        }

        if (!is_array($values)) {
            $values = array($values);
        }

        $not = ($not) ? ' NOT' : '';

        foreach ($values as $value) {
            $this->ar_wherein[] = $this->escape($value);
        }

        $prefix = (count($this->ar_where) == 0) ? '' : $type;

        $where_in = $prefix.$this->_protectIdentifiers($key).$not." IN (".implode(", ", $this->ar_wherein).") ";

        $this->ar_where[] = $where_in;
        if ($this->ar_caching === true) {
            $this->ar_cache_where[] = $where_in;
            $this->ar_cache_exists[] = 'where';
        }

        // reset the array for multiple calls
        $this->ar_wherein = array();
        return $this;
    }

    /**
     * Like
     *
     * Generates a %LIKE% portion of the query. Separates
     * multiple calls with AND
     *
     * @param mixed
     * @param mixed
     * @return object
     */
    public function like($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side);
    }

    /**
     * Not Like
     *
     * Generates a NOT LIKE portion of the query. Separates
     * multiple calls with AND
     *
     * @param mixed
     * @param mixed
     * @return object
     */
    public function notLike($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'AND ', $side, 'NOT');
    }

    /**
     * OR Like
     *
     * Generates a %LIKE% portion of the query. Separates
     * multiple calls with OR
     *
     * @param mixed
     * @param mixed
     * @return object
     */
    public function orLike($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side);
    }

    /**
     * OR Not Like
     *
     * Generates a NOT LIKE portion of the query. Separates
     * multiple calls with OR
     *
     * @param mixed
     * @param mixed
     * @return object
     */
    public function orNotLike($field, $match = '', $side = 'both')
    {
        return $this->_like($field, $match, 'OR ', $side, 'NOT');
    }

    /**
     * Like
     *
     * Called by like() or orlike()
     *
     * @param mixed
     * @param mixed
     * @param string
     * @return object
     */
    protected function _like($field, $match = '', $type = 'AND ', $side = 'both', $not = '')
    {
        if (!is_array($field)) {
            $field = array($field => $match);
        }

        foreach ($field as $k => $v) {
            $k = $this->_protectIdentifiers($k);

            $prefix = (count($this->ar_like) == 0) ? '' : $type;

            $v = $this->escapeLikeString($v);

            if ($side == 'none') {
                $like_statement = $prefix." $k $not LIKE '{$v}'";
            } else if ($side == 'before') {
                $like_statement = $prefix." $k $not LIKE '%{$v}'";
            } else if ($side == 'after') {
                $like_statement = $prefix." $k $not LIKE '{$v}%'";
            } else {
                $like_statement = $prefix." $k $not LIKE '%{$v}%'";
            }

            // some platforms require an escape sequence definition for LIKE wildcards
            if ($this->_like_escape_str != '') {
                $like_statement = $like_statement.sprintf($this->_like_escape_str, $this->_like_escape_chr);
            }

            $this->ar_like[] = $like_statement;
            if ($this->ar_caching === true) {
                $this->ar_cache_like[] = $like_statement;
                $this->ar_cache_exists[] = 'like';
            }
        }
        return $this;
    }

    /**
     * GROUP BY
     *
     * @param string
     * @return object
     */
    public function groupBy($by)
    {
        if (is_string($by)) {
            $by = explode(',', $by);
        }

        foreach ($by as $val) {
            $val = trim($val);

            if ($val != '') {
                $this->ar_groupby[] = $this->_protectIdentifiers($val);

                if ($this->ar_caching === true) {
                    $this->ar_cache_groupby[] = $this->_protectIdentifiers($val);
                    $this->ar_cache_exists[] = 'groupby';
                }
            }
        }
        return $this;
    }

    /**
     * Sets the HAVING value
     *
     * Separates multiple calls with AND
     *
     * @param string
     * @param string
     * @return object
     */
    public function having($key, $value = '', $escape = true)
    {
        return $this->_having($key, $value, 'AND ', $escape);
    }

    /**
     * Sets the OR HAVING value
     *
     * Separates multiple calls with OR
     *
     * @param string
     * @param string
     * @return object
     */
    public function orHaving($key, $value = '', $escape = true)
    {
        return $this->_having($key, $value, 'OR ', $escape);
    }

    /**
     * Sets the HAVING values
     *
     * @param string
     * @param string
     * @return object
     */
    protected function _having($key, $value = '', $type = 'AND ', $escape = true)
    {
        if (!is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v) {
            $prefix = (count($this->ar_having) == 0) ? '' : $type;

            if ($escape === true) {
                $k = $this->_protectIdentifiers($k);
            }

            if (!$this->_hasOperator($k)) {
                $k .= ' = ';
            }

            if ($v != '') {
                $v = ' '.$this->escape($v);
            }

            $this->ar_having[] = $prefix.$k.$v;
            if ($this->ar_caching === true) {
                $this->ar_cache_having[] = $prefix.$k.$v;
                $this->ar_cache_exists[] = 'having';
            }
        }

        return $this;
    }

    /**
     * Sets the ORDER BY value
     *
     * @param string
     * @param string $direction asc or desc
     * @return object
     */
    public function orderBy($orderby, $direction = '')
    {
        if (strtolower($direction) == 'random') {
            $orderby = ''; // Random results want or don't need a field name
            $direction = $this->_random_keyword;
        } else if (trim($direction) != '') {
            $direction = (in_array(strtoupper(trim($direction)), array('ASC', 'DESC'), true)) ? ' '.$direction : ' ASC';
        }

        if (strpos($orderby, ',') !== false) {
            $temp = array();
            foreach (explode(',', $orderby) as $part) {
                $part = trim($part);
                if (!in_array($part, $this->ar_aliased_tables)) {
                    $part = $this->_protectIdentifiers(trim($part));
                }

                $temp[] = $part;
            }

            $orderby = implode(', ', $temp);
        } else if ($direction != $this->_random_keyword) {
            $orderby = $this->_protectIdentifiers($orderby);
        }

        $orderby_statement = $orderby.$direction;

        $this->ar_orderby[] = $orderby_statement;
        if ($this->ar_caching === true) {
            $this->ar_cache_orderby[] = $orderby_statement;
            $this->ar_cache_exists[] = 'orderby';
        }

        return $this;
    }

    /**
     * Sets the LIMIT value
     *
     * @param integer $value the limit value
     * @param integer $offset the offset value
     * @return object
     */
    public function limit($value, $offset = '')
    {
        $this->ar_limit = (int)$value;

        if ($offset != '') {
            $this->ar_offset = (int)$offset;
        }

        return $this;
    }

    /**
     * Sets the OFFSET value
     *
     * @param integer $offset the offset value
     * @return object
     */
    public function offset($offset)
    {
        $this->ar_offset = $offset;
        return $this;
    }

    /**
     * The "set" function.  Allows key/value pairs to be set for inserting or updating
     *
     * @param mixed
     * @param string
     * @param boolean
     * @return object
     */
    public function set($key, $value = '', $escape = null)
    {
        if (!is_bool($escape)) {
            $escape = $this->_protect_identifiers;
        }
        $key = $this->_objectToArray($key);

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        foreach ($key as $k => $v) {
            if ($escape === false) {
                $this->ar_set[$this->_protectIdentifiers($k, false, $escape)] = $this->escape($v);
            } else {
                $this->ar_set[$this->_protectIdentifiers($k, false, $escape)] = $this->escape($v);
            }
        }

        return $this;
    }

    /**
     * Get
     *
     * Compiles the select statement based on the other functions called
     * and runs the query
     *
     * @param string $table the table
     * @param string $limit the limit clause
     * @param string $offset the offset clause
     * @return object
     */
    public function get($table = '', $limit = null, $offset = null)
    {
        if ($table != '') {
            $this->_trackAliases($table);
            $this->from($table);
        }

        if (!is_null($limit)) {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compileSelect();
        $result = $this->query($sql);
        $this->_resetSelect();
        return $result;
    }

    /**
     * "Count All Results" query
     *
     * Generates a platform-specific query string that counts all records
     * returned by an Active Record query.
     *
     * @param string
     * @return string
     */
    public function countAllResults($table = '')
    {
        if ($table != '') {
            $this->_trackAliases($table);
            $this->from($table);
        }

        $sql = $this->_compileSelect($this->_count_string.$this->_protectIdentifiers('NUMROWS'));

        $query = $this->query($sql);
        $this->_resetSelect();

        if ($query->getRowsCount() == 0) {
            return 0;
        }

        $row = $query->row();
        return (int)$row->NUMROWS;
    }

    /**
     * Get_Where
     *
     * Allows the where clause, limit and offset to be added directly
     *
     * @param string $where the where clause
     * @param string $limit the limit clause
     * @param string $offset the offset clause
     * @return object
     */
    public function getWhere($table = '', $where = null, $limit = null, $offset = null)
    {
        if ($table != '') {
            $this->from($table);
        }

        if (!is_null($where)) {
            $this->where($where);
        }

        if (!is_null($limit)) {
            $this->limit($limit, $offset);
        }

        $sql = $this->_compileSelect();

        $result = $this->query($sql);
        $this->_resetSelect();
        return $result;
    }

    /**
     * Insert_Batch
     *
     * Compiles batch insert strings and runs the queries
     *
     * @param string $table the table to retrieve the results from
     * @param array $set an associative array of insert values
     * @return object
     */
    public function insertBatch($table = '', $set = null, $returnAffectedRows = false)
    {
        if (!is_null($set)) {
            $this->setInsertBatch($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                //No valid data array.  Folds in cases where keys and values did not match up
                return $this->displayError('db_must_use_set');
            }
            return false;
        }

        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        }

        $affectedRows = 0;

        // Batch this baby
        for ($i = 0, $total = count($this->ar_set); $i < $total; $i = $i + 100) {

            $sql = $this->_insertBatch($this->_protectIdentifiers($table, true, null, false), $this->ar_keys, array_slice($this->ar_set, $i, 100));

            $this->query($sql);
            if ($returnAffectedRows) {
                $affectedRows += $this->affectedRows();
            }
        }

        $this->_resetWrite();

        return $returnAffectedRows ? $affectedRows : true;
    }

    /**
     * Allows key/value pairs to be set for batch inserts
     *
     * @param mixed
     * @param string
     * @param boolean
     * @return object
     */
    public function setInsertBatch($key, $value = '', $escape = true)
    {
        $key = $this->_objectToArrayBatch($key);

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        $keys = array_keys(current($key));
        sort($keys);

        foreach ($key as $row) {
            if (count(array_diff($keys, array_keys($row))) > 0 OR count(array_diff(array_keys($row), $keys)) > 0) {
                // batch function above returns an error on an empty array
                $this->ar_set[] = array();
                return;
            }

            ksort($row); // puts $row in the same order as our keys

            if ($escape === false) {
                $this->ar_set[] = '('.implode(',', $row).')';
            } else {
                $clean = array();

                foreach ($row as $value) {
                    $clean[] = $this->escape($value);
                }

                $this->ar_set[] = '('.implode(',', $clean).')';
            }
        }

        foreach ($keys as $k) {
            $this->ar_keys[] = $this->_protectIdentifiers($k);
        }

        return $this;
    }

    /**
     * Insert
     *
     * Compiles an insert string and runs the query
     *
     * @param string $table the table to insert data into
     * @param array $set an associative array of insert values
     * @return object
     */
    public function insert($table = '', $set = null)
    {
        if (!is_null($set)) {
            $this->set($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->displayError('db_must_use_set');
            }
            return false;
        }

        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        }

        $sql = $this->_insert($this->_protectIdentifiers($table, true, null, false), array_keys($this->ar_set), array_values($this->ar_set));

        $this->_resetWrite();
        return $this->query($sql);
    }

    /**
     * Replace
     *
     * Compiles an replace into string and runs the query
     *
     * @param string $table the table to replace data into
     * @param array $set an associative array of insert values
     * @return object
     */
    public function replace($table = '', $set = null)
    {
        if (!is_null($set)) {
            $this->set($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->displayError('db_must_use_set');
            }
            return false;
        }

        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        }

        $sql = $this->_replace($this->_protectIdentifiers($table, true, null, false), array_keys($this->ar_set), array_values($this->ar_set));

        $this->_resetWrite();
        return $this->query($sql);
    }

    /**
     * Update
     *
     * Compiles an update string and runs the query
     *
     * @param string $table the table to retrieve the results from
     * @param array $set an associative array of update values
     * @param mixed $where the where clause
     * @return object
     */
    public function update($table = '', $set = null, $where = null, $limit = null)
    {
        // Combine any cached components with the current statements
        $this->_mergeCache();

        if (!is_null($set)) {
            $this->set($set);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->displayError('db_must_use_set');
            }
            return false;
        }

        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        }

        if ($where != null) {
            $this->where($where);
        }

        if ($limit != null) {
            $this->limit($limit);
        }

        $sql = $this->_update($this->_protectIdentifiers($table, true, null, false), $this->ar_set, $this->ar_where, $this->ar_orderby, $this->ar_limit);

        $this->_resetWrite();
        return $this->query($sql);
    }

    /**
     * Update_Batch
     *
     * Compiles an update string and runs the query
     *
     * @param string $table the table to retrieve the results from
     * @param array $set an associative array of update values
     * @param string $where the where key
     * @return object
     */
    public function updateBatch($table = '', $set = null, $index = null, $returnAffectedRows = false)
    {
        // Combine any cached components with the current statements
        $this->_mergeCache();

        if (is_null($index)) {
            if ($this->db_debug) {
                return $this->displayError('db_must_use_index');
            }

            return false;
        }

        if (!is_null($set)) {
            $this->setUpdateBatch($set, $index);
        }

        if (count($this->ar_set) == 0) {
            if ($this->db_debug) {
                return $this->displayError('db_must_use_set');
            }

            return false;
        }

        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        }

        $affectedRows = 0;

        // Batch this baby
        for ($i = 0, $total = count($this->ar_set); $i < $total; $i = $i + 100) {
            $sql = $this->_updateBatch($this->_protectIdentifiers($table, true, null, false), array_slice($this->ar_set, $i, 100), $this->_protectIdentifiers($index), $this->ar_where);

            $this->query($sql);
            if ($returnAffectedRows) {
                $affectedRows += $this->affectedRows();
            }
        }

        $this->_resetWrite();
        return $returnAffectedRows ? $affectedRows : true;
    }

    /**
     * Allows key/value pairs to be set for batch updating
     *
     * @param array
     * @param string
     * @param boolean
     * @return object
     */
    public function setUpdateBatch($key, $index = '', $escape = true)
    {
        $key = $this->_objectToArrayBatch($key);

        if (!is_array($key)) {
            // @todo error
        }

        foreach ($key as $k => $v) {
            $index_set = false;
            $clean = array();

            foreach ($v as $k2 => $v2) {
                if ($k2 == $index) {
                    $index_set = true;
                } else {
                    $not[] = $k2.'-'.$v2;
                }

                if ($escape === false) {
                    $clean[$this->_protectIdentifiers($k2)] = $v2;
                } else {
                    $clean[$this->_protectIdentifiers($k2)] = $this->escape($v2);
                }
            }

            if ($index_set == false) {
                return $this->displayError('db_batch_missing_index');
            }

            $this->ar_set[] = $clean;
        }

        return $this;
    }

    /**
     * Empty Table
     *
     * Compiles a delete string and runs "DELETE FROM table"
     *
     * @param string $table the table to empty
     * @return object
     */
    public function emptyTable($table = '')
    {
        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        } else {
            $table = $this->_protectIdentifiers($table, true, null, false);
        }

        $sql = $this->_delete($table);

        $this->_resetWrite();

        return $this->query($sql);
    }

    /**
     * Truncate
     *
     * Compiles a truncate string and runs the query
     * If the database does not support the truncate() command
     * This function maps to "DELETE FROM table"
     *
     * @param string $table the table to truncate
     * @return object
     */
    public function truncate($table = '')
    {
        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        } else {
            $table = $this->_protectIdentifiers($table, true, null, false);
        }

        $sql = $this->_truncate($table);

        $this->_resetWrite();

        return $this->query($sql);
    }

    /**
     * Delete
     *
     * Compiles a delete string and runs the query
     *
     * @param mixed $table the table(s) to delete from. String or array
     * @param mixed $where the where clause
     * @param mixed $limit the limit clause
     * @param boolean
     * @return object
     */
    public function delete($table = '', $where = '', $limit = null, $reset_data = true)
    {
        // Combine any cached components with the current statements
        $this->_mergeCache();

        if ($table == '') {
            if (!isset($this->ar_from[0])) {
                if ($this->db_debug) {
                    return $this->displayError('db_must_set_table');
                }
                return false;
            }

            $table = $this->ar_from[0];
        } else if (is_array($table)) {
            foreach ($table as $single_table) {
                $this->delete($single_table, $where, $limit, false);
            }

            $this->_resetWrite();
            return;
        } else {
            $table = $this->_protectIdentifiers($table, true, null, false);
        }

        if ($where != '') {
            $this->where($where);
        }

        if ($limit != null) {
            $this->limit($limit);
        }

        if (count($this->ar_where) == 0 && count($this->ar_wherein) == 0 && count($this->ar_like) == 0) {
            if ($this->db_debug) {
                return $this->displayError('db_del_must_use_where');
            }

            return false;
        }

        $sql = $this->_delete($table, $this->ar_where, $this->ar_like, $this->ar_limit);

        if ($reset_data) {
            $this->_resetWrite();
        }

        return $this->query($sql);
    }

    /**
     * DB Prefix
     *
     * Prepends a database prefix if one exists in configuration
     *
     * @param string $table the table
     * @return string
     */
    public function dbprefix($table = '')
    {
        if ($table == '') {
            $this->displayError('db_table_name_required');
        }

        return $this->dbprefix.$table;
    }

    /**
     * Set DB Prefix
     *
     * Set's the DB Prefix to something new without needing to reconnect
     *
     * @param string $prefix the prefix
     * @return string
     */
    public function setDbPrefix($prefix = '')
    {
        return $this->dbprefix = $prefix;
    }

    /**
     * Track Aliases
     *
     * Used to track SQL statements written with aliased tables.
     *
     * @param string $table The table to inspect
     * @return string
     */
    protected function _trackAliases($table)
    {
        if (is_array($table)) {
            foreach ($table as $t) {
                $this->_trackAliases($t);
            }
            return;
        }

        // Does the string contain a comma?  If so, we need to separate
        // the string into discreet statements
        if (strpos($table, ',') !== false) {
            return $this->_trackAliases(explode(',', $table));
        }

        // if a table alias is used we can recognize it by a space
        if (strpos($table, " ") !== false) {
            // if the alias is written with the AS keyword, remove it
            $table = preg_replace('/\s+AS\s+/i', ' ', $table);

            // Grab the alias
            $table = trim(strrchr($table, " "));

            // Store the alias, if it doesn't already exist
            if (!in_array($table, $this->ar_aliased_tables)) {
                $this->ar_aliased_tables[] = $table;
            }
        }
    }

    /**
     * Compile the SELECT statement
     *
     * Generates a query string based on which functions were used.
     * Should not be called directly.  The get() function calls it.
     *
     * @return string
     */
    protected function _compileSelect($select_override = false)
    {
        // Combine any cached components with the current statements
        $this->_mergeCache();

        // ----------------------------------------------------------------

        // Write the "select" portion of the query

        if ($select_override !== false) {
            $sql = $select_override;
        } else {
            $sql = (!$this->ar_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';

            if (count($this->ar_select) == 0) {
                $sql .= '*';
            } else {
                // Cycle through the "select" portion of the query and prep each column name.
                // The reason we protect identifiers here rather then in the select() function
                // is because until the user calls the from() function we don't know if there are aliases
                foreach ($this->ar_select as $key => $val) {
                    $no_escape = isset($this->ar_no_escape[$key]) ? $this->ar_no_escape[$key] : null;
                    $this->ar_select[$key] = $this->_protectIdentifiers($val, false, $no_escape);
                }

                $sql .= implode(', ', $this->ar_select);
            }
        }

        // ----------------------------------------------------------------

        // Write the "FROM" portion of the query

        if (count($this->ar_from) > 0) {
            $sql .= "\nFROM ";

            $sql .= $this->_fromTables($this->ar_from);
        }

        // ----------------------------------------------------------------

        // Write the "JOIN" portion of the query

        if (count($this->ar_join) > 0) {
            $sql .= "\n";

            $sql .= implode("\n", $this->ar_join);
        }

        // ----------------------------------------------------------------

        // Write the "WHERE" portion of the query

        if (count($this->ar_where) > 0 OR count($this->ar_like) > 0) {
            $sql .= "\nWHERE ";
        }

        $sql .= implode("\n", $this->ar_where);

        // ----------------------------------------------------------------

        // Write the "LIKE" portion of the query

        if (count($this->ar_like) > 0) {
            if (count($this->ar_where) > 0) {
                $sql .= "\nAND ";
            }

            $sql .= implode("\n", $this->ar_like);
        }

        // ----------------------------------------------------------------

        // Write the "GROUP BY" portion of the query

        if (count($this->ar_groupby) > 0) {
            $sql .= "\nGROUP BY ";

            $sql .= implode(', ', $this->ar_groupby);
        }

        // ----------------------------------------------------------------

        // Write the "HAVING" portion of the query

        if (count($this->ar_having) > 0) {
            $sql .= "\nHAVING ";
            $sql .= implode("\n", $this->ar_having);
        }

        // ----------------------------------------------------------------

        // Write the "ORDER BY" portion of the query

        if (count($this->ar_orderby) > 0) {
            $sql .= "\nORDER BY ";
            $sql .= implode(', ', $this->ar_orderby);

            if ($this->ar_order !== false) {
                $sql .= ($this->ar_order == 'desc') ? ' DESC' : ' ASC';
            }
        }

        // ----------------------------------------------------------------

        // Write the "LIMIT" portion of the query

        if (is_numeric($this->ar_limit)) {
            $sql .= "\n";
            $sql = $this->_limit($sql, $this->ar_limit, $this->ar_offset);
        }

        return $sql;
    }

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param    object
     * @return    array
     */
    public function _objectToArray($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        foreach (get_object_vars($object) as $key => $val) {
            // There are some built in keys we need to ignore for this conversion
            if (!is_object($val) && !is_array($val) && $key != '_parent_name') {
                $array[$key] = $val;
            }
        }

        return $array;
    }

    /**
     * Object to Array
     *
     * Takes an object as input and converts the class variables to array key/vals
     *
     * @param object
     * @return array
     */
    public function _objectToArrayBatch($object)
    {
        if (!is_object($object)) {
            return $object;
        }

        $array = array();
        $out = get_object_vars($object);
        $fields = array_keys($out);

        foreach ($fields as $val) {
            // There are some built in keys we need to ignore for this conversion
            if ($val != '_parent_name') {

                $i = 0;
                foreach ($out[$val] as $data) {
                    $array[$i][$val] = $data;
                    $i++;
                }
            }
        }

        return $array;
    }

    /**
     * Start Cache
     *
     * Starts AR caching
     *
     * @return void
     */
    public function startCache()
    {
        $this->ar_caching = true;
    }

    /**
     * Stop Cache
     *
     * Stops AR caching
     *
     * @return void
     */
    public function stopCache()
    {
        $this->ar_caching = false;
    }

    /**
     * Flush Cache
     *
     * Empties the AR cache
     *
     * @return void
     */
    public function flushCache()
    {
        $this->_resetRun(array(
            'ar_cache_select' => array(),
            'ar_cache_from' => array(),
            'ar_cache_join' => array(),
            'ar_cache_where' => array(),
            'ar_cache_like' => array(),
            'ar_cache_groupby' => array(),
            'ar_cache_having' => array(),
            'ar_cache_orderby' => array(),
            'ar_cache_set' => array(),
            'ar_cache_exists' => array(),
            'ar_cache_no_escape' => array()
        ));
    }

    /**
     * Merge Cache
     *
     * When called, this function merges any cached AR arrays with
     * locally called ones.
     *
     * @return void
     */
    protected function _mergeCache()
    {
        if (count($this->ar_cache_exists) == 0) {
            return;
        }

        foreach ($this->ar_cache_exists as $val) {
            $ar_variable = 'ar_'.$val;
            $ar_cache_var = 'ar_cache_'.$val;

            if (count($this->$ar_cache_var) == 0) {
                continue;
            }

            $this->$ar_variable = array_unique(array_merge($this->$ar_cache_var, $this->$ar_variable));
        }

        // If we are "protecting identifiers" we need to examine the "from"
        // portion of the query to determine if there are any aliases
        if ($this->_protect_identifiers === true AND count($this->ar_cache_from) > 0) {
            $this->_trackAliases($this->ar_from);
        }

        $this->ar_no_escape = $this->ar_cache_no_escape;
    }

    /**
     * Resets the active record values.  Called by the get() function
     *
     * @param array $ar_reset_itmes An array of fields to reset
     * @return void
     */
    protected function _resetRun($ar_reset_items)
    {
        foreach ($ar_reset_items as $item => $default_value) {
            if (!in_array($item, $this->ar_store_array)) {
                $this->$item = $default_value;
            }
        }
    }

    /**
     * Resets the active record values.  Called by the get() function
     *
     * @return void
     */
    protected function _resetSelect()
    {
        $ar_reset_items = array(
            'ar_select' => array(),
            'ar_from' => array(),
            'ar_join' => array(),
            'ar_where' => array(),
            'ar_like' => array(),
            'ar_groupby' => array(),
            'ar_having' => array(),
            'ar_orderby' => array(),
            'ar_wherein' => array(),
            'ar_aliased_tables' => array(),
            'ar_no_escape' => array(),
            'ar_distinct' => false,
            'ar_limit' => false,
            'ar_offset' => false,
            'ar_order' => false,
        );

        $this->_resetRun($ar_reset_items);
    }

    /**
     * Resets the active record "write" values.
     * @return void
     */
    protected function _resetWrite()
    {
        $ar_reset_items = array(
            'ar_set' => array(),
            'ar_from' => array(),
            'ar_where' => array(),
            'ar_like' => array(),
            'ar_orderby' => array(),
            'ar_keys' => array(),
            'ar_limit' => false,
            'ar_order' => false
        );

        $this->_resetRun($ar_reset_items);
    }
}