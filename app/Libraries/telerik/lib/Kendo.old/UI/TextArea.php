<?php

namespace Kendo\UI;

class TextArea extends \Kendo\UI\Widget {
    public function name() {
        return 'TextArea';
    }

    protected function createElement() {
        return new \Kendo\Html\Element('textarea');
    }
//>> Properties

    /**
    * The visible width of the text control, in average character widths.
    * @param float $value
    * @return \Kendo\UI\TextArea
    */
    public function cols($value) {
        return $this->setProperty('cols', $value);
    }

    /**
    * If set to false, the widget will be disabled and will not allow user input. The widget is enabled by default and allows user input.
    * @param boolean $value
    * @return \Kendo\UI\TextArea
    */
    public function enable($value) {
        return $this->setProperty('enable', $value);
    }

    /**
    * Sets a value controlling how the color is applied. Can also be set to the following string values: "solid"; "flat"; "outline" or null.
    * @param string $value
    * @return \Kendo\UI\TextArea
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Adds a label before the textarea. If the textarea has no id attribute, a generated id will be assigned. The string and the function parameters are setting the inner HTML of the label.
    * @param string|\Kendo\JavaScriptFunction|\Kendo\UI\TextAreaLabel|array $value
    * @return \Kendo\UI\TextArea
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * The maximum number of characters that the user can enter.
    * @param float $value
    * @return \Kendo\UI\TextArea
    */
    public function maxLength($value) {
        return $this->setProperty('maxLength', $value);
    }

    /**
    * The hint displayed by the widget when it is empty. Not set by default.
    * @param string $value
    * @return \Kendo\UI\TextArea
    */
    public function placeholder($value) {
        return $this->setProperty('placeholder', $value);
    }

    /**
    * If set to true, the widget will be readonly and will not allow user input. The widget is not readonly by default and allows user input.
    * @param boolean $value
    * @return \Kendo\UI\TextArea
    */
    public function readonly($value) {
        return $this->setProperty('readonly', $value);
    }

    /**
    * Defines if (and how) the widget is resizable by the user.Can also be set to the following string values: "none" - default value. The user cannot resize the widget.; "both" - the user can resize both the height and width of the widget.; "horizontal" - the user can resize the width of the widget. or "vertical" - the user can resize the height of the widget..
    * @param string $value
    * @return \Kendo\UI\TextArea
    */
    public function resize($value) {
        return $this->setProperty('resize', $value);
    }

    /**
    * The number of visible text lines for the control.
    * @param float $value
    * @return \Kendo\UI\TextArea
    */
    public function rows($value) {
        return $this->setProperty('rows', $value);
    }

    /**
    * Sets a value controlling the border radius. Can also be set to the following string values: "small"; "medium"; "large"; "full" or "none".
    * @param string $value
    * @return \Kendo\UI\TextArea
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * The value of the widget.
    * @param string $value
    * @return \Kendo\UI\TextArea
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

    /**
    * Sets a value controlling size of the component. Can also be set to the following string values: "small"; "medium"; "large" or "none".
    * @param string $value
    * @return \Kendo\UI\TextArea
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Sets the change event of the TextArea.
    * Fired when the value of the widget is changed by the user.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TextArea
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
