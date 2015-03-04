<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * ODBC Result Class
 */
class DBOdbcResult extends DBResult
{

    /**
     * Number of rows in the result set
     * @return integer
     */
    public function getRowsCount()
    {
        return @odbc_num_rows($this->result_id);
    }

    /**
     * Number of fields in the result set
     * @return integer
     */
    public function getFieldsCount()
    {
        return @odbc_num_fields($this->result_id);
    }

    /**
     * Fetch Field Names
     * @return array
     */
    public function fetchFields()
    {
        $field_names = array();
        for ($i = 0; $i < $this->getFieldsCount(); $i++) {
            $field_names[] = odbc_field_name($this->result_id, $i);
        }

        return $field_names;
    }

    /**
     * Field data
     * @return array
     */
    public function getFieldData()
    {
        $retval = array();
        for ($i = 0; $i < $this->getFieldsCount(); $i++) {
            $item = array();
            $item['name'] = odbc_field_name($this->result_id, $i);
            $item['type'] = odbc_field_type($this->result_id, $i);
            $item['max_length'] = odbc_field_len($this->result_id, $i);
            $item['primary_key'] = 0;
            $item['default'] = '';

            $retval[] = $item;
        }

        return $retval;
    }

    /**
     * Free the result
     * @return null
     */
    public function freeResult()
    {
        if (is_resource($this->result_id)) {
            odbc_free_result($this->result_id);
            $this->result_id = false;
        }
    }

    /**
     * Data Seek
     * Moves the internal pointer to the desired offset.  We call
     * this internally before fetching results to make sure the
     * result set starts at zero
     * @return array
     */
    protected function _dataSeek($n = 0)
    {
        return false;
    }

    /**
     * Result - associative array
     * Returns the result set as an array
     * @return array
     */
    protected function _fetchAssoc()
    {
        if (function_exists('odbc_fetch_object')) {
            return odbc_fetch_array($this->result_id);
        } else {
            return $this->_odbcFetchArray($this->result_id);
        }
    }

    /**
     * Result - object
     * Returns the result set as an object
     * @return object
     */
    protected function _fetchObject()
    {
        if (function_exists('odbc_fetch_object')) {
            return odbc_fetch_object($this->result_id);
        } else {
            return $this->_odbcFetchObject($this->result_id);
        }
    }

    /**
     * Result - object
     * @return object
     */
    protected function _odbcFetchObject(& $odbc_result)
    {
        $rs = array();
        $rs_obj = false;
        if (odbc_fetch_into($odbc_result, $rs)) {
            foreach ($rs as $k => $v) {
                $field_name = odbc_field_name($odbc_result, $k + 1);
                $rs_obj->$field_name = $v;
            }
        }
        return $rs_obj;
    }

    /**
     * Result - array
     * @return array
     */
    protected function _odbcFetchArray(& $odbc_result)
    {
        $rs = array();
        $rs_assoc = false;
        if (odbc_fetch_into($odbc_result, $rs)) {
            $rs_assoc = array();
            foreach ($rs as $k => $v) {
                $field_name = odbc_field_name($odbc_result, $k + 1);
                $rs_assoc[$field_name] = $v;
            }
        }
        return $rs_assoc;
    }
}