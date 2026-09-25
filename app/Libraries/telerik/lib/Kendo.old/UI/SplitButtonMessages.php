<?php

namespace Kendo\UI;

class SplitButtonMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Controls the label suffix that will be concatenated to the button's text and used for the aria-label attribute.
    * @param  $value
    * @return \Kendo\UI\SplitButtonMessages
    */
    public function labelSuffix($value) {
        return $this->setProperty('labelSuffix', $value);
    }

//<< Properties
}

?>
