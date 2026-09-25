<?php

namespace Kendo\UI;

class FormValidatable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Configures the Form Validator validateOnBlur option.
    * @param boolean $value
    * @return \Kendo\UI\FormValidatable
    */
    public function validateOnBlur($value) {
        return $this->setProperty('validateOnBlur', $value);
    }

    /**
    * Configures the Form Validator validationSummary option.
    * @param boolean| $value
    * @return \Kendo\UI\FormValidatable
    */
    public function validationSummary($value) {
        return $this->setProperty('validationSummary', $value);
    }

    /**
    * Sets the errorTemplate option of the FormValidatable.
    * Configures the Form Validator errorTemplate option.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\FormValidatable
    */
    public function errorTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('errorTemplate', $value);
    }

    /**
    * Sets the errorTemplate option of the FormValidatable.
    * Configures the Form Validator errorTemplate option.
    * @param string $value The template content.
    * @return \Kendo\UI\FormValidatable
    */
    public function errorTemplate($value) {
        return $this->setProperty('errorTemplate', $value);
    }

//<< Properties
}

?>
