<?php

namespace Kendo\UI;

class SkeletonContainerGridGap extends \Kendo\SerializableObject {
//>> Properties

    /**
    * A value in pixels determining the space between horizontal spacing between the items.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGridGap
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * A value in pixels determining the space between vertical spacing between the items.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGridGap
    */
    public function rows($value) {
        return $this->setProperty('rows', $value);
    }

//<< Properties
}

?>
