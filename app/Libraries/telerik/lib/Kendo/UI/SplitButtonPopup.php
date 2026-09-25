<?php

namespace Kendo\UI;

class SplitButtonPopup extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines a jQuery selector that will be used to find a container element, where the popup will be appended to.
    * @param string $value
    * @return \Kendo\UI\SplitButtonPopup
    */
    public function appendTo($value) {
        return $this->setProperty('appendTo', $value);
    }

//<< Properties
}

?>
