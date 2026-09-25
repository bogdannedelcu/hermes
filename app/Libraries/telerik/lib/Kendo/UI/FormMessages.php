<?php

namespace Kendo\UI;

class FormMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The text message displayed for the Form submit button.
    * @param string $value
    * @return \Kendo\UI\FormMessages
    */
    public function submit($value) {
        return $this->setProperty('submit', $value);
    }

    /**
    * The text message displayed for the Form clear button.
    * @param string $value
    * @return \Kendo\UI\FormMessages
    */
    public function clear($value) {
        return $this->setProperty('clear', $value);
    }

    /**
    * The text message displayed next to a field label when the items.label.optional option is set to true.
    * @param string $value
    * @return \Kendo\UI\FormMessages
    */
    public function optional($value) {
        return $this->setProperty('optional', $value);
    }

//<< Properties
}

?>
