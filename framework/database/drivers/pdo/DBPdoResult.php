<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * PDO Result Class
 */
class DBPdoResult extends DBResult
{

    //public $num_rows;

    /**
     * Number of rows in the result set
     * @return int
     */
    public function getRowsCount()
    {
        if (($num_rows = $this->result_id->rowCount()) > 0) {
            return $num_rows;
        }

        $num_rows = count($this->result_id->fetchAll());
        $this->result_id->execute();
        return $num_rows;
    }

    /**
     * Number of fields in the result set
     * @return integer
     */
    public function getFieldsCount()
    {
        return $this->result_id->columnCount();
    }

    /**
     * Fetch Field Names
     * Generates an array of column names
     * @return array
     */
    public function fetchFields()
    {
        $data = array();

        try {
            for ($i = 0; $i < $this->getFieldsCount(); $i++) {
                $item = $this->result_id->getColumnMeta($i);
                $data[] = $item['name'];
            }

            return $data;
        } catch (Exception $e) {
            if ($this->db->db_debug) {
                return $this->db->displayError('db_unsuported_feature');
            }
            return false;
        }
    }

    /**
     * Field data
     * @return array
     */
    public function getFieldData()
    {
        $data = array();

        try {
            for ($i = 0; $i < $this->getFieldsCount(); $i++) {
                $data[] = $this->result_id->getColumnMeta($i);
            }

            return $data;
        } catch (Exception $e) {
            if ($this->db->db_debug) {
                return $this->db->displayError('db_unsuported_feature');
            }
            return false;
        }
    }

    /**
     * Free the result
     * @return null
     */
    public function freeResult()
    {
        if (is_object($this->result_id)) {
            $this->result_id = false;
        }
    }

    /**
     * Binds a column to a PHP variable.
     * @param $column
     * @param $value
     * @param null $dataType
     * @return bool|void
     */
    public function bindColumn($column, &$value, $dataType = null)
    {
        if ($dataType === null) {
            $this->result_id->bindColumn($column, $value);
        } else {
            $this->result_id->bindColumn($column, $value, $dataType);
        }
    }

    /**
     * Set the default fetch mode for this statement
     * @param mixed $mode fetch mode
     * @see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php
     */
    public function setFetchMode($mode)
    {
        $params = func_get_args();
        call_user_func_array(array($this->result_id, 'setFetchMode'), $params);
    }

    /**
     * Print Debug info
     */
    public function debugDumpParams()
    {
        $this->result_id->debugDumpParams();
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
     * @return array
     */
    protected function _fetchAssoc()
    {
        return $this->result_id->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Result - object
     * @return object
     */
    protected function _fetchObject()
    {
        return $this->result_id->fetchObject();
    }

    /**
     * Fetches the next row from a result set
     * @return array|void
     */
    public function fetch()
    {
        $params = func_get_args();
        return call_user_func_array(array($this->result_id, 'fetch'), $params);
    }
}