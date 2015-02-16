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
 * @since		Version 2.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Utf8 Class
 *
 * Provides support for UTF-8 environments
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	UTF-8
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/utf8.html
 */
class Utf8 {

	/**
	 * Constructor
	 *
	 * Determines if UTF-8 support is to be enabled
	 *
	 */
	public function __construct()
	{
		Fly::log('debug', "Utf8 Class Initialized");
        $charset = strtoupper(Fly::getConfig('charset'));
		if (
			preg_match('/./u', 'é') === 1					// PCRE must support UTF-8
			&& function_exists('iconv')					// iconv must be installed
			&& ini_get('mbstring.func_overload') != 1		// Multibyte string function overloading cannot be enabled
			&& $charset == 'UTF-8'			// Application charset must be UTF-8
			) {
			Fly::log('debug', "UTF-8 Support Enabled");

			define('UTF8_ENABLED', true);

			// set internal encoding for multibyte string functions if necessary
			// and set a flag so we don't have to repeatedly use extension_loaded()
			// or function_exists()
			if (extension_loaded('mbstring')) {
				define('MB_ENABLED', true);
				mb_internal_encoding('UTF-8');
			} else {
				define('MB_ENABLED', false);
			}
		} else {
			Fly::log('debug', "UTF-8 Support Disabled");
			define('UTF8_ENABLED', false);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Clean UTF-8 strings
	 *
	 * Ensures strings are UTF-8
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function cleanString($str)
	{
		if ($this->_is_ascii($str) === false) {
			$str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
		}
		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Remove ASCII control characters
	 *
	 * Removes all ASCII control characters except horizontal tabs,
	 * line feeds, and carriage returns, as all others can cause
	 * problems in XML
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function safeAsciiForXml($str)
	{
		return StringHelper::removeInvisibleCharacters($str, FALSE);
	}

	// --------------------------------------------------------------------

	/**
	 * Convert to UTF-8
	 *
	 * Attempts to convert a string to UTF-8
	 *
	 * @access	public
	 * @param	string
	 * @param	string	- input encoding
	 * @return	string
	 */
	function convertToUtf8($str, $encoding)
	{
		if (function_exists('iconv')) {
			$str = @iconv($encoding, 'UTF-8', $str);
		} else if (function_exists('mb_convert_encoding')) {
			$str = @mb_convert_encoding($str, 'UTF-8', $encoding);
		} else {
			return false;
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Is ASCII?
	 *
	 * Tests if a string is standard 7-bit ASCII or not
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	function _is_ascii($str)
	{
		return (preg_match('/[^\x00-\x7F]/S', $str) == 0);
	}

	// --------------------------------------------------------------------

}
// End Utf8 Class

/* End of file Utf8.php */
/* Location: ./system/core/Utf8.php */