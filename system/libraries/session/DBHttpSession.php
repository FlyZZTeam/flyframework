<?php
/**
 * CDbHttpSession class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

/**
 * CDbHttpSession extends {@link CHttpSession} by using database as session data storage.
 *
 * CDbHttpSession stores session data in a DB table named 'YiiSession'. The table name
 * can be changed by setting {@link sessionTableName}. If the table does not exist,
 * it will be automatically created if {@link autoCreateSessionTable} is set true.
 *
 * The following is the table structure:
 *
 * <pre>
 * CREATE TABLE YiiSession
 * (
 *     id CHAR(32) PRIMARY KEY,
 *     expire INTEGER,
 *     data BLOB
 * )
 * </pre>
 * Where 'BLOB' refers to the BLOB-type of your preffered database.
 *
 * CDbHttpSession relies on {@link http://www.php.net/manual/en/ref.pdo.php PDO} to access database.
 *
 * By default, it will use an SQLite3 database named 'session-YiiVersion.db' under the application runtime directory.
 * You can also specify {@link connectionID} so that it makes use of a DB application component to access database.
 *
 * When using CDbHttpSession in a production server, we recommend you pre-create the session DB table
 * and set {@link autoCreateSessionTable} to be false. This will greatly improve the performance.
 * You may also create a DB index for the 'expire' column in the session table to further improve the performance.
 *
 * @property boolean $useCustomStorage Whether to use custom storage.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package system.web
 * @since 1.0
 */
class DBHttpSession extends HttpSession
{
    public  $activeDbGroupID = 'default';

    /**
     * @var string the ID of a {@link CDbConnection} application component. If not set, a SQLite database
     * will be automatically created and used. The SQLite database file is
     * is <code>protected/runtime/session-YiiVersion.db</code>.
     */
    public $connectionID;

    /**
     * @var string the name of the DB table to store session content.
     * Note, if {@link autoCreateSessionTable} is false and you want to create the DB table manually by yourself,
     * you need to make sure the DB table is of the following structure:
     * <pre>
     * (id CHAR(32) PRIMARY KEY, expire INTEGER, data BLOB)
     * </pre>
     * @see autoCreateSessionTable
     */
    public $sessionTableName='FlySession';

    /**
     * @var boolean whether the session DB table should be automatically created if not exists. Defaults to true.
     * @see sessionTableName
     */
    public $autoCreateSessionTable=true;

    /**
     * Returns a value indicating whether to use custom session storage.
     * This method overrides the parent implementation and always returns true.
     * @return boolean whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return true;
    }

    /**
     * Updates the current session id with a newly generated one.
     * Please refer to {@link http://php.net/session_regenerate_id} for more details.
     * @param boolean $deleteOldSession Whether to delete the old associated session file or not.
     * @since 1.1.8
     */
    public function regenerateID($deleteOldSession = false)
    {
        $oldID = session_id();

        // if no session is started, there is nothing to regenerate
        if (empty($oldID)) {
            return;
        }

        parent::regenerateID(false);
        $newID = session_id();
        $db = Fly::app()->getDB($this->activeDbGroupID);

        $row = $db
            ->select()
            ->from($this->sessionTableName)
            ->where(array('id' => $oldID))
            ->get()
            ->rowArray();
        if($row) {
            if($deleteOldSession) {
                $db->update($this->sessionTableName, array('id' => $newID), array('id' => $oldID));
            } else {
                $row['id'] = $newID;
                $db->insert($this->sessionTableName, $row);
            }
        } else {
            // shouldn't reach here normally
            $db->insert($this->sessionTableName, array(
                'id'=>$newID,
                'expire'=>time()+$this->getTimeout(),
            ));
        }
    }

    /**
     * Creates the session DB table.
     * @param CDbConnection $db the database connection
     * @param string $tableName the name of the table to be created
     */
    protected function createSessionTable($db, $tableName)
    {
        $driver = $db->getPlatform();

        if ($driver === 'mysql') {
            $blob = 'LONGBLOB';
        } else if ($driver === 'pgsql') {
            $blob='BYTEA';
        } else {
            $blob = 'BLOB';
        }
        $fields = array(
            'id' => array(
                'type' => 'CHAR',
                'constraint' => 32,
            ),
            'expire' => array(
                'type' => 'integer',
            ),
            'data' => array(
                'type' => $blob,
            ),
        );
        $db->utils()->addField($fields);
        $db->utils()->addKey('id', true);
        $db->utils()->createTable($tableName);
    }

    /**
     * Session open handler.
     * Do not call this method directly.
     * @param string $savePath session save path
     * @param string $sessionName session name
     * @return boolean whether session is opened successfully
     */
    public function openSession($savePath, $sessionName)
    {
        $db = Fly::app()->getDB($this->activeDbGroupID);
        if ($this->autoCreateSessionTable) {
            try {
                $db->delete($this->sessionTableName, array('expire < ' => time()));
            } catch(Exception $e) {
                $this->createSessionTable($db, $this->sessionTableName);
            }
        }
        return true;
    }

    /**
     * Session read handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        $db = Fly::app()->getDB($this->activeDbGroupID);
        $data = $db
            ->select('data')
            ->from($this->sessionTableName)
            ->where(array('expire > ' => time(), 'id' => $id))
            ->get()
            ->rowArray();
        if ($data && isset($data['data'])) {
            return $data['data'];
        }
        return '';
    }

    /**
     * Session write handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return boolean whether session write is successful
     */
    public function writeSession($id, $data)
    {
        // exception must be caught in session write handler
        // http://us.php.net/manual/en/function.session-set-save-handler.php
        try {
            $expire = time() + $this->getTimeout();
            $db = Fly::app()->getDB($this->activeDbGroupID);
            $res = $db->select('id')->from($this->sessionTableName)->where(array('id' => $id))->get()->rowArray();
            if (!$res) {
                $db->insert($this->sessionTableName, array(
                    'id' => $id,
                    'data' => $data,
                    'expire' => $expire,
                ));
            } else {
                $db->update($this->sessionTableName,array(
                    'data' => $data,
                    'expire' => $expire
                ), array('id' => $id));
            }
        } catch(Exception $e) {
            if(FLY_DEBUG) {
                echo $e->getMessage();
            }
            // it is too late to log an error message here
            return false;
        }
        return true;
    }

    /**
     * Session destroy handler.
     * Do not call this method directly.
     * @param string $id session ID
     * @return boolean whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        Fly::app()->getDB($this->activeDbGroupID)
            ->delete($this->sessionTableName, array('id' => $id));
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * Do not call this method directly.
     * @param integer $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return boolean whether session is GCed successfully
     */
    public function gcSession($maxLifetime)
    {
        Fly::app()->getDB($this->activeDbGroupID)
            ->delete($this->sessionTableName, array('expire < ' => time()));
        return true;
    }
}
