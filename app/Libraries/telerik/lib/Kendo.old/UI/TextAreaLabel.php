<?php

namespace Kendo\UI;

class TextAreaLabel extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the inner HTML of the label.
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\TextAreaLabel
    */
    public function content($value) {
        return $this->setProperty('content', $value);
    }

    /**
    * If set to true, the widget will be wrapped in a container that will allow the floating label functionality.
    * @param boolean $value
    * @return \Kendo\UI\TextAreaLabel
    */
    public function floating($value) {
        return $this->setProperty('floating', $value);
    }

//<< Properties
}

?>
