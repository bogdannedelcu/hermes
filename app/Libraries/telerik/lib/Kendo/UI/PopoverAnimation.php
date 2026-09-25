<?php

namespace Kendo\UI;

class PopoverAnimation extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The animation that will be used when the Popover closes.
    * @param \Kendo\UI\PopoverAnimationClose|array $value
    * @return \Kendo\UI\PopoverAnimation
    */
    public function close($value) {
        return $this->setProperty('close', $value);
    }

    /**
    * The animation that will be used when the Popover opens.
    * @param \Kendo\UI\PopoverAnimationOpen|array $value
    * @return \Kendo\UI\PopoverAnimation
    */
    public function open($value) {
        return $this->setProperty('open', $value);
    }

//<< Properties
}

?>
