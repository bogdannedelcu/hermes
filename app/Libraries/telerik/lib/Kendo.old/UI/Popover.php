<?php

namespace Kendo\UI;

class Popover extends \Kendo\UI\Widget {
    public function name() {
        return 'Popover';
    }
    public function html() {
        //popover does not render content
    }

    public function script($executeOnDomReady = true) {
        $script = array();

        if ($executeOnDomReady) {
            $script[] = 'jQuery(function(){';
        }

        //popover should have a selector instead of id
        $script[] = 'jQuery("';
        $script[] = $this->id;
        $script[] = '").kendo';
        $script[] = $this->name();
        $script[] = '(';
        $script[] = $this->toJSON();
        $script[] = ');';

        if ($executeOnDomReady) {
            $script[] = '});';
        }

        return implode($script);
    }

//>> Properties

    /**
    * Adds PopoverAction to the Popover.
    * @param \Kendo\UI\PopoverAction|array,... $value one or more PopoverAction to add.
    * @return \Kendo\UI\Popover
    */
    public function addAction($value) {
        return $this->add('actions', func_get_args());
    }

    /**
    * A value indicating how the actions buttons will be positioned. Possible values are: start; center; between; around or evenly.
    * @param string $value
    * @return \Kendo\UI\Popover
    */
    public function actionsLayout($value) {
        return $this->setProperty('actionsLayout', $value);
    }

    /**
    * A collection of {Animation} objects which are used to change the default animations. If set to false, all widget animations will be disabled. animation:true is not a valid configuration.
    * @param boolean|\Kendo\UI\PopoverAnimation|array $value
    * @return \Kendo\UI\Popover
    */
    public function animation($value) {
        return $this->setProperty('animation', $value);
    }

    /**
    * Defines a kendo template that will be used as the card body inside the popover component.
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\Popover
    */
    public function body($value) {
        return $this->setProperty('body', $value);
    }

    /**
    * Specifies a selector for the elements within the container which will display the Popover.
    * @param string $value
    * @return \Kendo\UI\Popover
    */
    public function filter($value) {
        return $this->setProperty('filter', $value);
    }

    /**
    * Defines a kendo template that will be used as the card header inside the popover component.
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\Popover
    */
    public function header($value) {
        return $this->setProperty('header', $value);
    }

    /**
    * The height (in pixels) of the Popover.
    * @param float $value
    * @return \Kendo\UI\Popover
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Defines a value indicating whether the popover will show/hide only when clicking on the target element.
    * @param boolean $value
    * @return \Kendo\UI\Popover
    */
    public function toggleOnClick($value) {
        return $this->setProperty('toggleOnClick', $value);
    }

    /**
    * The width (in pixels) of the Popover.
    * @param float $value
    * @return \Kendo\UI\Popover
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * The position that is relative to the target element at which the Popover will be shown.The supported values are: bottom; top; left; right or center.
    * @param string $value
    * @return \Kendo\UI\Popover
    */
    public function position($value) {
        return $this->setProperty('position', $value);
    }

    /**
    * The event on which the Popover will be shown.The supported values are: mouseenter; click or focus.
    * @param string $value
    * @return \Kendo\UI\Popover
    */
    public function showOn($value) {
        return $this->setProperty('showOn', $value);
    }

    /**
    * Specifies the offset (in pixels) between the Popover and the anchor. ╨óhe offset is rendered from the callout arrow.
    * @param float $value
    * @return \Kendo\UI\Popover
    */
    public function offset($value) {
        return $this->setProperty('offset', $value);
    }

    /**
    * Sets the show event of the Popover.
    * Fires when a Popover is shown.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Popover
    */
    public function show($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('show', $value);
    }

    /**
    * Sets the hide event of the Popover.
    * Fires when a Popover is hidden.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Popover
    */
    public function hide($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('hide', $value);
    }


//<< Properties
}

?>
