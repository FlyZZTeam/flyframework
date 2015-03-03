<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Postgres Result Class
 */
class DBPostgreResult extends DBResult
{

    /**
     * Number of rows in the result set
     * @return integer
     */
    public function getRowsCount()
    {
        return @pg_num_rows($this->result_id);
    }

    /**
     * Number of fields in the result set
     * @return integer
     */
    public function getFieldsCount()
    {
        return @pg_num_fields($this->result_id);
    }

    /**
     * Fetch Field Names
     * @return array
     */
    public function fetchFields()
    {
        $field_names = array();
        for ($i = 0; $i < $this->getFieldsCount(); $i++) {
            $field_names[] = pg_field_name($this->result_id, $i);
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
            $F = new stdClass();
            $F->name = pg_field_name($this->result_id, $i);
            $F->type = pg_field_type($this->result_id, $i);
            $F->max_length = pg_field_size($this->result_id, $i);
            $F->primary_key = 0;
            $F->default = '';

            $retval[] = $F;
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
            pg_free_result($this->result_id);
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
        return pg_result_seek($this->result_id, $n);
    }

    /**
     * Result - associative array
     * @return array
     */
    protected function _fetchAssoc()
    {
        return pg_fetch_assoc($this->result_id);
    }

    /**
     * Result - object
     * @return object
     */
    protected function _fetchObject()
    {
        return pg_fetch_object($this->result_id);
    }
}