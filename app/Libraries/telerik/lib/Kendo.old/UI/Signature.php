<?php

namespace Kendo\UI;

class Signature extends \Kendo\UI\Widget {
    public function name() {
        return 'Signature';
    }
//>> Properties

    /**
    * Gets or sets the background color of the signature.
    * @param string $value
    * @return \Kendo\UI\Signature
    */
    public function backgroundColor($value) {
        return $this->setProperty('backgroundColor', $value);
    }

    /**
    * The stroke color of the signature.
    * @param string $value
    * @return \Kendo\UI\Signature
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * If set to false, the widget will be disabled and will not allow user input. The widget is enabled by default and allows user input.
    * @param boolean $value
    * @return \Kendo\UI\Signature
    */
    public function enable($value) {
        return $this->setProperty('enable', $value);
    }

    /**
    * Sets a value controlling how the color is applied. Can also be set to the following string values: "solid"; "flat"; "outline" or "none".
    * @param string $value
    * @return \Kendo\UI\Signature
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Determines the height of the signature in pixels.
    * @param float $value
    * @return \Kendo\UI\Signature
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * A value indicating whether the dotted line should be displayed in the background.
    * @param boolean $value
    * @return \Kendo\UI\Signature
    */
    public function hideLine($value) {
        return $this->setProperty('hideLine', $value);
    }

    /**
    * A value indicating whether the component can be maximized
    * @param boolean $value
    * @return \Kendo\UI\Signature
    */
    public function maximizable($value) {
        return $this->setProperty('maximizable', $value);
    }

    /**
    * Defines a value indicating the scaling size of the popup signature pad
    * @param float $value
    * @return \Kendo\UI\Signature
    */
    public function popupScale($value) {
        return $this->setProperty('popupScale', $value);
    }

    /**
    * If set to true, the widget will be readonly and will not allow user input. The widget is not readonly by default and allows user input.
    * @param boolean $value
    * @return \Kendo\UI\Signature
    */
    public function readonly($value) {
        return $this->setProperty('readonly', $value);
    }

    /**
    * Sets a value controlling the border radius. Can also be set to the following string values: "small"; "medium"; "large"; "full" or "none".
    * @param string $value
    * @return \Kendo\UI\Signature
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * Sets a value controlling size of the component. Can also be set to the following string values: "small"; "medium"; "large" or "none".
    * @param string $value
    * @return \Kendo\UI\Signature
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * A value indicating whether to smoothen out the signature lines.
    * @param boolean $value
    * @return \Kendo\UI\Signature
    */
    public function smooth($value) {
        return $this->setProperty('smooth', $value);
    }

    /**
    * Defines how wide will the stroke be.
    * @param float $value
    * @return \Kendo\UI\Signature
    */
    public function strokeWidth($value) {
        return $this->setProperty('strokeWidth', $value);
    }

    /**
    * A string value representing a Base64-encoded PNG image
    * @param string $value
    * @return \Kendo\UI\Signature
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

    /**
    * Determines the width of the signature in pixels.
    * @param float $value
    * @return \Kendo\UI\Signature
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Sets the change event of the Signature.
    * Fired when the value of the widget is changed by the user.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Signature
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the close event of the Signature.
    * Fired when the value popup of the component is changed
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Signature
    */
    public function close($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('close', $value);
    }

    /**
    * Sets the open event of the Signature.
    * Fired when the value of the widget is changed by the user.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Signature
    */
    public function open($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('open', $value);
    }


//<< Properties
}

?>
