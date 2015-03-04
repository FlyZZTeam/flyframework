<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Form Validation Class
 */
class Validator extends Component
{
    public static $builtInValidators = array();

    protected $_rules = array();
    protected $_errors = array();
    protected $_errorMessages = array();
    public $attributes = array();
    public $attributeLabels = array();

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
     * @param $data
     */
    public function setAttributes($data, $attributeLabels = array())
    {
        $this->attributes = $data;
        $this->attributeLabels = $attributeLabels;
    }

    /**
     * Set Rules
     *
     * This function takes an array of field names and validation
     * rules as input, validates the info, and stores it
     *
     * @param mixed
     * @param string
     * @return void
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
     * @param string
     * @param string
     * @return string
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
                foreach ($error as $e) {
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
     * @param string
     * @param string
     * @return str
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
     * @return bool
     */
    public function run($context = null)
    {
        if (count($this->attributes) == 0) {
            return false;
        }

        // Does the _field_data array containing the validation rules exist?
        // If not, we look to see if they were assigned via a config file
        if (count($this->_rules) == 0) {
            return true;
        }

        foreach ($this->_rules as $row) {
            $type = $row[1];
            if (preg_match("/^validator:([\w\W]+)/", $type)) {
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
            return true;
        }

        return false;
    }

    /**
     * Execute a validator engine.
     * @param $rule
     * @param null $model
     */
    protected function _executeValidatorEngine($rule, $model = null)
    {
        $type = str_replace('validator:', '', $rule[1]);
        $validator = self::createValidator($type, $model, $rule[0]);
        $attributes = array();
        if (is_string($rule[0])) {
            $attributes = preg_split('/[\s,]+/', $rule[0], -1, PREG_SPLIT_NO_EMPTY);
        }
        $data = array();
        $labels = array();
        foreach ($attributes as $val) {
            if (isset($this->attributes[$val])) {
                $data[$val] = $this->attributes[$val];
            }
            if (isset($this->attributeLabels[$val])) {
                $labels[$val] = $this->attributeLabels[$val];
            }
        }
        $validator->setAttributes($data, $labels);
        foreach ($attributes as $val) {
            $validator->validateAttribute($model, $val);
            if ($validator->hasErrors($val)) {
                $this->addError($val, $validator->getError($val));
                if ($this->skipOnError) {
                    break;
                }
            }
        }
    }

    /**
     * Executes the InLineEngine
     * @param array
     * @param array
     * @param mixed
     * @param integer
     * @return mixed
     */
    protected function _executeInLineEngine($row, $context = null)
    {
        $rules = explode('|', $row[1]);
        $attributeData = array();
        if (isset($this->attributes[$row[0]])) {
            $data = $this->attributes[$row[0]];
            if (is_array($data)) {
                $attributeData = $data;
            } else {
                $attributeData[] = $data;
            }
        }
        $attributeLabel = '';
        if (isset($this->attributeLabels[$row[0]])) {
            $attributeLabel = $this->attributeLabels[$row[0]];
        }

        foreach ($attributeData as $item) {
            // If the field is blank, but NOT required, no further tests are necessary
            $callback = false;
            $postdata = $item;
            if (!in_array('required', $rules) && is_null($postdata)) {
                // Before we bail out, does the rule contain a callback?
                if (preg_match("/(callback:\w+(\[.*?\])?)/", implode(' ', $rules), $match)) {
                    $callback = true;
                    $rules = (array('1' => $match[1]));
                } else {
                    return;
                }
            }

            // Isset Test. Typically this rule will only apply to checkboxes.
            if (is_null($postdata) AND $callback == false) {
                if (in_array('isset', $rules, true) OR in_array('required', $rules)) {
                    // Set the message type
                    $type = (in_array('required', $rules)) ? 'required' : 'isset';

                    $message = $this->createErrorMessage($type, $row[0]);

                    $this->addError($row[0], $message);
                }

                return;
            }

            foreach ($rules As $rule) {

                // Is the rule a callback?
                $callback = false;
                if (substr($rule, 0, 9) == 'callback:') {
                    $rule = substr($rule, 9);
                    $callback = true;
                }

                // Strip the parameter (if exists) from the rule
                // Rules can contain a parameter: max_length[5]
                $param = false;
                if (preg_match("/(.*?)\[(.*)\]/", $rule, $match)) {
                    $rule = $match[1];
                    $param = $match[2];
                }

                // Call the function that corresponds to the rule
                if ($callback === true) {
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
                    if (!in_array('required', $rules, true) AND $result !== false) {
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
                if ($result === false) {
                    $newParams = array();
                    if ($param && is_array($param)) {
                        $count = 1;
                        foreach ($param as $val) {
                            $newParams['{param'.$count.'}'] = isset($this->attributeLabels[$val]) ? $this->attributeLabels[$val] : $val;
                            $count++;
                        }
                    }
                    $message = isset($row['message']) ? $row['message'] : '';
                    $message = $this->createErrorMessage($rule, $row[0], $message, $newParams);
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
        if (method_exists($model, $type)) {
            $validator = new InlineValidator;
            $validator->attributes = $attributes;
            $validator->method = $type;
            $validator->params = $params;
            if (isset($params['skipOnError'])) {
                $validator->skipOnError = $params['skipOnError'];
            }
        } else {
            //$params['attributes'] = $attributes;
            if (isset(self::$builtInValidators[$type])) {
                $className = Fly::import(self::$builtInValidators[$type], true);
            } else {
                $className = Fly::import($type, true);
            }
            $validator = new $className;
            foreach ($params as $name => $value) {
                $validator->$name = $value;
            }
        }
        return $validator;
    }

    /**
     * Create error message.
     *
     * @param $attribute string
     * @param $message string
     * @param array $params array
     */
    protected function createErrorMessage($rule, $attribute = '', $message = '', $params = array())
    {
        $params['{attribute}'] = $attribute;
        if (isset($this->attributeLabels[$attribute])) {
            if ($this->attributeLabels[$attribute] !== '') {
                $params['{attribute}'] = $this->attributeLabels[$attribute];
            }
        }

        if (empty($message)) {
            if (!isset($this->_errorMessages[$rule])) {
                if (false === ($line = Fly::t('form_validation', $rule))) {
                    $line = 'Unable to access an error message corresponding to your field name.';
                }
            } else {
                $line = $this->_errorMessages[$rule];
            }
        } else {
            $line = $message;
        }
        $message = strtr($line, $params);
        return $message;
    }

    /**
     * Validates a single attribute.
     * Child classes must implement this method to provide the actual validation logic.
     * @param Model $model the data model to be validated
     * @param string $attribute the name of the attribute to be validated.
     */
    public function validateAttribute($model, $attribute)
    {
        if (isset($this->attributes[$attribute])) {
            $val = $this->attributes[$attribute];
        } else {
            $val = $model->$attribute;
        }
        $result = $this->validateValue($val);
        if (!empty($result)) {
            $className = str_replace('validator', '', get_class());
            $message = $this->createErrorMessage($className, $attribute, $result[0], $result[1]);
            $this->addError($attribute, $message);
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
        throw new FlyException(get_class($this).' does not support validateValue().');
    }

    /**
     * Required
     * @param string
     * @return bool
     */
    public function required($str)
    {
        if (!is_array($str)) {
            return (trim($str) == '') ? false : true;
        } else {
            return (!empty($str));
        }
    }

    /**
     * Performs a Regular Expression match test.
     * @param string
     * @param regex
     * @return bool
     */
    public function regexMatch($str, $regex)
    {
        if (!preg_match($regex, $str)) {
            return false;
        }
        return true;
    }

    /**
     * Match one field to another
     * @param string
     * @param field
     * @return bool
     */
    public function matches($str, $field)
    {
        if (!isset($this->attributes[$field])) {
            return false;
        }

        $field = $this->attributes[$field];

        return ($str !== $field) ? false : true;
    }

    /**
     * Match one field to another
     * @param string
     * @param field
     * @return bool
     */
    public function isUnique($str, $field)
    {
        list($table, $field) = explode('.', $field);
        $query = Fly::app()->db->limit(1)->getWhere($table, array($field => $str));

        return $query->getRowsCount() === 0;
    }

    /**
     * Minimum Length
     * @param string
     * @param value
     * @return bool
     */
    public function minLength($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        if (function_exists('mb_strlen')) {
            return (mb_strlen($str) < $val) ? false : true;
        }

        return (strlen($str) < $val) ? false : true;
    }

    /**
     * Max Length
     * @param string
     * @param value
     * @return bool
     */
    public function maxLength($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        if (function_exists('mb_strlen')) {
            return (mb_strlen($str) > $val) ? false : true;
        }

        return (strlen($str) > $val) ? false : true;
    }

    /**
     * Exact Length
     * @param string
     * @param value
     * @return bool
     */
    public function exactLength($str, $val)
    {
        if (preg_match("/[^0-9]/", $val)) {
            return false;
        }

        if (function_exists('mb_strlen')) {
            return (mb_strlen($str) != $val) ? false : true;
        }

        return (strlen($str) != $val) ? false : true;
    }

    /**
     * Valid Email
     * @param string
     * @return bool
     */
    public function validEmail($str)
    {
        return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $str)) ? false : true;
    }

    /**
     * Valid Emails
     * @param string
     * @return bool
     */
    public function validEmails($str)
    {
        if (strpos($str, ',') === false) {
            return $this->validEmail(trim($str));
        }

        foreach (explode(',', $str) as $email) {
            if (trim($email) != '' && $this->validEmail(trim($email)) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate IP Address
     * @param string
     * @param string "ipv4" or "ipv6" to validate a specific ip format
     * @return string
     */
    public function validIp($ip, $which = '')
    {
        return Fly::app()->Request->validIp($ip, $which);
    }

    /**
     * Alpha
     * @param string
     * @return bool
     */
    public function alpha($str)
    {
        return (!preg_match("/^([a-z])+$/i", $str)) ? false : true;
    }

    /**
     * Alpha-numeric
     * @param string
     * @return bool
     */
    public function alphaNumeric($str)
    {
        return (!preg_match("/^([a-z0-9])+$/i", $str)) ? false : true;
    }

    /**
     * Alpha-numeric with underscores and dashes
     * @param string
     * @return bool
     */
    public function alphaDash($str)
    {
        return (!preg_match("/^([-a-z0-9_-])+$/i", $str)) ? false : true;
    }

    /**
     * Numeric
     * @param string
     * @return bool
     */
    public function numeric($str)
    {
        return (bool)preg_match('/^[\-+]?[0-9]*\.?[0-9]+$/', $str);
    }

    /**
     * Is Numeric
     * @param string
     * @return bool
     */
    public function isNumeric($str)
    {
        return (!is_numeric($str)) ? false : true;
    }

    /**
     * Integer
     * @param string
     * @return bool
     */
    public function integer($str)
    {
        return (bool)preg_match('/^[\-+]?[0-9]+$/', $str);
    }

    /**
     * Decimal number
     * @param string
     * @return bool
     */
    public function decimal($str)
    {
        return (bool)preg_match('/^[\-+]?[0-9]+\.[0-9]+$/', $str);
    }

    /**
     * Greather than
     * @param string
     * @return bool
     */
    public function greaterThan($str, $min)
    {
        if (!is_numeric($str)) {
            return false;
        }
        return $str > $min;
    }

    /**
     * Less than
     * @param string
     * @return bool
     */
    public function lessThan($str, $max)
    {
        if (!is_numeric($str)) {
            return false;
        }
        return $str < $max;
    }

    /**
     * Is a Natural number  (0,1,2,3, etc.)
     * @param string
     * @return bool
     */
    public function isNatural($str)
    {
        return (bool)preg_match('/^[0-9]+$/', $str);
    }

    /**
     * Is a Natural number, but not a zero  (1,2,3, etc.)
     * @param string
     * @return bool
     */
    public function isNaturalNoZero($str)
    {
        if (!preg_match('/^[0-9]+$/', $str)) {
            return false;
        }

        if ($str == 0) {
            return false;
        }

        return true;
    }

    /**
     * Valid Base64
     *
     * Tests a string for characters outside of the Base64 alphabet
     * as defined by RFC 2045 http://www.faqs.org/rfcs/rfc2045
     *
     * @param string
     * @return bool
     */
    public function validBase64($str)
    {
        return (bool)!preg_match('/[^a-zA-Z0-9\/\+=]/', $str);
    }
}
