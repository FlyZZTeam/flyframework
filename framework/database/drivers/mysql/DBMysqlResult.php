<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * MySQL Result Class
 */
class DBMysqlResult extends DBResult
{

    /**
     * Number of rows in the result set
     * @return integer
     */
    public function getRowsCount()
    {
        return @mysql_num_rows($this->result_id);
    }

    /**
     * Number of fields in the result set
     * @return integer
     */
    public function getFieldsCount()
    {
        return @mysql_num_fields($this->result_id);
    }

    /**
     * Fetch Field Names
     * Generates an array of column names
     * @return array
     */
    public function fetchFields()
    {
        $field_names = array();
        while ($field = mysql_fetch_field($this->result_id)) {
            $field_names[] = $field->name;
        }

        return $field_names;
    }

    /**
     * Field data
     * Generates an array of objects containing field meta-data
     * @return array
     */
    public function getFieldData()
    {
        $retval = array();
        while ($field = mysql_fetch_field($this->result_id)) {
            $retval[] = $field;
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
            mysql_free_result($this->result_id);
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
        return mysql_data_seek($this->result_id, $n);
    }

    /**
     * Result - associative array
     * Returns the result set as an array
     * @return array
     */
    protected function _fetchAssoc()
    {
        return mysql_fetch_assoc($this->result_id);
    }

    /**
     * Result - object
     * Returns the result set as an object
     * @return object
     */
    protected function _fetchObject()
    {
        return mysql_fetch_object($this->result_id);
    }
}