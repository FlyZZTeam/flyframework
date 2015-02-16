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

// ------------------------------------------------------------------------

/**
 * Form Validation Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Validation
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/form_validation.html
 */
class Validation extends Component
{
	protected $_rules		= array();
	protected $_errors	    = array();
	protected $_errorMessages = array();
    protected $_attributes = array();

    /**
     * @var boolean whether this validation rule should be skipped when there is already a validation
     * error for the current attribute. Defaults to false.
     */
    public $skipOnError = false;
    public $engine = 'inLine';

	/**
	 * Constructor
	 */
	public function __construct($rules = array())
	{
		// Validation rules can be stored in a config file.
		$this->setRules($rules);

		// Set the character encoding in MB.
		if (function_exists('mb_internal_encoding')) {
			mb_internal_encoding(Fly::getConfig('charset'));
		}

		Fly::log('debug', "Form Validation Class Initialized");
	}

    /**
     * Set wait for the validation raw data
     *
     * @param $data
     */
    public function setAttributes($data, $attributeLabels = array())
    {
        foreach ($data as $key => $val) {
            $this->_attributes[$key]['data'] = $val;
            if (isset($attributeLabels[$key])) {
                $this->_attributes[$key]['label'] = $attributeLabels[$key];
            } else {
                $this->_attributes[$key]['label'] = '';
            }
        }
    }

	/**
	 * Set Rules
	 *
	 * This function takes an array of field names and validation
	 * rules as input, validates the info, and stores it
	 *
	 * @access	public
	 * @param	mixed
	 * @param	string
	 * @return	void
	 */
	public function setRules($rules = array())
	{
        if (empty($rules) || !is_array($rules)) {
            return $this;
        }
        $this->_rules = $rules;

		return $this;
	}

	/**
	 * Set Error Message
	 *
	 * Lets users set their own error messages on the fly.  Note:  The key
	 * name has to match the  function name that it corresponds to.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	public function setMessage($lang, $val = '')
	{
		if (!is_array($lang)) {
			$lang = array($lang => $val);
		}

		$this->_errorMessages = array_merge($this->_errorMessages, $lang);

		return $this;
	}

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors !== array();
        } else {
            return isset($this->_errors[$attribute]);
        }
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_errors;
        } else {
            return isset($this->_errors[$attribute]) ? $this->_errors[$attribute] : array();
        }
    }

    /**
     * Returns the first error of the specified attribute.
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     */
    public function getError($attribute)
    {
        return isset($this->_errors[$attribute]) ? reset($this->_errors[$attribute]) : null;
    }

    /**
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error)
    {
        $this->_errors[$attribute][] = $error;
    }

    /**
     * Adds a list of errors.
     * @param array $errors a list of errors. The array keys must be attribute names.
     * The array values should be error messages. If an attribute has multiple errors,
     * these errors must be given in terms of an array.
     * You may use the result of {@link getErrors} as the value for this parameter.
     */
    public function addErrors($errors)
    {
        foreach ($errors as $attribute => $error) {
            if (is_array($error)) {
                foreach($error as $e) {
                    $this->addError($attribute, $e);
                }
            } else {
                $this->addError($attribute, $error);
            }
        }
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null) {
            $this->_errors = array();
        } else {
            unset($this->_errors[$attribute]);
        }
    }

	/**
	 * Error String
	 *
	 * Returns the error messages as a string, wrapped in the error delimiters
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	str
	 */
	public function getErrorString()
	{
		// No errrors, validation passes!
		if (count($this->_errors) === 0) {
			return '';
		}
		// Generate the error string
		$str = '';
		foreach ($this->_errors as $val) {
			if ($val != '') {
				$str .= implode('<br>', $val).'<br>';
			}
		}
		return $str;
	}

	/**
	 * Run the Validator
	 *
	 * This function does all the work.
	 *
	 * @access	public
	 * @return	bool
	 */
	public function run($context = null)
	{
		if (count($this->_attributes) == 0) {
			return false;
		}

		// Does the _field_data array containing the validation rules exist?
		// If not, we look to see if they were assigned via a config file
		if (count($this->_rules) == 0) {
            return true;
		}

		foreach ($this->_rules as $row) {
            $type = $row[1];
            if (preg_match("/^validator:(\w\W)+/", $type)) {
                $engine = '_executeValidatorEngine';
            } else {
                $engine = '_execute'.ucfirst($this->engine).'Engine';
            }
			$this->$engine($row, $context);
            if ($this->skipOnError && $this->hasErrors()) {
                break;
            }
		}

		// Did we end up with any errors?
		$totalErrors = count($this->_errors);

		// No errors, validation passes!
		if ($totalErrors == 0) {
			return TRUE;
		}

		return FALSE;
	}

    protected function _executeValidatorEngine($rule, $model = null)
    {
        $type = str_replace('validator:', '', $rule[1]);
        $validator = self::createValidator($type, $model, $rule[0]);
        $validator->validateAttribute($model, $rule[0]);
        $this->addErrors($validator->getErrors());
    }

	/**
	 * Executes the InLineEngine
	 *
	 * @access	private
	 * @param	array
	 * @param	array
	 * @param	mixed
	 * @param	integer
	 * @return	mixed
	 */
	protected function _executeInLineEngine($row, $context = NULL)
	{
        $rules = explode('|', $row[1]);
        $attributeData = array();
        if (isset($this->_attributes[$row[0]]) && isset($this->_attributes[$row[0]]['data'])) {
            $data = $this->_attributes[$row[0]]['data'];
            if (is_array($data)) {
                $attributeData = $data;
            } else {
                $attributeData[] = $data;
            }
        }
        $attributeLabel = '';
        if (isset($this->_attributes[$row[0]]) && isset($this->_attributes[$row[0]]['label'])) {
            $attributeLabel = $this->_attributes[$row[0]]['label'];
        }

        foreach ($attributeData as $item) {
		    // If the field is blank, but NOT required, no further tests are necessary
		    $callback = FALSE;
            $postdata = $item;
            if (!in_array('required', $rules) && is_null($postdata)) {
                // Before we bail out, does the rule contain a callback?
                if (preg_match("/(callback:\w+(\[.*?\])?)/", implode(' ', $rules), $match)) {
                    $callback = TRUE;
                    $rules = (array('1' => $match[1]));
                } else {
                    return;
                }
            }

            // Isset Test. Typically this rule will only apply to checkboxes.
            if (is_null($postdata) AND $callback == FALSE) {
                if (in_array('isset', $rules, TRUE) OR in_array('required', $rules)) {
                    // Set the message type
                    $type = (in_array('required', $rules)) ? 'required' : 'isset';

                    if (!isset($this->_errorMessages[$type])) {
                        if (FALSE === ($line = Fly::t('form_validation', $type))) {
                            $line = 'The field was not set';
                        }
                    } else {
                        $line = $this->_errorMessages[$type];
                    }

                    // Build the error message
                    $message = sprintf($line, $attributeLabel);

                    $this->addError($row[0], $message);
                }

                return;
            }

            foreach ($rules As $rule) {

                // Is the rule a callback?
                $callback = FALSE;
                if (substr($rule, 0, 9) == 'callback:') {
                    $rule = substr($rule, 9);
                    $callback = TRUE;
                }

                // Strip the parameter (if exists) from the rule
                // Rules can contain a parameter: max_length[5]
                $param = FALSE;
                if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
                    $rule	= $match[1];
                    $param	= $match[2];
                }

                // Call the function that corresponds to the rule
                if ($callback === TRUE) {
                    $object = null;
                    if (method_exists($context, $rule)) {
                        $object = $context;
                    } else if (method_exists(Fly::app()->Controller, $rule)) {
                        $object = Fly::app()->Controller;
                    } else {
                        Fly::log('debug', "Unable to find callback validation rule: ".$rule);
                        continue;
                    }
                    $result = $object->$rule($postdata, $param);
                    // If the field isn't required and we just processed a callback we'll move on...
                    if (!in_array('required', $rules, TRUE) AND $result !== FALSE) {
                        continue;
                    }
                } else {
                    if (!method_exists($this, $rule)) {
                        // If our own wrapper function doesn't exist we see if a native PHP function does.
                        // Users can use any native PHP function call that has one param.
                        if (function_exists($rule)) {
                            $result = $rule($postdata);
                        } else {
                            Fly::log('debug', "Unable to find validation rule: ".$rule);
                        }

                        continue;
                    }
                    $result = $this->$rule($postdata, $param);
                }

                // Did the rule test negatively?  If so, grab the error.
                if ($result === FALSE) {
                    if (isset($row['message'])) {
                        $message = $row['message'];
                    } else {
                        if (!isset($this->_errorMessages[$rule])) {
                            if (FALSE === ($line = Fly::t('form_validation', $rule))) {
                                $line = 'Unable to access an error message corresponding to your field name.';
                            }
                        } else {
                            $line = $this->_errorMessages[$rule];
                        }

                        // Is the parameter we are inserting into the error message the name
                        // of another field?  If so we need to grab its "field label"
                        if (isset($this->_attributes[$param]) && isset($this->_attributes[$param]['label'])) {
                            $param = $this->_attributes[$param]['label'];
                        }

                        // Build the error message
                        $message = sprintf($line, $attributeLabel, $param);
                    }
                    $this->addError($row[0], $message);
                    if ($this->skipOnError) {
                        return;
                    }
                }
            }
        }
	}

    /**
     * Creates a validator object.
     * @param mixed $type the validator type. This can be a built-in validator name,
     * a method name of the model class, an anonymous function, or a validator class name.
     * @param Model $model the data model to be validated.
     * @param array|string $attributes list of attributes to be validated. This can be either an array of
     * the attribute names or a string of comma-separated attribute names.
     * @param array $params initial values to be applied to the validator properties
     * @return Validator the validator
     */
    public static function createValidator($type, $model, $attributes, $params = array())
    {
        if (is_string($attributes)) {
            $attributes = preg_split('/[\s,]+/', $attributes, -1, PREG_SPLIT_NO_EMPTY);
        }
        $params['attributes'] = $attributes;

        if(method_exists($model, $type)) {
            /*
            $validator=new CInlineValidator;
            $validator->attributes=$attributes;
            $validator->method=$name;
            if(isset($params['clientValidate']))
            {
                $validator->clientValidate=$params['clientValidate'];
                unset($params['clientValidate']);
            }
            $validator->params=$params;
            if(isset($params['skipOnError']))
                $validator->skipOnError=$params['skipOnError'];*/
        } else {
            $params['attributes'] = $attributes;
            if(isset(self::$builtInValidators[$type]))
                $className = Fly::import(self::$builtInValidators[$type], true);
            else
                $className = Fly::import($type, true);
            $validator = new $className;
            foreach($params as $name => $value) {
                $validator->$name = $value;
            }
        }
    }

    /**
     * Validates a single attribute.
     * Child classes must implement this method to provide the actual validation logic.
     * @param Model $model the data model to be validated
     * @param string $attribute the name of the attribute to be validated.
     */
    public function validateAttribute($model, $attribute)
    {
        if (isset($this->_attributes[$attribute]) && isset($this->_attributes[$attribute]['data'])) {
            $val = $this->_attributes[$attribute]['data'];
        } else {
            $val = $model->$attribute;
        }
        $result = $this->validateValue($val);
        if (!empty($result)) {
            //$this->addError($model, $attribute, $result[0], $result[1]);
        }
    }

    /**
     * Validates a value.
     * A validator class can implement this method to support data validation out of the context of a data model.
     * @param mixed $value the data value to be validated.
     * @return array|null the error message and the parameters to be inserted into the error message.
     * Null should be returned if the data is valid.
     * @throws FlyException if the validator does not supporting data validation without a model
     */
    protected function validateValue($value)
    {
        throw new FlyException(get_class($this) . ' does not support validateValue().');
    }

	/**
	 * Required
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function required($str)
	{
		if (!is_array($str)) {
			return (trim($str) == '') ? FALSE : TRUE;
		} else {
			return ( ! empty($str));
		}
	}

	/**
	 * Performs a Regular Expression match test.
	 *
	 * @access	public
	 * @param	string
	 * @param	regex
	 * @return	bool
	 */
	public function regexMatch($str, $regex)
	{
		if (!preg_match($regex, $str)) {
			return FALSE;
		}
		return  TRUE;
	}

	/**
	 * Match one field to another
	 *
	 * @access	public
	 * @param	string
	 * @param	field
	 * @return	bool
	 */
	public function matches($str, $field)
	{
		if (!isset($_POST[$field])) {
			return FALSE;
		}

		$field = $_POST[$field];

		return ($str !== $field) ? FALSE : TRUE;
	}

	/**
	 * Match one field to another
	 *
	 * @access	public
	 * @param	string
	 * @param	field
	 * @return	bool
	 */
	public function isUnique($str, $field)
	{
		list($table, $field)=explode('.', $field);
		$query = Fly::app()->db->limit(1)->getWhere($table, array($field => $str));
		
		return $query->getRowsCount() === 0;
    }

	/**
	 * Minimum Length
	 *
	 * @access	public
	 * @param	string
	 * @param	value
	 * @return	bool
	 */
	public function minLength($str, $val)
	{
		if (preg_match("/[^0-9]/", $val)) {
			return FALSE;
		}

		if (function_exists('mb_strlen')) {
			return (mb_strlen($str) < $val) ? FALSE : TRUE;
		}

		return (strlen($str) < $val) ? FALSE : TRUE;
	}

	/**
	 * Max Length
	 *
	 * @access	public
	 * @param	string
	 * @param	value
	 * @return	bool
	 */
	public function maxLength($str, $val)
	{
		if (preg_match("/[^0-9]/", $val)) {
			return FALSE;
		}

		if (function_exists('mb_strlen')) {
			return (mb_strlen($str) > $val) ? FALSE : TRUE;
		}

		return (strlen($str) > $val) ? FALSE : TRUE;
	}

	/**
	 * Exact Length
	 *
	 * @access	public
	 * @param	string
	 * @param	value
	 * @return	bool
	 */
	public function exactLength($str, $val)
	{
		if (preg_match("/[^0-9]/", $val)) {
			return FALSE;
		}

		if (function_exists('mb_strlen')) {
			return (mb_strlen($str) != $val) ? FALSE : TRUE;
		}

		return (strlen($str) != $val) ? FALSE : TRUE;
	}

	/**
	 * Valid Email
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validEmail($str)
	{
		return ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? FALSE : TRUE;
	}

	/**
	 * Valid Emails
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validEmails($str)
	{
		if (strpos($str, ',') === FALSE) {
			return $this->validEmail(trim($str));
		}

		foreach (explode(',', $str) as $email) {
			if (trim($email) != '' && $this->validEmail(trim($email)) === FALSE) {
				return FALSE;
			}
		}

		return TRUE;
    }

	/**
	 * Validate IP Address
	 *
	 * @access	public
	 * @param	string
	 * @param	string "ipv4" or "ipv6" to validate a specific ip format
	 * @return	string
	 */
	public function validIp($ip, $which = '')
	{
		return Fly::app()->Request->validIp($ip, $which);
	}

	/**
	 * Alpha
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function alpha($str)
	{
		return (!preg_match("/^([a-z])+$/i", $str)) ? FALSE : TRUE;
	}

	/**
	 * Alpha-numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function alphaNumeric($str)
	{
		return ( ! preg_match("/^([a-z0-9])+$/i", $str)) ? FALSE : TRUE;
	}

	/**
	 * Alpha-numeric with underscores and dashes
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function alphaDash($str)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $str)) ? FALSE : TRUE;
	}

	/**
	 * Numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function numeric($str)
	{
		return (bool)preg_match( '/^[\-+]?[0-9]*\.?[0-9]+$/', $str);

	}

	/**
	 * Is Numeric
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function isNumeric($str)
	{
		return ( ! is_numeric($str)) ? FALSE : TRUE;
	}

	/**
	 * Integer
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function integer($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
	}

	/**
	 * Decimal number
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function decimal($str)
	{
		return (bool) preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
	}

	/**
	 * Greather than
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function greaterThan($str, $min)
	{
		if (!is_numeric($str)) {
			return FALSE;
		}
		return $str > $min;
	}

	/**
	 * Less than
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function lessThan($str, $max)
	{
		if (!is_numeric($str)) {
			return FALSE;
		}
		return $str < $max;
	}

	/**
	 * Is a Natural number  (0,1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function isNatural($str)
	{
		return (bool) preg_match( '/^[0-9]+$/', $str);
	}

	/**
	 * Is a Natural number, but not a zero  (1,2,3, etc.)
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function isNaturalNoZero($str)
	{
		if (!preg_match( '/^[0-9]+$/', $str)) {
			return FALSE;
		}

		if ($str == 0) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Valid Base64
	 *
	 * Tests a string for characters outside of the Base64 alphabet
	 * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validBase64($str)
	{
		return (bool) ! preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
	}

	/**
	 * Prep data for form
	 *
	 * This function allows HTML to be safely shown in a form.
	 * Special characters are converted.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function prepForForm($data = '')
	{
		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = $this->prepForForm($val);
			}
			return $data;
		}

		if ($data === '') {
		    return $data;
		}

		return str_replace(array("'", '"', '<', '>'), array("&#39;", "&quot;", '&lt;', '&gt;'), stripslashes($data));
	}

	/**
	 * Prep URL
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function prepUrl($str = '')
	{
		if ($str == 'http://' OR $str == '') {
			return '';
		}

		if (substr($str, 0, 7) != 'http://' && substr($str, 0, 8) != 'https://') {
			$str = 'http://'.$str;
		}

		return $str;
	}

	/**
	 * Strip Image Tags
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function stripImageTags($str)
	{
		return Fly::app()->Request->stripImageTags($str);
	}

	/**
	 * XSS Clean
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function cleanXss($str)
	{
		return Fly::app()->Security->cleanXss($str);
	}

	/**
	 * Convert PHP tags to entities
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function encodePhpTags($str)
	{
		return str_replace(array('<?php', '<?PHP', '<?', '?>'),  array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;'), $str);
	}

}
