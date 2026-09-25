<?php

namespace Kendo\UI;

class WizardMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies text to be rendered in the "Done" button on the final step.
    * @param string $value
    * @return \Kendo\UI\WizardMessages
    */
    public function done($value) {
        return $this->setProperty('done', $value);
    }

    /**
    * Specifies text to be rendered in the "Next" button on each step.
    * @param string $value
    * @return \Kendo\UI\WizardMessages
    */
    public function next($value) {
        return $this->setProperty('next', $value);
    }

    /**
    * Specifies text to be rendered in the Pager element.
    * @param string $value
    * @return \Kendo\UI\WizardMessages
    */
    public function of($value) {
        return $this->setProperty('of', $value);
    }

    /**
    * Specifies text to be rendered in the "Previous" button on each step.
    * @param string $value
    * @return \Kendo\UI\WizardMessages
    */
    public function previous($value) {
        return $this->setProperty('previous', $value);
    }

    /**
    * Specifies text to be rendered in the "Reset" button on each step.
    * @param string $value
    * @return \Kendo\UI\WizardMessages
    */
    public function reset($value) {
        return $this->setProperty('reset', $value);
    }

    /**
    * Specifies text to be rendered in the Pager element.
    * @param string $value
    * @return \Kendo\UI\WizardMessages
    */
    public function step($value) {
        return $this->setProperty('step', $value);
    }

//<< Properties
}

?>
