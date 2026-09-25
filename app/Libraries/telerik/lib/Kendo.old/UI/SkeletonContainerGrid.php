<?php

namespace Kendo\UI;

class SkeletonContainerGrid extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the number of columns.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGrid
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * An object holding values that determine the spacing between the skeleton items horizontally and vertically.
    * @param \Kendo\UI\SkeletonContainerGridGap|array $value
    * @return \Kendo\UI\SkeletonContainerGrid
    */
    public function gap($value) {
        return $this->setProperty('gap', $value);
    }

    /**
    * Adds SkeletonContainerGridItem to the SkeletonContainerGrid.
    * @param \Kendo\UI\SkeletonContainerGridItem|array,... $value one or more SkeletonContainerGridItem to add.
    * @return \Kendo\UI\SkeletonContainerGrid
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Defines the number of rows.
    * @param float $value
    * @return \Kendo\UI\SkeletonContainerGrid
    */
    public function rows($value) {
        return $this->setProperty('rows', $value);
    }

//<< Properties
}

?>
