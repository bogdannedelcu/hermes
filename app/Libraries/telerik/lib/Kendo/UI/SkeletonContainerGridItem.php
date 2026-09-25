<?php

namespace Kendo\UI;

class SkeletonContainerGridItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * A value that determines the start position of the item on the column axis.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGridItem
    */
    public function colStart($value) {
        return $this->setProperty('colStart', $value);
    }

    /**
    * A value that determines how many columns will the item span.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGridItem
    */
    public function colSpan($value) {
        return $this->setProperty('colSpan', $value);
    }

    /**
    * A value that determines the start position of the item on the row axis.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGridItem
    */
    public function rowStart($value) {
        return $this->setProperty('rowStart', $value);
    }

    /**
    * A value that determines how many rows will the item span.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGridItem
    */
    public function rowSpan($value) {
        return $this->setProperty('rowSpan', $value);
    }

    /**
    * Determines the shape of the item
    * @param string $value
    * @return \Kendo\UI\SkeletonContainerGridItem
    */
    public function shape($value) {
        return $this->setProperty('shape', $value);
    }

//<< Properties
}

?>
