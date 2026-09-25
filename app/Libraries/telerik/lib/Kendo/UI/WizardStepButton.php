<?php

namespace Kendo\UI;

class WizardStepButton extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the click option of the WizardStepButton.
    * A click handler that defines the logic to be executed upon button click.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\WizardStepButton
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Specifies whether the Button in question is enabled or not.
    * @param boolean $value
    * @return \Kendo\UI\WizardStepButton
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Specifies the name of the Button. The default buttons have "reserved" names (those names are "previous", "next", "done", "reset"). That would allow additional customization of that Buttons' text and behavior.
    * @param string $value
    * @return \Kendo\UI\WizardStepButton
    */
    public function name($value) {
        return $this->setProperty('name', $value);
    }

    /**
    * Specifies whether the Button will have the k-primary class assigned or not.
    * @param boolean $value
    * @return \Kendo\UI\WizardStepButton
    */
    public function primary($value) {
        return $this->setProperty('primary', $value);
    }

    /**
    * Specifies the text to be displayed in the Button.
    * @param string $value
    * @return \Kendo\UI\WizardStepButton
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

//<< Properties
}

?>
