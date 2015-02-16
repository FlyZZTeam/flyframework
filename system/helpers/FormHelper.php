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
 * CodeIgniter Form Helpers
 *
 * @package		CodeIgniter
 * @subpackage	Helpers
 * @category	Helpers
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/helpers/form_helper.html
 */

class FormHelper
{

    /**
     * Form Declaration
     *
     * Creates the opening portion of the form.
     *
     * @access	public
     * @param	string	the URI segments of the form destination
     * @param	array	a key/value pair of attributes
     * @param	array	a key/value pair hidden data
     * @return	string
     */
    public static function createForm($action = '', $attributes = '', $hidden = array())
    {
        if ($attributes == '') {
            $attributes = 'method="post"';
        }

        $form = '<form action="'.$action.'"';

        $form .= self::attributesToString($attributes, true);

        $form .= '>';

        // Add CSRF field if enabled, but leave it out for GET requests and requests to external websites
        if (Fly::getConfig('csrf_protection') === true && !(strpos($action, Fly::app()->Request->getBaseUrl(true)) === false || strpos($form, 'method="get"'))) {
            $hidden[Fly::app()->Security->getCsrfTokenName()] = Fly::app()->Security->getCsrfHash();
        }

        if (is_array($hidden) && count($hidden) > 0) {
            $form .= sprintf("<div style=\"display:none\">%s</div>", self::createHidden($hidden));
        }

        return $form;
    }

    /**
     * Form Declaration - Multipart type
     *
     * Creates the opening portion of the form, but with "multipart/form-data".
     *
     * @access	public
     * @param	string	the URI segments of the form destination
     * @param	array	a key/value pair of attributes
     * @param	array	a key/value pair hidden data
     * @return	string
     */
    public static function createMultipartForm($action = '', $attributes = array(), $hidden = array())
    {
        if (is_string($attributes)) {
            $attributes .= ' enctype="multipart/form-data"';
        } else {
            $attributes['enctype'] = 'multipart/form-data';
        }

        return self::createForm($action, $attributes, $hidden);
    }

    /**
     * Hidden Input Field
     *
     * Generates hidden fields.  You can pass a simple key/value string or an associative
     * array with multiple values.
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @return	string
     */
    public static function createHidden($name, $value = '', $recursing = false)
    {
        static $form;

        if ($recursing === false) {
            $form = "\n";
        }

        if (is_array($name)) {
            foreach ($name as $key => $val) {
                self::createHidden($key, $val, true);
            }
            return $form;
        }

        if (!is_array($value)) {
            $form .= '<input type="hidden" name="'.$name.'" value="'.self::prep($value, $name).'" />'."\n";
        } else {
            foreach ($value as $k => $v) {
                $k = (is_int($k)) ? '' : $k;
                self::createHidden($name.'['.$k.']', $v, TRUE);
            }
        }
        return $form;
    }

    /**
     * Text Input Field
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createInput($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'text', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);

        return "<input ".self::parseFormAttributes($data, $defaults).$extra." />";
    }

    /**
     * Password Field
     *
     * Identical to the input function but adds the "password" type
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createPassword($data = '', $value = '', $extra = '')
    {
        if (!is_array($data)) {
            $data = array('name' => $data);
        }

        $data['type'] = 'password';
        return self::createInput($data, $value, $extra);
    }

    /**
     * Upload Field
     *
     * Identical to the input function but adds the "file" type
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */

    public static function createUpload($data = '', $value = '', $extra = '')
    {
        if (!is_array($data)) {
            $data = array('name' => $data);
        }
        $data['type'] = 'file';
        return self::createInput($data, $value, $extra);
    }

    /**
     * Textarea field
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createTextarea($data = '', $value = '', $extra = '')
    {
        $defaults = array('name' => (( ! is_array($data)) ? $data : ''), 'cols' => '40', 'rows' => '10');

        if (!is_array($data) && !isset($data['value'])) {
            $val = $value;
        } else {
            $val = $data['value'];
            unset($data['value']); // textareas don't use the value attribute
        }
        $name = (is_array($data)) ? $data['name'] : $data;
        return "<textarea ".self::parseFormAttributes($data, $defaults).$extra.">".self::prep($val, $name)."</textarea>";
    }

    /**
     * Multi-select menu
     *
     * @access	public
     * @param	string
     * @param	array
     * @param	mixed
     * @param	string
     * @return	type
     */
    public static function createMultiSelect($name = '', $options = array(), $selected = array(), $extra = '')
    {
        if (!strpos($extra, 'multiple')) {
            $extra .= ' multiple="multiple"';
        }
        return self::createDropdown($name, $options, $selected, $extra);
    }

    /**
     * Drop-down Menu
     *
     * @access	public
     * @param	string
     * @param	array
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createDropdown($name = '', $options = array(), $selected = array(), $extra = '')
    {
        if (!is_array($selected)) {
            $selected = array($selected);
        }

        // If no selected state was submitted we will attempt to set it automatically
        if (count($selected) === 0) {
            // If the form name appears in the $_POST array we have a winner!
            if (isset($_POST[$name])) {
                $selected = array($_POST[$name]);
            }
        }

        if ($extra != '') $extra = ' '.$extra;

        $multiple = (count($selected) > 1 && strpos($extra, 'multiple') === FALSE) ? ' multiple="multiple"' : '';

        $form = '<select name="'.$name.'"'.$extra.$multiple.">\n";

        foreach ($options as $key => $val) {
            $key = (string) $key;

            if (is_array($val) && ! empty($val)) {
                $form .= '<optgroup label="'.$key.'">'."\n";

                foreach ($val as $optgroup_key => $optgroup_val) {
                    $sel = (in_array($optgroup_key, $selected)) ? ' selected="selected"' : '';

                    $form .= '<option value="'.$optgroup_key.'"'.$sel.'>'.(string) $optgroup_val."</option>\n";
                }

                $form .= '</optgroup>'."\n";
            } else {
                $sel = (in_array($key, $selected)) ? ' selected="selected"' : '';

                $form .= '<option value="'.$key.'"'.$sel.'>'.(string) $val."</option>\n";
            }
        }

        $form .= '</select>';

        return $form;
    }

    /**
     * Checkbox Field
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	bool
     * @param	string
     * @return	string
     */
    public static function createCheckbox($data = '', $value = '', $checked = FALSE, $extra = '')
    {
        $defaults = array('type' => 'checkbox', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);

        if (is_array($data) && array_key_exists('checked', $data)) {
            $checked = $data['checked'];

            if ($checked == false) {
                unset($data['checked']);
            } else {
                $data['checked'] = 'checked';
            }
        }

        if ($checked == true) {
            $defaults['checked'] = 'checked';
        } else {
            unset($defaults['checked']);
        }

        return "<input ".self::parseFormAttributes($data, $defaults).$extra." />";
    }

    /**
     * Radio Button
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	bool
     * @param	string
     * @return	string
     */
    public static function createRadio($data = '', $value = '', $checked = false, $extra = '')
    {
        if (!is_array($data)) {
            $data = array('name' => $data);
        }
        $data['type'] = 'radio';
        return self::createCheckbox($data, $value, $checked, $extra);
    }

    /**
     * Submit Button
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createSubmit($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'submit', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);

        return "<input ".self::parseFormAttributes($data, $defaults).$extra." />";
    }

    /**
     * Reset Button
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createReset($data = '', $value = '', $extra = '')
    {
        $defaults = array('type' => 'reset', 'name' => (( ! is_array($data)) ? $data : ''), 'value' => $value);

        return "<input ".self::parseFormAttributes($data, $defaults).$extra." />";
    }

    /**
     * Form Button
     *
     * @access	public
     * @param	mixed
     * @param	string
     * @param	string
     * @return	string
     */
    public static function createButton($data = '', $content = '', $extra = '')
    {
        $defaults = array('name' => (( ! is_array($data)) ? $data : ''), 'type' => 'button');

        if ( is_array($data) AND isset($data['content'])) {
            $content = $data['content'];
            unset($data['content']); // content is not an attribute
        }

        return "<button ".self::parseFormAttributes($data, $defaults).$extra.">".$content."</button>";
    }

    /**
     * Form Label Tag
     *
     * @access	public
     * @param	string	The text to appear onscreen
     * @param	string	The id the label applies to
     * @param	string	Additional attributes
     * @return	string
     */
    public static function createLabel($label_text = '', $id = '', $attributes = array())
    {

        $label = '<label';

        if ($id != '') {
            $label .= " for=\"$id\"";
        }

        if (is_array($attributes) AND count($attributes) > 0) {
            foreach ($attributes as $key => $val) {
                $label .= ' '.$key.'="'.$val.'"';
            }
        }

        $label .= ">$label_text</label>";
        return $label;
    }

    /**
     * Fieldset Tag
     *
     * Used to produce <fieldset><legend>text</legend>.  To close fieldset
     * use form_fieldset_close()
     *
     * @access	public
     * @param	string	The legend text
     * @param	string	Additional attributes
     * @return	string
     */
    public static function createFieldset($legend_text = '', $attributes = array())
    {
        $fieldset = "<fieldset";

        $fieldset .= self::attributesToString($attributes, FALSE);

        $fieldset .= ">\n";

        if ($legend_text != '') {
            $fieldset .= "<legend>$legend_text</legend>\n";
        }
        return $fieldset;
    }

    /**
     * Fieldset Close Tag
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function createFieldsetCloseTag($extra = '')
    {
        return "</fieldset>".$extra;
    }

    /**
     * Form Close Tag
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public function createFormCloseTag($extra = '')
    {
        return "</form>".$extra;
    }

    /**
     * Form Prep
     *
     * Formats text so that it can be safely placed in a form field in the event it has HTML tags.
     *
     * @access	public
     * @param	string
     * @return	string
     */
    public static function prep($str = '', $field_name = '')
    {
        static $prepped_fields = array();

        // if the field name is an array we do this recursively
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $str[$key] = self::prep($val);
            }
            return $str;
        }

        if ($str === '') {
            return '';
        }

        // we've already prepped a field with this name
        // @todo need to figure out a way to namespace this so
        // that we know the *exact* field and not just one with
        // the same name
        if (isset($prepped_fields[$field_name])) {
            return $str;
        }

        $str = htmlspecialchars($str);

        // In case htmlspecialchars misses these.
        $str = str_replace(array("'", '"'), array("&#39;", "&quot;"), $str);

        if ($field_name != '') {
            $prepped_fields[$field_name] = $field_name;
        }

        return $str;
    }

    /**
     * Form Value
     *
     * Grabs a value from the POST array for the specified field so you can
     * re-populate an input field or textarea.  If Form Validation
     * is active it retrieves the info from the validation class
     *
     * @access	public
     * @param	string
     * @return	mixed
     */
    public static function setValue($field = '', $default = '')
    {
        if (FALSE === ($validation =& self::getValidation())) {
            if (!isset($_POST[$field])) {
                return $default;
            }
            return self::prep($_POST[$field], $field);
        }

        return self::prep($validation->setValue($field, $default), $field);
    }

    /**
     * Set Select
     *
     * Let's you set the selected value of a <select> menu via data in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	bool
     * @return	string
     */
    public static function setSelect($field = '', $value = '', $default = FALSE)
    {
        $validation =& self::getValidation();

        if ($validation === FALSE) {
            if (!isset($_POST[$field])) {
                if (count($_POST) === 0 && $default == TRUE) {
                    return ' selected="selected"';
                }
                return '';
            }

            $field = $_POST[$field];

            if (is_array($field)) {
                if (!in_array($value, $field)) {
                    return '';
                }
            } else {
                if (($field == '' || $value == '') || ($field != $value)) {
                    return '';
                }
            }

            return ' selected="selected"';
        }

        return $validation->setSelect($field, $value, $default);
    }

    /**
     * Set Checkbox
     *
     * Let's you set the selected value of a checkbox via the value in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	bool
     * @return	string
     */
    public static function setCheckbox($field = '', $value = '', $default = FALSE)
    {
        $validation =& self::getValidation();

        if ($validation === FALSE) {
            if (!isset($_POST[$field])) {
                if (count($_POST) === 0 && $default == TRUE) {
                    return ' checked="checked"';
                }
                return '';
            }

            $field = $_POST[$field];

            if (is_array($field)) {
                if (!in_array($value, $field)) {
                    return '';
                }
            } else {
                if (($field == '' || $value == '') || ($field != $value)) {
                    return '';
                }
            }
            return ' checked="checked"';
        }

        return $validation->setCheckbox($field, $value, $default);
    }

    /**
     * Set Radio
     *
     * Let's you set the selected value of a radio field via info in the POST array.
     * If Form Validation is active it retrieves the info from the validation class
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	bool
     * @return	string
     */
    public static function setRadio($field = '', $value = '', $default = FALSE)
    {
        $validation =& self::getValidation();

        if ($validation === FALSE) {
            if (!isset($_POST[$field])) {
                if (count($_POST) === 0 && $default == true) {
                    return ' checked="checked"';
                }
                return '';
            }

            $field = $_POST[$field];

            if (is_array($field)) {
                if (!in_array($value, $field)) {
                    return '';
                }
            } else {
                if (($field == '' || $value == '') || ($field != $value)) {
                    return '';
                }
            }
            return ' checked="checked"';
        }

        return $validation->setRadio($field, $value, $default);
    }

    /**
     * Form Error
     *
     * Returns the error for a specific form field.  This is a helper for the
     * form validation class.
     *
     * @access	public
     * @param	string
     * @param	string
     * @param	string
     * @return	string
     */
    public static function error($field = '', $prefix = '', $suffix = '')
    {
        if (FALSE === ($validation =& self::getValidation())) {
            return '';
        }

        return $validation->error($field, $prefix, $suffix);
    }

    /**
     * Validation Error String
     *
     * Returns all the errors associated with a form submission.  This is a helper
     * function for the form validation class.
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	string
     */
    public static function validationErrors($prefix = '', $suffix = '')
    {
        if (FALSE === ($validation =& self::getValidation())) {
            return '';
        }

        return $validation->errorString($prefix, $suffix);
    }

    /**
     * Parse the form attributes
     *
     * Helper function used by some of the form helpers
     *
     * @access	private
     * @param	array
     * @param	array
     * @return	string
     */
    public static function parseFormAttributes($attributes, $default)
    {
        if (is_array($attributes)) {
            foreach ($default as $key => $val) {
                if (isset($attributes[$key])) {
                    $default[$key] = $attributes[$key];
                    unset($attributes[$key]);
                }
            }

            if (count($attributes) > 0) {
                $default = array_merge($default, $attributes);
            }
        }

        $att = '';

        foreach ($default as $key => $val) {
            if ($key == 'value') {
                $val = self::prep($val, $default['name']);
            }
            $att .= $key . '="' . $val . '" ';
        }
        return $att;
    }

    /**
     * Attributes To String
     *
     * Helper function used by some of the form helpers
     *
     * @access	private
     * @param	mixed
     * @param	bool
     * @return	string
     */
    public static function attributesToString($attributes, $formtag = false)
    {
        if (is_string($attributes) && strlen($attributes) > 0) {
            if ($formtag == true && strpos($attributes, 'method=') === false) {
                $attributes .= ' method="post"';
            }

            if ($formtag == true && strpos($attributes, 'accept-charset=') === false) {
                $attributes .= ' accept-charset="'.strtolower(Fly::getConfig('charset')).'"';
            }

            return ' '.$attributes;
        }

        if (is_object($attributes) && count($attributes) > 0) {
            $attributes = (array)$attributes;
        }

        if (is_array($attributes) && count($attributes) > 0) {
            $atts = '';

            if (!isset($attributes['method']) && $formtag === true) {
                $atts .= ' method="post"';
            }

            if (!isset($attributes['accept-charset']) && $formtag === true) {
                $atts .= ' accept-charset="'.strtolower(Fly::getConfig('charset')).'"';
            }

            foreach ($attributes as $key => $val) {
                $atts .= ' '.$key.'="'.$val.'"';
            }
            return $atts;
        }
    }

    /**
     * Validation Object
     *
     * Determines what the form validation class was instantiated as, fetches
     * the object and returns it.
     *
     * @access	private
     * @return	mixed
     */
    public static function &getValidation()
    {
        $return = Fly::app()->getValidation();
        return $return;
    }

}
