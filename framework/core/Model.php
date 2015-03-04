<?php
/**
 * @link http://www.flyframework.com/
 * @copyright Copyright &copy; FlyZZ Team
 * @license http://www.flyframework.com/license.html
 * @author zz <zz@flyzz.net>
 */

/**
 * Model is the base class providing the common features needed by data model objects.
 * Model defines the basic framework for data models that need to be validated.
 *
 * @property array $errors Errors for all attributes or the specified attribute. Empty array is returned if no error.
 * @property array $attributes Attribute values (name=>value).
 * @property array $safeAttributeNames Safe attribute names.
 */
class Model extends Component implements IteratorAggregate, ArrayAccess
{
    /**
     * Returns the list of attribute names of the model.
     * @return array list of attribute names.
     */
    public function attributeNames()
    {
        $class = new ReflectionClass($this);
        $names = array();
        foreach ($class->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $names[] = $property->getName();
            }
        }
        return $names;
    }

    /**
     * Returns the validation rules for attributes.
     *
     * This method should be overridden to declare validation rules.
     * The following are some examples:
     * <pre>
     * array(
     *     array('username', 'required', 'message' => 'username error.'),
     *     array('username', 'callback::length'),
     * );
     * </pre>
     *
     * OR
     *
     * <pre>
     * array(
     *  'test'=>array(
     *     array('username', 'required', 'message' => 'username error.'),
     *     array('username', 'callback::length'),
     *  )
     * );
     * </pre>
     *
     * Note, in order to inherit rules defined in the parent class, a child class needs to
     * merge the parent rules with child rules using functions like array_merge().
     *
     * @return array validation rules to be applied when {@link validate()} is called.
     */
    public function rules($group = null)
    {
        return array();
    }

    /**
     * Returns a list of behaviors that this model should behave as.
     * The return value should be an array of behavior configurations indexed by
     * behavior names. Each behavior configuration can be either a string specifying
     * the behavior class or an array of the following structure:
     * <pre>
     * 'behaviorName'=>array(
     *     'class'=>'path.to.BehaviorClass',
     *     'property1'=>'value1',
     *     'property2'=>'value2',
     * )
     * </pre>
     *
     * Note, the behavior classes must implement {@link IBehavior} or extend from
     * {@link Behavior}. Behaviors declared in this method will be attached
     * to the model when it is instantiated.
     *
     * For more details about behaviors, see {@link Component}.
     * @return array the behavior configurations (behavior name=>behavior configuration)
     */
    public function behaviors()
    {
        return array();
    }

    /**
     * Returns the attribute labels.
     * Attribute labels are mainly used in error messages of validation.
     * By default an attribute label is generated using {@link generateAttributeLabel}.
     * This method allows you to explicitly specify attribute labels.
     *
     * Note, in order to inherit labels defined in the parent class, a child class needs to
     * merge the parent labels with child labels using functions like array_merge().
     *
     * @return array attribute labels (name=>label)
     * @see generateAttributeLabel
     */
    public function attributeLabels()
    {
        return array();
    }

    /**
     * Performs the validation.
     *
     * This method executes the validation rules as declared in {@link rules}.
     * Only the rules applicable to the current {@link scenario} will be executed.
     * A rule is considered applicable to a scenario if its 'on' option is not set
     * or contains the scenario.
     *
     * Errors found during the validation can be retrieved via {@link getErrors}.
     *
     * @param array $attributes list of attributes that should be validated. Defaults to null,
     * meaning any attribute listed in the applicable validation rules should be
     * validated. If this parameter is given as a list of attributes, only
     * the listed attributes will be validated.
     * @param boolean $clearErrors whether to call {@link clearErrors} before performing validation
     * @return boolean whether the validation is successful without any error.
     * @see beforeValidate
     * @see afterValidate
     */
    public function validate($attributes = null, $group = null, $clearErrors = true)
    {
        if ($clearErrors) {
            $this->clearErrors();
        }
        if ($this->beforeValidate()) {
            Fly::app()->getValidator()->setRules($this->rules($group));
            Fly::app()->getValidator()->setAttributes($attributes, $this->attributeLabels());
            Fly::app()->getValidator()->run($this);
            $this->afterValidate();
            return !$this->hasErrors();
        }
        else
            return false;
    }

    /**
     * This method is invoked after a model instance is created by new operator.
     * The default implementation raises the {@link onAfterConstruct} event.
     * You may override this method to do postprocessing after model creation.
     * Make sure you call the parent implementation so that the event is raised properly.
     */
    protected function afterConstruct()
    {
        if ($this->hasEventHandler('onAfterConstruct')) {
            $this->onAfterConstruct(new Event($this));
        }
    }

    /**
     * This method is invoked before validation starts.
     * The default implementation calls {@link onBeforeValidate} to raise an event.
     * You may override this method to do preliminary checks before validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     * @return boolean whether validation should be executed. Defaults to true.
     * If false is returned, the validation will stop and the model is considered invalid.
     */
    protected function beforeValidate()
    {
        $event=new ModelEvent($this);
        $this->onBeforeValidate($event);
        return $event->isValid;
    }

    /**
     * This method is invoked after validation ends.
     * The default implementation calls {@link onAfterValidate} to raise an event.
     * You may override this method to do postprocessing after validation.
     * Make sure the parent implementation is invoked so that the event can be raised.
     */
    protected function afterValidate()
    {
        $this->onAfterValidate(new Event($this));
    }

    /**
     * This event is raised after the model instance is created by new operator.
     * @param Event $event the event parameter
     */
    public function onAfterConstruct($event)
    {
        $this->trigger('onAfterConstruct', $event);
    }

    /**
     * This event is raised before the validation is performed.
     * @param ModelEvent $event the event parameter
     */
    public function onBeforeValidate($event)
    {
        $this->trigger('onBeforeValidate', $event);
    }

    /**
     * This event is raised after the validation is performed.
     * @param Event $event the event parameter
     */
    public function onAfterValidate($event)
    {
        $this->trigger('onAfterValidate', $event);
    }

    /**
     * Returns the text label for the specified attribute.
     * @param string $attribute the attribute name
     * @return string the attribute label
     * @see generateAttributeLabel
     * @see attributeLabels
     */
    public function getAttributeLabel($attribute)
    {
        $labels = $this->attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        } else {
            return $this->generateAttributeLabel($attribute);
        }
    }

    /**
     * Returns a value indicating whether there is any validation error.
     * @param string $attribute attribute name. Use null to check all attributes.
     * @return boolean whether there is any error.
     */
    public function hasErrors($attribute = null)
    {
        return Fly::app()->getValidator()->hasErrors($attribute);
    }

    /**
     * Returns the errors for all attribute or a single attribute.
     * @param string $attribute attribute name. Use null to retrieve errors for all attributes.
     * @return array errors for all attributes or the specified attribute. Empty array is returned if no error.
     */
    public function getErrors($attribute = null)
    {
        return Fly::app()->getValidator()->getErrors($attribute);
    }

    /**
     * Returns the first error of the specified attribute.
     * @param string $attribute attribute name.
     * @return string the error message. Null is returned if no error.
     */
    public function getError($attribute)
    {
        return Fly::app()->getValidator()->getError($attribute);
    }

    /**
     * Adds a new error to the specified attribute.
     * @param string $attribute attribute name
     * @param string $error new error message
     */
    public function addError($attribute, $error)
    {
        Fly::app()->getValidator()->addError($attribute, $error);
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
        Fly::app()->getValidator()->addErrors($errors);
    }

    /**
     * Removes errors for all attributes or a single attribute.
     * @param string $attribute attribute name. Use null to remove errors for all attribute.
     */
    public function clearErrors($attribute = null)
    {
        Fly::app()->getValidator()->clearErrors($attribute);
    }

    /**
     * Generates a user friendly attribute label.
     * This is done by replacing underscores or dashes with blanks and
     * changing the first letter of each word to upper case.
     * For example, 'department_name' or 'DepartmentName' becomes 'Department Name'.
     * @param string $name the column name
     * @return string the attribute label
     */
    public function generateAttributeLabel($name)
    {
        return ucwords(trim(strtolower(str_replace(array('-','_','.'),' ',preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $name)))));
    }

    /**
     * Returns all attribute values.
     * @param array $names list of attributes whose value needs to be returned.
     * Defaults to null, meaning all attributes as listed in {@link attributeNames} will be returned.
     * If it is an array, only the attributes in the array will be returned.
     * @return array attribute values (name=>value).
     */
    public function getAttributes($names = null)
    {
        $values = array();
        foreach ($this->attributeNames() as $name) {
            $values[$name] = $this->$name;
        }

        if (is_array($names)) {
            $values2 = array();
            foreach ($names as $name) {
                $values2[$name] = isset($values[$name]) ? $values[$name] : null;
            }
            return $values2;
        } else {
            return $values;
        }
    }

    /**
     * Sets the attribute values in a massive way.
     * @param array $values attribute values (name=>value) to be set.
     * @param boolean $safeOnly whether the assignments should only be done to the safe attributes.
     * A safe attribute is one that is associated with a validation rule in the current {@link scenario}.
     * @see getSafeAttributeNames
     * @see attributeNames
     */
    public function setAttributes($values)
    {
        if(!is_array($values))
            return;
        $attributes = array_flip($this->attributeNames());
        foreach ($values as $name=>$value) {
            if (isset($attributes[$name])) {
                $this->$name=$value;
            }
        }
    }

    /**
     * Sets the attributes to be null.
     * @param array $names list of attributes to be set null. If this parameter is not given,
     * all attributes as specified by {@link attributeNames} will have their values unset.
     */
    public function unsetAttributes($names=null)
    {
        if ($names === null) {
            $names = $this->attributeNames();
        }
        foreach($names as $name) {
            $this->$name = null;
        }
    }

    /**
     * This method is invoked when an unsafe attribute is being massively assigned.
     * The default implementation will log a warning message if YII_DEBUG is on.
     * It does nothing otherwise.
     * @param string $name the unsafe attribute name
     * @param mixed $value the attribute value
     * @since 1.1.1
     */
    public function onUnsafeAttribute($name, $value)
    {
        if (FLY_DEBUG) {
            Fly::log(Fly::t('yii','Failed to set unsafe attribute "{attribute}" of "{class}".',array('{attribute}'=>$name, '{class}'=>get_class($this))), 'error');

        }
    }

    /**
     * Returns an iterator for traversing the attributes in the model.
     * This method is required by the interface IteratorAggregate.
     * @return ArrayIterator an iterator for traversing the items in the list.
     */
    public function getIterator()
    {
        $attributes = $this->getAttributes();
        return new ArrayIterator($attributes);
    }

    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param mixed $offset the offset to check on
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return property_exists($this, $offset);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param integer $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset,$item)
    {
        $this->$offset = $item;
    }

    /**
     * Unsets the element at the specified offset.
     * This method is required by the interface ArrayAccess.
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }
}
