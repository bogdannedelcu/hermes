<?php

namespace Kendo\UI;

class DropDownButtonMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Controls the label suffix that will be concatenated to the button's text and used for the aria-label attribute.
    * @param  $value
    * @return \Kendo\UI\DropDownButtonMessages
    */
    public function labelSuffix($value) {
        return $this->setProperty('labelSuffix', $value);
    }

//<< Properties
}

?>
