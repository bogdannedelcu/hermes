<?php

namespace Kendo\UI;

class Wizard extends \Kendo\UI\Widget {
    public function name() {
        return 'Wizard';
    }

    protected function createElement() {
        $tag = $this->getProperty('tag');

        if (!$tag) {
            $tag = 'div';
        }

        $element = new \Kendo\Html\Element($tag);

        foreach ($this->getProperty('attributes') as $key => $value) {
            $element->attr($key, $value);
        }

        return $element;
    }
//>> Properties

    /**
    * Indicates whether the Steps in the Wizard will render their Buttons and Pager element.
    * @param boolean $value
    * @return \Kendo\UI\Wizard
    */
    public function actionBar($value) {
        return $this->setProperty('actionBar', $value);
    }

    /**
    * Indicates whether the step content will be loaded on demand when a given step is selected. Applicable when the step configuration has "contentUrl" set.
    * @param boolean $value
    * @return \Kendo\UI\Wizard
    */
    public function loadOnDemand($value) {
        return $this->setProperty('loadOnDemand', $value);
    }

    /**
    * Provides configuration options for the messages present in the Wizard widget.
    * @param \Kendo\UI\WizardMessages|array $value
    * @return \Kendo\UI\Wizard
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Indicates whether the Steps in the Wizard will render their Pager element.
    * @param boolean $value
    * @return \Kendo\UI\Wizard
    */
    public function pager($value) {
        return $this->setProperty('pager', $value);
    }

    /**
    * Indicates whether the step content will be reloaded on each navigation to given step. Applicable when the step configuration has "contentUrl" set.
    * @param boolean $value
    * @return \Kendo\UI\Wizard
    */
    public function reloadOnSelect($value) {
        return $this->setProperty('reloadOnSelect', $value);
    }

    /**
    * Provides configuration options for the Stepper instance of the Wizard widget.
    * @param \Kendo\UI\WizardStepper|array $value
    * @return \Kendo\UI\Wizard
    */
    public function stepper($value) {
        return $this->setProperty('stepper', $value);
    }

    /**
    * Indicates whether the Wizard will automatically validate any Kendo Form configured for a Step. Validation will be executed upon Step navigation.
    * @param boolean|\Kendo\UI\WizardValidateForms|array $value
    * @return \Kendo\UI\Wizard
    */
    public function validateForms($value) {
        return $this->setProperty('validateForms', $value);
    }

    /**
    * Adds WizardStep to the Wizard.
    * @param \Kendo\UI\WizardStep|array,... $value one or more WizardStep to add.
    * @return \Kendo\UI\Wizard
    */
    public function addStep($value) {
        return $this->add('steps', func_get_args());
    }

    /**
    * The position of the Wizard step content according to the embedded Stepper widget.
    * @param string $value
    * @return \Kendo\UI\Wizard
    */
    public function contentPosition($value) {
        return $this->setProperty('contentPosition', $value);
    }

    /**
    * Sets the activate event of the Wizard.
    * Fires when a new step has been selected upon user interaction.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function activate($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('activate', $value);
    }

    /**
    * Sets the contentLoad event of the Wizard.
    * Triggered when content is fetched from an AJAX request and has been loaded.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function contentLoad($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('contentLoad', $value);
    }

    /**
    * Sets the done event of the Wizard.
    * Triggered when the "Done" action Button has been clicked
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function done($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('done', $value);
    }

    /**
    * Sets the error event of the Wizard.
    * Triggered when an attempt to fetch step content with an AJAX request fails. The event will be fired only when the user clicks on the Previous or Next button, or navigates to another step using the Stepper. It will not be fired if the remote request is raised as a result of a Step.load() method call.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function error($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('error', $value);
    }

    /**
    * Sets the reset event of the Wizard.
    * Triggered when the "Reset" action Button has been clicked
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function reset($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('reset', $value);
    }

    /**
    * Sets the select event of the Wizard.
    * Fires when the user clicks on the Stepper or clicks on "Previous"/"Next" action Buttons to select another step.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }

    /**
    * Sets the formValidateFailed event of the Wizard.
    * Fired when the validateForms configuration option is set to true (default), and the validation of the Kendo UI Form on the current Wizard step fails when the user tries to navigate to another step.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\Wizard
    */
    public function formValidateFailed($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('formValidateFailed', $value);
    }


//<< Properties

    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

    public function tag($value) {
        return $this->setProperty('tag', $value);
    }
}

?>
