<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * SQLSRV Result Class
 */
class DBSqlsrvResult extends DBResult
{

    /**
     * Number of rows in the result set
     * @return integer
     */
    public function getRowsCount()
    {
        return @sqlsrv_num_rows($this->result_id);
    }

    /**
     * Number of fields in the result set
     * @return integer
     */
    public function getFieldsCount()
    {
        return @sqlsrv_num_fields($this->result_id);
    }

    /**
     * Fetch Field Names
     * @return array
     */
    public function fetchFields()
    {
        $field_names = array();
        foreach (sqlsrv_field_metadata($this->result_id) as $offset => $field) {
            $field_names[] = $field['Name'];
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
        foreach (sqlsrv_field_metadata($this->result_id) as $offset => $field) {
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
            sqlsrv_free_stmt($this->result_id);
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
    public function _dataSeek($n = 0)
    {
        // Not implemented
    }

    /**
     * Result - associative array
     * Returns the result set as an array
     * @return array
     */
    protected function _fetchAssoc()
    {
        return sqlsrv_fetch_array($this->result_id, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Returns the result set as an object
     * @return object
     */
    protected function _fetchObject()
    {
        return sqlsrv_fetch_object($this->result_id);
    }
}