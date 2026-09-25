<?php

namespace Kendo\UI;

class StepperStep extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines whether the Step is enabled or not. By default all steps are enabled.
    * @param boolean $value
    * @return \Kendo\UI\StepperStep
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Defines whether the Step is in error state (is invalid). By default all steps are valid.
    * @param boolean $value
    * @return \Kendo\UI\StepperStep
    */
    public function error($value) {
        return $this->setProperty('error', $value);
    }

    /**
    * Defines a name of an existing icon in the Kendo UI theme sprite. The icon will be displayed in the indicator element of that Step. For a list of available icon names, please refer to the Web Font Icons article.
    * @param string $value
    * @return \Kendo\UI\StepperStep
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Sets the iconTemplate option of the StepperStep.
    * The template used to render the icon in the indicator of the step.The fields which can be used in the template are: label String - the label set on the step; icon String - the icon specified for this step (if any); successIcon String - the successIcon specified for this step (if any); enabled Boolean - indicates whether the step is enabled (true) or disabled (false); error Boolean - indicates whether the step has error (true) or not (false); selected Boolean - indicates whether the step is selected; previous Boolean - indicates whether the step is before the currently selected or not; index Number - a zero-based index of the current step; isFirstStep Boolean - indicates whether the step is the initial one in the Stepper; isLastStep Boolean - indicates whether the step is the last one in the Stepper; indicatorVisible Boolean - indicates whether the indicator, which holds the icon should be displayed or not or labelVisible Boolean - indicates whether the label section of the step should be displayed or not.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\StepperStep
    */
    public function iconTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('iconTemplate', $value);
    }

    /**
    * Sets the iconTemplate option of the StepperStep.
    * The template used to render the icon in the indicator of the step.The fields which can be used in the template are: label String - the label set on the step; icon String - the icon specified for this step (if any); successIcon String - the successIcon specified for this step (if any); enabled Boolean - indicates whether the step is enabled (true) or disabled (false); error Boolean - indicates whether the step has error (true) or not (false); selected Boolean - indicates whether the step is selected; previous Boolean - indicates whether the step is before the currently selected or not; index Number - a zero-based index of the current step; isFirstStep Boolean - indicates whether the step is the initial one in the Stepper; isLastStep Boolean - indicates whether the step is the last one in the Stepper; indicatorVisible Boolean - indicates whether the indicator, which holds the icon should be displayed or not or labelVisible Boolean - indicates whether the label section of the step should be displayed or not.
    * @param string $value The template content.
    * @return \Kendo\UI\StepperStep
    */
    public function iconTemplate($value) {
        return $this->setProperty('iconTemplate', $value);
    }

    /**
    * Defines the label (text) of the Step.
    * @param string $value
    * @return \Kendo\UI\StepperStep
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Defines whether the Step is selected.
    * @param boolean $value
    * @return \Kendo\UI\StepperStep
    */
    public function selected($value) {
        return $this->setProperty('selected', $value);
    }

    /**
    * Defines a name of an existing icon in the Kendo UI theme sprite. The icon will be displayed in the indicator element of that Step, when the step is a previous one and it does not have an error. For a list of available icon names, please refer to the Web Font Icons article.
    * @param string $value
    * @return \Kendo\UI\StepperStep
    */
    public function successIcon($value) {
        return $this->setProperty('successIcon', $value);
    }

//<< Properties
}

?>
