<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Database Result Class
 *
 * This is the platform-independent result class.
 * This class will not be called directly. Rather, the adapter
 * class for the specific database will extend and instantiate it.
 */
class DBResult
{

    var $conn_id = null;
    var $result_id = null;
    var $result_array = array();
    var $result_object = array();
    var $custom_result_object = array();
    var $current_row = 0;
    var $num_rows = 0;
    var $row_data = null;

    /**
     * Query result.  Acts as a wrapper function for the following functions.
     * @param string $type can be "object" or "array"
     * @return mixed either a result object or array
     */
    public function result($type = 'object')
    {
        if ($type == 'array')
            return $this->resultArray();
        else if ($type == 'object')
            return $this->resultObject();
        else return $this->customResultObject($type);
    }

    /**
     * Custom query result.
     * @param string $class_name A string that represents the type of object you want back
     * @return array of objects
     */
    public function customResultObject($class_name)
    {
        if (array_key_exists($class_name, $this->custom_result_object)) {
            return $this->custom_result_object[$class_name];
        }

        if ($this->result_id === false /*OR $this->getRowsCount() == 0*/) {
            return array();
        }

        // add the data to the object
        $this->_dataSeek(0);
        $result_object = array();

        while ($row = $this->_fetchObject()) {
            $object = new $class_name();

            foreach ($row as $key => $value) {
                $object->$key = $value;
            }

            $result_object[] = $object;
        }

        // return the array
        return $this->custom_result_object[$class_name] = $result_object;
    }

    /**
     * Query result.  "object" version.
     * @return object
     */
    public function resultObject()
    {
        if (count($this->result_object) > 0) {
            return $this->result_object;
        }

        // In the event that query caching is on the result_id variable
        // will return FALSE since there isn't a valid SQL resource so
        // we'll simply return an empty array.
        if ($this->result_id === false /* OR $this->getRowsCount() == 0*/) {
            return array();
        }

        $this->_dataSeek(0);
        while ($row = $this->_fetchObject()) {
            $this->result_object[] = $row;
        }

        return $this->result_object;
    }

    /**
     * Query result.  "array" version.
     * @return array
     */
    public function resultArray($dataProcess = false)
    {
        if (count($this->result_array) > 0) {
            return $this->result_array;
        }

        // In the event that query caching is on the result_id variable
        // will return FALSE since there isn't a valid SQL resource so
        // we'll simply return an empty array.
        if ($this->result_id === false /* OR $this->getRowsCount() == 0*/) {
            return array();
        }

        $this->_dataSeek(0);
        if ($dataProcess === false) {
            while ($row = $this->_fetchAssoc()) {
                $this->result_array[] = $row;
            }
        } else {
            while ($row = $this->_fetchAssoc()) {
                $this->result_array[] = call_user_func($dataProcess, $row);
            }
        }

        return $this->result_array;
    }

    /**
     * Query result.  Acts as a wrapper function for the following functions.
     * @param string
     * @param string $type can be "object" or "array"
     * @return mixed either a result object or array
     */
    public function row($n = 0, $type = 'object')
    {
        if (!is_numeric($n)) {
            // We cache the row data for subsequent uses
            if (!is_array($this->row_data)) {
                $this->row_data = $this->rowArray(0);
            }

            // array_key_exists() instead of isset() to allow for MySQL NULL values
            if (array_key_exists($n, $this->row_data)) {
                return $this->row_data[$n];
            }
            // reset the $n variable if the result was not achieved
            $n = 0;
        }

        if ($type == 'object')
            return $this->rowObject($n);
        else if ($type == 'array')
            return $this->rowArray($n);
        else return $this->customRowObject($n, $type);
    }

    /**
     * Assigns an item into a particular column slot
     * @return object
     */
    public function setRow($key, $value = null)
    {
        // We cache the row data for subsequent uses
        if (!is_array($this->row_data)) {
            $this->row_data = $this->rowArray(0);
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->row_data[$k] = $v;
            }

            return;
        }

        if ($key != '' && !is_null($value)) {
            $this->row_data[$key] = $value;
        }
    }

    /**
     * Returns a single result row - custom object version
     * @return object
     */
    public function customRowObject($n, $type)
    {
        $result = $this->customResultObject($type);

        if (count($result) == 0) {
            return $result;
        }

        if ($n != $this->current_row && isset($result[$n])) {
            $this->current_row = $n;
        }

        return $result[$this->current_row];
    }

    /**
     * Returns a single result row - object version
     * @return object
     */
    public function rowObject($n = 0)
    {
        $result = $this->resultObject();

        if (count($result) == 0) {
            return $result;
        }

        if ($n != $this->current_row AND isset($result[$n])) {
            $this->current_row = $n;
        }

        return $result[$this->current_row];
    }

    /**
     * Returns a single result row - array version
     * @return array
     */
    public function rowArray($n = 0)
    {
        $result = $this->resultArray();

        if (count($result) == 0) {
            return $result;
        }

        if ($n != $this->current_row AND isset($result[$n])) {
            $this->current_row = $n;
        }

        return $result[$this->current_row];
    }

    /**
     * Returns the "first" row
     * @return object
     */
    public function firstRow($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }
        return $result[0];
    }

    /**
     * Returns the "last" row
     * @return object
     */
    public function lastRow($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }
        return $result[count($result) - 1];
    }

    /**
     * Returns the "next" row
     * @return object
     */
    public function nextRow($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }

        if (isset($result[$this->current_row + 1])) {
            ++$this->current_row;
        }

        return $result[$this->current_row];
    }

    /**
     * Returns the "previous" row
     * @return object
     */
    public function previousRow($type = 'object')
    {
        $result = $this->result($type);

        if (count($result) == 0) {
            return $result;
        }

        if (isset($result[$this->current_row - 1])) {
            --$this->current_row;
        }
        return $result[$this->current_row];
    }

    /**
     * Result - associative array
     *
     * Returns the result set as an array
     *
     * @return array
     */
    public function fetchAssoc()
    {
        return $this->_fetchAssoc();
    }

    /**
     * Result - object
     *
     * Returns the result set as an object
     *
     * @return object
     */
    public function fetchObject()
    {
        return $this->_fetchObject();
    }

    /**
     * The following functions are normally overloaded by the identically named
     * methods in the platform-specific driver -- except when query caching
     * is used.  When caching is enabled we do not load the other driver.
     * These functions are primarily here to prevent undefined function errors
     * when a cached result object is in use.  They are not otherwise fully
     * operational due to the unavailability of the database resource IDs with
     * cached results.
     */
    public function getRowsCount()
    {
        return $this->num_rows;
    }

    public function getFieldsCount()
    {
        return 0;
    }

    public function fetchFields()
    {
        return array();
    }

    public function getFieldData()
    {
        return array();
    }

    public function freeResult()
    {
        return true;
    }

    public function bindColumn($column, $param, $type)
    {
        return false;
    }

    public function fetch()
    {
        return $this->_fetchAssoc();
    }

    public function setFetchMode($mode)
    {
    }

    public function debugDumpParams()
    {
    }

    protected function _dataSeek()
    {
        return true;
    }

    protected function _fetchAssoc()
    {
        return array();
    }

    protected function _fetchObject()
    {
        return array();
    }
}
