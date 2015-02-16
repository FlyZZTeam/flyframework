<?php
/**
 * Created by PhpStorm.
 * User: geet
 * Date: 15/2/10
 * Time: 上午11:00
 */

class TestBehavior extends Behavior
{
    /**
     * Declares events and the corresponding event handler methods.
     * The default implementation returns 'onAfterConstruct', 'onBeforeValidate' and 'onAfterValidate' events and handlers.
     * If you override this method, make sure you merge the parent result to the return value.
     * @return array events (array keys) and the corresponding event handler methods (array values).
     * @see CBehavior::events
     */
    public function events()
    {
        return array(
            'onBeginRequest'=>'onBeginRequest',
        );
    }

    public function onBeginRequest($event)
    {
        echo "hello";
    }
}