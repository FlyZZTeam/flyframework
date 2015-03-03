<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * oci8 Result Class
 */
class DBOci8Result extends DBResult
{

    public $stmt_id;
    public $curs_id;
    public $limit_used;

    /**
     * Number of rows in the result set.
     * Oracle doesn't have a graceful way to retun the number of rows
     * so we have to use what amounts to a hack.
     * @return integer
     */
    public function getRowsCount()
    {
        if ($this->num_rows === 0 && count($this->resultArray()) > 0) {
            $this->num_rows = count($this->resultArray());
            @oci_execute($this->stmt_id, OCI_DEFAULT);

            if ($this->curs_id) {
                @oci_execute($this->curs_id, OCI_DEFAULT);
            }
        }

        return $this->num_rows;
    }

    /**
     * Number of fields in the result set
     * @return integer
     */
    public function getFieldsCount()
    {
        $count = @oci_num_fields($this->stmt_id);

        // if we used a limit we subtract it
        if ($this->limit_used) {
            $count = $count - 1;
        }

        return $count;
    }

    /**
     * Fetch Field Names
     * Generates an array of column names
     * @return array
     */
    public function fetchFields()
    {
        $field_names = array();
        for ($c = 1, $fieldCount = $this->getFieldsCount(); $c <= $fieldCount; $c++) {
            $field_names[] = oci_field_name($this->stmt_id, $c);
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
        for ($c = 1, $fieldCount = $this->getFieldsCount(); $c <= $fieldCount; $c++) {
            $item = array();
            $item['name'] = oci_field_name($this->stmt_id, $c);
            $item['type'] = oci_field_type($this->stmt_id, $c);
            $item['max_length'] = oci_field_size($this->stmt_id, $c);
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
            oci_free_statement($this->result_id);
            $this->result_id = false;
        }
    }

    /**
     * Result - associative array
     * Returns the result set as an array
     * @return array
     */
    protected function _fetchAssoc()
    {
        $id = ($this->curs_id) ? $this->curs_id : $this->stmt_id;
        return oci_fetch_assoc($id);
    }

    /**
     * Result - object
     * Returns the result set as an object
     * @return object
     */
    protected function _fetchObject()
    {
        $id = ($this->curs_id) ? $this->curs_id : $this->stmt_id;
        return @oci_fetch_object($id);
    }

    /**
     * Query result.  "array" version.
     *
     * @access  public
     * @return  array
     */
    /*
    public function resultArray()
    {
        if (count($this->result_array) > 0) {
            return $this->result_array;
        }

        $row = NULL;
        while ($row = $this->_fetchAssoc()) {
            $this->result_array[] = $row;
        }

        return $this->result_array;
    }*/

    /**
     * Data Seek
     *
     * Moves the internal pointer to the desired offset.  We call
     * this internally before fetching results to make sure the
     * result set starts at zero
     * @return array
     */
    protected function _dataSeek($n = 0)
    {
        return false; // Not needed
    }
}
