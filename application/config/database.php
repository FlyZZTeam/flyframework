<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------
| DATABASE CONNECTIVITY SETTINGS
| -------------------------------------------------------------------
| This file will contain the settings needed to access your database.
|
| For complete instructions please consult the 'Database Connection'
| page of the User Guide.
|
| -------------------------------------------------------------------
| EXPLANATION OF VARIABLES
| -------------------------------------------------------------------
|
|	['hostname'] The hostname of your database server.
|	['username'] The username used to connect to the database
|	['password'] The password used to connect to the database
|	['database'] The name of the database you want to connect to
|	['dbdriver'] The database type. ie: mysql.  Currently supported:
				 mysql, mysqli, postgre, odbc, mssql, sqlite, oci8
|	['dbprefix'] You can add an optional prefix, which will be added
|				 to the table name when using the  Active Record class
|	['pconnect'] TRUE/FALSE - Whether to use a persistent connection
|	['db_debug'] TRUE/FALSE - Whether database errors should be displayed.
|	['cache_on'] TRUE/FALSE - Enables/disables query caching
|	['cachedir'] The path to the folder where cache files should be stored
|	['char_set'] The character set used in communicating with the database
|	['dbcollat'] The character collation used in communicating with the database
|				 NOTE: For MySQL and MySQLi databases, this setting is only used
| 				 as a backup if your server is running PHP < 5.2.3 or MySQL < 5.0.7
|				 (and in table creation queries made with DB Forge).
| 				 There is an incompatibility in PHP with mysql_real_escape_string() which
| 				 can make your site vulnerable to SQL injection if you are using a
| 				 multi-byte character set and are running versions lower than these.
| 				 Sites using Latin-1 or UTF-8 database character set and collation are unaffected.
|	['swap_pre'] A default table prefix that should be swapped with the dbprefix
|	['autoinit'] Whether or not to automatically initialize the database.
|	['stricton'] TRUE/FALSE - forces 'Strict Mode' connections
|							- good for ensuring strict SQL while developing
|
| The $active_group variable lets you choose which connection group to
| make active.  By default there is only one group (the 'default' group).
|
| The $active_record variables lets you determine whether or not to load
| the active record class
*/

//$db['active_group'] = 'd2';
$db['active_record'] = true;

$db['default']['hostname'] = 'localhost';//'mysql:host=127.0.0.1;dbname=flytest';
$db['default']['username'] = 'postgres';
$db['default']['password'] = '123456';
$db['default']['database'] = 'flytest';
$db['default']['dbdriver'] = 'postgre';
$db['default']['dbprefix'] = 'fly_';
$db['default']['pconnect'] = false;
$db['default']['db_debug'] = true;
$db['default']['cache_on'] = false;
$db['default']['cachedir'] = './application/cache/';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_general_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

$db['d2']['hostname'] = '127.0.0.1';
$db['d2']['username'] = 'root';
$db['d2']['password'] = '123456';
$db['d2']['database'] = 'flymail';
$db['d2']['dbdriver'] = 'mysql';
$db['d2']['dbprefix'] = 'fly_';
$db['d2']['pconnect'] = false;
$db['d2']['db_debug'] = TRUE;
$db['d2']['cache_on'] = false;
$db['d2']['cachedir'] = './application/cache/';
$db['d2']['char_set'] = 'utf8';
$db['d2']['dbcollat'] = 'utf8_general_ci';
$db['d2']['swap_pre'] = '';
$db['d2']['autoinit'] = TRUE;
$db['d2']['stricton'] = FALSE;

return $db;

/* End of file database.php */
/* Location: ./application/config/database.php */