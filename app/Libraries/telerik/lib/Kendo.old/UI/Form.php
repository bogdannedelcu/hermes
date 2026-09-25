<?php

namespace Kendo\UI;

class Form extends \Kendo\UI\Widget {
    public function name() {
        return 'Form';
    }

    protected function createElement() {
        $element = new \Kendo\Html\Element('form');

        foreach ($this->getProperty('attributes') as $key => $value) {
            $element->attr($key, $value);
        }

        return $element;
    }
//>> Properties

    /**
    * Sets the buttonsTemplate option of the Form.
    * Specifies the template which is used for rendering the From buttons.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\Form
    */
    public function buttonsTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('buttonsTemplate', $value);
    }

    /**
    * Sets the buttonsTemplate option of the Form.
    * Specifies the template which is used for rendering the From buttons.
    * @param string $value The template content.
    * @return \Kendo\UI\Form
    */
    public function buttonsTemplate($value) {
        return $this->setProperty('buttonsTemplate', $value);
    }

    /**
    * Specifies if the first editor of the form will be focused on initialization.
    * @param boolean $value
    * @return \Kendo\UI\Form
    */
    public function focusFirst($value) {
        return $this->setProperty('focusFirst', $value);
    }

    /**
    * Sets the formatLabel option of the Form.
    * Callback function that could be used to change the default format of the automatically generated labels.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Form
    */
    public function formatLabel($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('formatLabel', $value);
    }

    /**
    * Provides the data model of the Form.The widget renders the form fields based on their data type, unless the items option is specified.
    * @param  $value
    * @return \Kendo\UI\Form
    */
    public function formData($value) {
        return $this->setProperty('formData', $value);
    }

    /**
    * Adds FormItem to the Form.
    * @param \Kendo\UI\FormItem|array,... $value one or more FormItem to add.
    * @return \Kendo\UI\Form
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Configures the Form orientation. Available options are "horizontal" and "vertical".By default, the Form is rendered with vertical orientation.
    * @param string $value
    * @return \Kendo\UI\Form
    */
    public function orientation($value) {
        return $this->setProperty('orientation', $value);
    }

    /**
    * Configures text messages displayed in the Form. Use it to customize or localize the Form messages.
    * @param \Kendo\UI\FormMessages|array $value
    * @return \Kendo\UI\Form
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Configures the built-in Validator options.
    * @param \Kendo\UI\FormValidatable|array $value
    * @return \Kendo\UI\Form
    */
    public function validatable($value) {
        return $this->setProperty('validatable', $value);
    }

    /**
    * Specify the layout of Form content. Valid options are: grid: This is equivalent to display: grid. It defines the form element as a grid container and establishes a new grid formatting context for its contents..
    * @param string $value
    * @return \Kendo\UI\Form
    */
    public function layout($value) {
        return $this->setProperty('layout', $value);
    }

    /**
    * Grid layout settings.
    * @param \Kendo\UI\FormGrid|array $value
    * @return \Kendo\UI\Form
    */
    public function grid($value) {
        return $this->setProperty('grid', $value);
    }

    /**
    * Sets the validate event of the Form.
    * Fired when the validation of the entire form completes.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Form
    */
    public function validate($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('validate', $value);
    }

    /**
    * Sets the validateField event of the Form.
    * Fired when the validation state of a field changes.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Form
    */
    public function validateField($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('validateField', $value);
    }

    /**
    * Sets the change event of the Form.
    * Triggered when the form model is updated.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Form
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the submit event of the Form.
    * Triggered when the form is submitted.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Form
    */
    public function submit($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('submit', $value);
    }

    /**
    * Sets the clear event of the Form.
    * Triggered when the form is cleared.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Form
    */
    public function clear($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('clear', $value);
    }


//<< Properties

    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }
}

?>
