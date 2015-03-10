<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Localization class
 */
class Locale
{

    /**
     * @var array List of translations
     */
    var $language = array();
    /**
     * @var array List of loaded language files
     */
    var $is_loaded = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        Fly::log('debug', "Language Class Initialized");
    }

    /**
     * Load a language file
     * @param mixed $langfile the name of the language file to be loaded. Can be an array
     * @param string $idiom the language (english, etc.)
     * @param bool $return return loaded array of translations
     * @param bool $add_suffix add suffix to $langfile
     * @param string $alt_path alternative path to look for language file
     * @return    mixed
     */
    public function load($langfile = '', $idiom = '', $return = false, $add_suffix = true, $alt_path = '')
    {
        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix == true) {
            $langfile = str_replace('_lang.', '', $langfile).'_lang';
        }

        $langfile .= '.php';

        if (in_array($langfile, $this->is_loaded, true)) {
            return;
        }

        $config = Fly::getConfig();

        if ($idiom == '') {
            $deft_lang = (!isset($config['language'])) ? 'en_us' : $config['language'];
            $idiom = ($deft_lang == '') ? 'en_us' : $deft_lang;
        }

        // Determine where the language file is and load it
        if ($alt_path != '' && file_exists($alt_path.'/language/'.$idiom.'/'.$langfile)) {
            include($alt_path.'/language/'.$idiom.'/'.$langfile);
        } else {
            $found = false;
            $paths = Fly::app()->getSearchPaths();

            // There is no current language files currently set by default when using the english
            $languages = array($idiom, 'en_us');
            foreach ($paths as $package_path) {
                foreach ($languages as $language) {
                    if (file_exists($package_path.'/language/'.$language.'/'.$langfile)) {
                        include($package_path.'/language/'.$language.'/'.$langfile);
                        $found = true;
                        break;
                    }
                }
            }
        }

        if (!isset($lang)) {
            Fly::log('error', 'Language file contains no data: language/'.$idiom.'/'.$langfile);
            return;
        }

        if ($return == true) {
            return $lang;
        }

        $this->is_loaded[] = $langfile;
        $this->language = array_merge($this->language, $lang);
        unset($lang);

        Fly::log('debug', 'Language file loaded: language/'.$idiom.'/'.$langfile);
        return true;
    }

    /**
     * Fetch a single line of text from the language array
     * @param string $line the language line
     * @return string
     */
    public function line($line = '')
    {
        $value = ($line == '' || !isset($this->language[$line])) ? false : $this->language[$line];

        // Because killer robots like unicorns!
        if ($value === false) {
            $value = $line;
            Fly::log('error', 'Could not find the language line "'.$line.'"');
        }
        return $value;
    }
}
