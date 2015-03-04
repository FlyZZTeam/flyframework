<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Encryption Class
 *
 * Provides two-way keyed encoding using Mcrypt
 */
class Encrypt
{
    private $encryptionKey = '';
    private $_hashType = 'sha1';
    private $_mcryptExists = false;
    private $_mcryptCipher;
    private $_mcryptMode;

    /**
     * Constructor
     *
     * Simply determines whether the mcrypt library exists.
     */
    public function __construct()
    {
        $this->_mcryptExists = (!function_exists('mcrypt_encrypt')) ? false : true;

        if ($this->_mcryptExists === false) {
            throw new FlyException(Fly::t('fly', 'The Encrypt library requires the Mcrypt extension.'));
        }

        Fly::log('debug', "Encrypt Class Initialized");
    }

    /**
     * Fetch the encryption key
     *
     * Returns it as MD5 in order to have an exact-length 128 bit key.
     * Mcrypt is sensitive to keys that are not the correct length
     *
     * @param string
     * @return string
     */
    public function getKey($key = '')
    {
        if ($key == '') {
            if ($this->encryptionKey != '') {
                return $this->encryptionKey;
            }
            $key = Fly::getConfig('encryption_key');
            if ($key == false) {
                throw new FlyException(Fly::t('fly', 'In order to use the encryption class requires that you set an encryption key in your config file.'));
            }
        }
        return md5($key);
    }

    /**
     * Set the encryption key
     *
     * @param string
     * @return void
     */
    public function setKey($key = '')
    {
        $this->encryptionKey = $key;
    }

    /**
     * Encode
     *
     * Encodes the message string using bitwise XOR encoding.
     * The key is combined with a random hash, and then it
     * too gets converted using XOR. The whole thing is then run
     * through mcrypt using the randomized key. The end result
     * is a double-encrypted message string that is randomized
     * with each call to this function, even if the supplied
     * message and key are the same.
     *
     * @param string $string the string to encode
     * @param string $key the key
     * @return string
     */
    public function encode($string, $key = '')
    {
        $key = $this->getKey($key);
        $enc = $this->mcryptEncode($string, $key);

        return base64_encode($enc);
    }

    /**
     * Decode
     *
     * Reverses the above process
     *
     * @param string
     * @param string
     * @return string
     */
    function decode($string, $key = '')
    {
        $key = $this->getKey($key);

        if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string)) {
            return false;
        }

        $dec = base64_decode($string);

        if (($dec = $this->mcryptDecode($dec, $key)) === false) {
            return false;
        }

        return $dec;
    }

    /**
     * Encode from Legacy
     *
     * Takes an encoded string from the original Encryption class algorithms and
     * returns a newly encoded string using the improved method added in 2.0.0
     * This allows for backwards compatibility and a method to transition to the
     * new encryption algorithms.
     *
     * For more details, see http://codeigniter.com/user_guide/installation/upgrade_200.html#encryption
     *
     * @param string
     * @param int $legacy_mode (mcrypt mode constant)
     * @param string
     * @return string
     */
    public function encodeFromLegacy($string, $legacy_mode = MCRYPT_MODE_ECB, $key = '')
    {
        // decode it first
        // set mode temporarily to what it was when string was encoded with the legacy
        // algorithm - typically MCRYPT_MODE_ECB
        $current_mode = $this->_getMode();
        $this->setMode($legacy_mode);

        $key = $this->getKey($key);

        if (preg_match('/[^a-zA-Z0-9\/\+=]/', $string)) {
            return false;
        }

        $dec = base64_decode($string);

        if (($dec = $this->mcryptDecode($dec, $key)) === false) {
            return false;
        }

        $dec = $this->_xorDecode($dec, $key);

        // set the mcrypt mode back to what it should be, typically MCRYPT_MODE_CBC
        $this->setMode($current_mode);

        // and re-encode
        return base64_encode($this->mcryptEncode($dec, $key));
    }

    /**
     * XOR Decode
     *
     * Takes an encoded string and key as input and generates the
     * plain-text original message
     *
     * @param string
     * @param string
     * @return string
     */
    private function _xorDecode($string, $key)
    {
        $string = $this->_xorMerge($string, $key);

        $dec = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $dec .= (substr($string, $i++, 1) ^ substr($string, $i, 1));
        }

        return $dec;
    }

    /**
     * XOR key + string Combiner
     *
     * Takes a string and key as input and computes the difference using XOR
     *
     * @param string
     * @param string
     * @return string
     */
    function _xorMerge($string, $key)
    {
        $hash = $this->hash($key);
        $str = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $str .= substr($string, $i, 1) ^ substr($hash, ($i % strlen($hash)), 1);
        }

        return $str;
    }

    /**
     * Encrypt using Mcrypt
     *
     * @param string
     * @param string
     * @return string
     */
    public function mcryptEncode($data, $key)
    {
        $init_size = mcrypt_get_iv_size($this->_getCipher(), $this->_getMode());
        $init_vect = mcrypt_create_iv($init_size, MCRYPT_RAND);
        return $this->_addCipherNoise($init_vect.mcrypt_encrypt($this->_getCipher(), $key, $data, $this->_getMode(), $init_vect), $key);
    }

    /**
     * Decrypt using Mcrypt
     *
     * @param string
     * @param string
     * @return string
     */
    public function mcryptDecode($data, $key)
    {
        $data = $this->_removeCipherNoise($data, $key);
        $init_size = mcrypt_get_iv_size($this->_getCipher(), $this->_getMode());

        if ($init_size > strlen($data)) {
            return false;
        }

        $init_vect = substr($data, 0, $init_size);
        $data = substr($data, $init_size);
        return rtrim(mcrypt_decrypt($this->_getCipher(), $key, $data, $this->_getMode(), $init_vect), "\0");
    }

    /**
     * Adds permuted noise to the IV + encrypted data to protect
     * against Man-in-the-middle attacks on CBC mode ciphers
     * http://www.ciphersbyritter.com/GLOSSARY.HTM#IV
     *
     * Function description
     *
     * @param string
     * @param string
     * @return string
     */
    private function _addCipherNoise($data, $key)
    {
        $keyhash = $this->hash($key);
        $keylen = strlen($keyhash);
        $str = '';

        for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j) {
            if ($j >= $keylen) {
                $j = 0;
            }

            $str .= chr((ord($data[$i]) + ord($keyhash[$j])) % 256);
        }

        return $str;
    }

    /**
     * Removes permuted noise from the IV + encrypted data.
     * @param type
     * @return type
     */
    private function _removeCipherNoise($data, $key)
    {
        $keyhash = $this->hash($key);
        $keylen = strlen($keyhash);
        $str = '';

        for ($i = 0, $j = 0, $len = strlen($data); $i < $len; ++$i, ++$j) {
            if ($j >= $keylen) {
                $j = 0;
            }

            $temp = ord($data[$i]) - ord($keyhash[$j]);

            if ($temp < 0) {
                $temp = $temp + 256;
            }

            $str .= chr($temp);
        }

        return $str;
    }

    /**
     * Set the Mcrypt Cipher
     *
     * @param constant
     * @return string
     */
    function setCipher($cipher)
    {
        $this->_mcryptCipher = $cipher;
    }

    /**
     * Set the Mcrypt Mode
     *
     * @param constant
     * @return string
     */
    function setMode($mode)
    {
        $this->_mcryptMode = $mode;
    }

    /**
     * Get Mcrypt cipher Value
     *
     * @return    string
     */
    private function _getCipher()
    {
        if ($this->_mcryptCipher == '') {
            $this->_mcryptCipher = MCRYPT_RIJNDAEL_256;
        }
        return $this->_mcryptCipher;
    }

    /**
     * Get Mcrypt Mode Value
     *
     * @return    string
     */
    private function _getMode()
    {
        if ($this->_mcryptMode == '') {
            $this->_mcryptMode = MCRYPT_MODE_CBC;
        }
        return $this->_mcryptMode;
    }

    /**
     * Set the Hash type
     *
     * @param string
     * @return string
     */
    public function setHash($type = 'sha1')
    {
        $this->_hashType = ($type != 'sha1' AND $type != 'md5') ? 'sha1' : $type;
    }

    /**
     * Hash encode a string
     *
     * @param string
     * @return string
     */
    public function hash($str)
    {
        return ($this->_hashType == 'sha1') ? $this->sha1($str) : md5($str);
    }

    /**
     * Generate an SHA1 Hash
     *
     * @param string
     * @return string
     */
    public function sha1($str)
    {
        if (!function_exists('sha1')) {
            if (!function_exists('mhash')) {
                Fly::import('system.libraries.Sha1');
                $SH = new Sha1();
                return $SH->generate($str);
            } else {
                return bin2hex(mhash(MHASH_SHA1, $str));
            }
        } else {
            return sha1($str);
        }
    }
}