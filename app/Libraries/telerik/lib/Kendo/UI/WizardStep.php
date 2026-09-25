<?php

namespace Kendo\UI;

class WizardStep extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds WizardStepButton to the WizardStep.
    * @param \Kendo\UI\WizardStepButton|array,... $value one or more WizardStepButton to add.
    * @return \Kendo\UI\WizardStep
    */
    public function addButton($value) {
        return $this->add('buttons', func_get_args());
    }

    /**
    * Specifies the HTML string content to be rendered in the step.
    * @param string $value
    * @return \Kendo\UI\WizardStep
    */
    public function content($value) {
        return $this->setProperty('content', $value);
    }

    /**
    * Specifies the id of a DOM element, which content to be used as a content of the current step.
    * @param string $value
    * @return \Kendo\UI\WizardStep
    */
    public function contentId($value) {
        return $this->setProperty('contentId', $value);
    }

    /**
    * Specifies an endpoint which the step content should be loaded from.
    * @param string $value
    * @return \Kendo\UI\WizardStep
    */
    public function contentUrl($value) {
        return $this->setProperty('contentUrl', $value);
    }

    /**
    * Specifies a custom class that will be set on the step container element.
    * @param string $value
    * @return \Kendo\UI\WizardStep
    */
    public function className($value) {
        return $this->setProperty('className', $value);
    }

    /**
    * Specifies whether the step is enabled or not.
    * @param boolean $value
    * @return \Kendo\UI\WizardStep
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Defines the Form widget configuration, which will populate the Wizard step content.
    * @param  $value
    * @return \Kendo\UI\WizardStep
    */
    public function form($value) {
        return $this->setProperty('form', $value);
    }

    /**
    * Defines a name of an existing icon in the Kendo UI theme sprite. The icon will be displayed in the Stepper step element. For a list of available icon names, please refer to the Web Font Icons article.
    * @param string $value
    * @return \Kendo\UI\WizardStep
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Sets the iconTemplate option of the WizardStep.
    * The template used to render the icon in the Stepper step.The fields which can be used in the template are: title String - the title set on the step; label String - same as title - the title set on the step; icon String - the icon specified for this step (if any); enabled Boolean - indicates whether the step is enabled (true) or disabled (false); selected Boolean - indicates whether the step is selected; previous Boolean - indicates whether the step is before the currently selected or not; index Number - a zero-based index of the current step; isFirstStep Boolean - indicates whether the step is the initial one in the Stepper; isLastStep Boolean - indicates whether the step is the last one in the Stepper; indicatorVisible Boolean - indicates whether the indicator, which holds the icon should be displayed or not or labelVisible Boolean - indicates whether the label section of the step should be displayed or not.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\WizardStep
    */
    public function iconTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('iconTemplate', $value);
    }

    /**
    * Sets the iconTemplate option of the WizardStep.
    * The template used to render the icon in the Stepper step.The fields which can be used in the template are: title String - the title set on the step; label String - same as title - the title set on the step; icon String - the icon specified for this step (if any); enabled Boolean - indicates whether the step is enabled (true) or disabled (false); selected Boolean - indicates whether the step is selected; previous Boolean - indicates whether the step is before the currently selected or not; index Number - a zero-based index of the current step; isFirstStep Boolean - indicates whether the step is the initial one in the Stepper; isLastStep Boolean - indicates whether the step is the last one in the Stepper; indicatorVisible Boolean - indicates whether the indicator, which holds the icon should be displayed or not or labelVisible Boolean - indicates whether the label section of the step should be displayed or not.
    * @param string $value The template content.
    * @return \Kendo\UI\WizardStep
    */
    public function iconTemplate($value) {
        return $this->setProperty('iconTemplate', $value);
    }

    /**
    * Specifies whether the pager will be rendered on the current step or not.
    * @param boolean $value
    * @return \Kendo\UI\WizardStep
    */
    public function pager($value) {
        return $this->setProperty('pager', $value);
    }

    /**
    * Specifies a title of the current step.
    * @param string $value
    * @return \Kendo\UI\WizardStep
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

//<< Properties
}

?>
