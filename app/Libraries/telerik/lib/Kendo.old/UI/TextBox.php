<?php

namespace Kendo\UI;

class TextBox extends \Kendo\UI\Widget {
    public function name() {
        return 'TextBox';
    }

    protected function createElement() {
        return new \Kendo\Html\Element('input', true);
    }
//>> Properties

    /**
    * If set to false, the widget will be disabled and will not allow user input. The widget is enabled by default and allows user input.
    * @param boolean $value
    * @return \Kendo\UI\TextBox
    */
    public function enable($value) {
        return $this->setProperty('enable', $value);
    }

    /**
    * Sets a value controlling how the color is applied. Can also be set to the following string values: "solid"; "flat"; "outline" or null.
    * @param string $value
    * @return \Kendo\UI\TextBox
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Adds a label before the input. If the input has no id attribute, a generated id will be assigned. The string and the function parameters are setting the inner HTML of the label.
    * @param string|\Kendo\JavaScriptFunction|\Kendo\UI\TextBoxLabel|array $value
    * @return \Kendo\UI\TextBox
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * The hint displayed by the widget when it is empty. Not set by default.
    * @param string $value
    * @return \Kendo\UI\TextBox
    */
    public function placeholder($value) {
        return $this->setProperty('placeholder', $value);
    }

    /**
    * If set to true, the widget will be readonly and will not allow user input. The widget is not readonly by default and allows user input.
    * @param boolean $value
    * @return \Kendo\UI\TextBox
    */
    public function readonly($value) {
        return $this->setProperty('readonly', $value);
    }

    /**
    * Sets a value controlling the border radius. Can also be set to the following string values: "small"; "medium"; "large"; "full" or null.
    * @param string $value
    * @return \Kendo\UI\TextBox
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * The value of the widget.
    * @param string $value
    * @return \Kendo\UI\TextBox
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

    /**
    * Sets a value controlling size of the component. Can also be set to the following string values: "small"; "medium"; "large" or null.
    * @param string $value
    * @return \Kendo\UI\TextBox
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Sets the change event of the TextBox.
    * Fired when the value of the widget is changed by the user.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TextBox
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }


//<< Properties
}

?>
