<?php

namespace Kendo\UI;

class FloatingActionButton extends \Kendo\UI\Widget {
    public function name() {
        return 'FloatingActionButton';
    }
//>> Properties

    /**
    * Specifies position of the FloatingActionButton relative to its container. Valid position options are: "top start": positions the button at top left corner of the container.; "top center": positions the button at top center of the container.; "top end": positions the button at top right corner of the container.; "middle start": positions the button at middle left of the container.; "middle end": positions the button at middle right of the container.; "bottom start": positions the button at bottom left corner of the container.; "bottom center": positions the button at top center of the container. or "bottom end": positions the button at bottom right corner of the container..  works in conjunction with positionMode and alignOffset.**Note: when using align, make sure the FloatingActionButton container has css position other than "static" and allows overflow content.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function align($value) {
        return $this->setProperty('align', $value);
    }

    /**
    * Specifies the horizontal and vertical offset of the FloatingActionButton.
    * @param \Kendo\UI\FloatingActionButtonAlignOffset|array $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function alignOffset($value) {
        return $this->setProperty('alignOffset', $value);
    }

    /**
    * Specifies whether the FloatingActionButton is enabled (true) or disabled (false).
    * @param boolean $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Controls how the color is applied to the button. Valid values are: "solid", and null.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Specifies the name for an existing icon in a Kendo UI theme that is rendered in the FloatingActionButton.See the Web Font Icons help article for more details on Kendo UI icons.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Adds FloatingActionButtonItem to the FloatingActionButton.
    * @param \Kendo\UI\FloatingActionButtonItem|array,... $value one or more FloatingActionButtonItem to add.
    * @return \Kendo\UI\FloatingActionButton
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Controls what border radius is applied to a button. Valid values are: "small", "medium", "large", "full", and null.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * Specifies the text of the FloatingActionButton. Default is empty string.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * Specifies if the FloatingActionButton is visible initially.
    * @param boolean $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function visible($value) {
        return $this->setProperty('visible', $value);
    }

    /**
    * Defines the type of positioning
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function positionMode($value) {
        return $this->setProperty('positionMode', $value);
    }

    /**
    * Specifies the color of the component
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Specifies the size of the component
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Specifies the shape of the component
    * @param string $value
    * @return \Kendo\UI\FloatingActionButton
    */
    public function shape($value) {
        return $this->setProperty('shape', $value);
    }

    /**
    * Sets the click event of the FloatingActionButton.
    * Fires when the user clicks on a the FloatingActionButton.**Note: when using items configuration, clicking on the FloatingActionButton will open the speed-dial list popup.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FloatingActionButton
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Sets the collapse event of the FloatingActionButton.
    * Fires when the speed-dial popup is closed and its animation is finished.**Note: this event is triggered only when using items configuration.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FloatingActionButton
    */
    public function collapse($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('collapse', $value);
    }

    /**
    * Sets the expand event of the FloatingActionButton.
    * Fires when the speed-dial popup is opened and its animation is finished.**Note: this event is triggered only when using items configuration.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FloatingActionButton
    */
    public function expand($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('expand', $value);
    }


//<< Properties
}

?>
