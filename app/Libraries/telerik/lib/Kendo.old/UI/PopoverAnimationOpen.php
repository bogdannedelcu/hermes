<?php

namespace Kendo\UI;

class PopoverAnimationOpen extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The effect that will be used for opening the Popover.
    * @param string $value
    * @return \Kendo\UI\PopoverAnimationOpen
    */
    public function effects($value) {
        return $this->setProperty('effects', $value);
    }

    /**
    * Defines the duration of the open animation.
    * @param float $value
    * @return \Kendo\UI\PopoverAnimationOpen
    */
    public function duration($value) {
        return $this->setProperty('duration', $value);
    }

//<< Properties
}

?>
