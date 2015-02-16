<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

class DB
{
    private $_instance = array();

    /**
     * Initialize the database
     *
     * @category	Database
     * @author		ExpressionEngine Dev Team
     * @link		http://codeigniter.com/user_guide/database/
     * @param 	string
     * @param 	bool	Determines if active record should be used or not
     */
    public function getDbInstance($id = '', $params = '', $activeRecordOverride = NULL)
    {
        if (!is_string($id) || $id === '') {
            $id = 'default';
        }
        if (isset($this->_instance[$id])) {
            return $this->_instance[$id];
        }

        // Load the DB config file if a DSN string wasn't passed
        if (is_string($params) && ($params === '' || strpos($params, '://') === FALSE)) {
            Fly::loadConfig('config.database', true);
            $db = Fly::getConfig('database');
            if (!isset($db) || count($db) == 0) {
                throw new FlyException(Fly::t('fly', 'No database connection settings were found in the database config file.'));
            }

            $activeGroup = $id;
            if (!isset($db[$activeGroup])) {
                if (isset($db['active_group'])) {
                    $activeGroup = $db['active_group'];
                }
            }



            if (!isset($activeGroup) || !isset($db[$activeGroup])) {
                throw new FlyException(Fly::t('fly', 'You have specified an invalid database connection group.'));
            }

            $params = $db[$activeGroup];

            if (isset($db['active_record'])) {
                $activeRecord = $db['active_record'];
            }

        } else if (is_string($params)) {

            /* parse the URL from the DSN string
             *  Database settings can be passed as discreet
             *  parameters or as a data source name in the first
             *  parameter. DSNs must have this prototype:
             *  $dsn = 'driver://username:password@hostname/database';
             */

            if (($dns = @parse_url($params)) === FALSE) {
                throw new FlyException(Fly::t('fly', 'Invalid DB Connection String'));
            }

            $params = array(
                                'dbdriver'	=> $dns['scheme'],
                                'hostname'	=> (isset($dns['host'])) ? rawurldecode($dns['host']) : '',
                                'username'	=> (isset($dns['user'])) ? rawurldecode($dns['user']) : '',
                                'password'	=> (isset($dns['pass'])) ? rawurldecode($dns['pass']) : '',
                                'database'	=> (isset($dns['path'])) ? rawurldecode(substr($dns['path'], 1)) : ''
                            );

            // were additional config items set?
            if (isset($dns['query'])) {
                parse_str($dns['query'], $extra);

                foreach ($extra as $key => $val) {
                    // booleans please
                    if (strtoupper($val) == "TRUE") {
                        $val = TRUE;
                    } else if (strtoupper($val) == "FALSE") {
                        $val = FALSE;
                    }

                    $params[$key] = $val;
                }
            }
        }

        // No DB specified yet?  Beat them senseless...
        if (!isset($params['dbdriver']) || $params['dbdriver'] == '') {
            throw new FlyException(Fly::t('fly', 'You have not selected a database type to connect to.'));
        }

        // Load the DB classes.  Note: Since the active record class is optional
        // we need to dynamically create a class that extends proper parent class
        // based on whether we're using the active record class or not.
        // Kudos to Paul for discovering this clever use of eval()

        if ($activeRecordOverride !== NULL) {
            $activeRecord = $activeRecordOverride;
        }

        //require_once(BASEPATH.'database/DB_driver.php');

        if (!isset($activeRecord) || $activeRecord == TRUE) {
            //require_once(BASEPATH.'database/DB_active_rec.php');

            if (!class_exists('DBComponent', false)) {
                eval('class DBComponent extends DBActiveRecord { }');
            }
        } else {
            if (!class_exists('DBComponent', false)) {
                eval('class DBComponent extends DBDriver { }');
            }
        }

        //require_once(BASEPATH.'database/drivers/'.$params['dbdriver'].'/'.$params['dbdriver'].'_driver.php');

        // Instantiate the DB adapter
        $driver = 'DB'.ucfirst($params['dbdriver']).'Driver';
        $DB = new $driver($params);

        if ($DB->autoinit == TRUE) {
            $DB->initialize();
        }

        if (isset($params['stricton']) && $params['stricton'] == TRUE) {
            $DB->query('SET SESSION sql_mode="STRICT_ALL_TABLES"');
        }
        $this->_instance[$id] = $DB;

        return $DB;
    }

    /**
     * Initialize the DBUtility
     *
     * @param $db
     * @return mixed
     */
    public function getDbUtilityInstance($id = '', $params = '', $activeRecordOverride = NULL)
    {
        $db = $this->getDbInstance($id, $params, $activeRecordOverride);
        return DBUtility::shareInstance($db);
    }

    /**
     * Close a database connection
     *
     * @param $id
     */
    public function close($id)
    {
        if (!empty($this->_instance) && isset($this->_instance[$id])) {
            $this->_instance[$id]->close();
            unset($this->_instance[$id]);
        }
    }

    /**
     * Close All database connection
     */
    public function closeAll()
    {
        foreach ($this->_instance as $key => $val) {
            $val->close();
            unset($this->_instance[$key]);
        }
    }

    /**
     * Return all database instance
     */
    public function getDbs()
    {
        return $this->_instance;
    }

}