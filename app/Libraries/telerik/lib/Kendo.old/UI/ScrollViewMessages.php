<?php

namespace Kendo\UI;

class ScrollViewMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the aria-label for the next button.
    * @param string $value
    * @return \Kendo\UI\ScrollViewMessages
    */
    public function nextButtonLabel($value) {
        return $this->setProperty('nextButtonLabel', $value);
    }

    /**
    * Defines the aria-label for the previous button.
    * @param string $value
    * @return \Kendo\UI\ScrollViewMessages
    */
    public function previousButtonLabel($value) {
        return $this->setProperty('previousButtonLabel', $value);
    }

//<< Properties
}

?>
