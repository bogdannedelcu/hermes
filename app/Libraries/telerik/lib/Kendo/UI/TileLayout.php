<?php

namespace Kendo\UI;

class TileLayout extends \Kendo\UI\Widget {
    public function name() {
        return 'TileLayout';
    }
//>> Properties

    /**
    * Defines the number of columns.
    * @param float $value
    * @return \Kendo\UI\TileLayout
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * Determines the width of the columns. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\TileLayout
    */
    public function columnsWidth($value) {
        return $this->setProperty('columnsWidth', $value);
    }

    /**
    * Adds TileLayoutContainer to the TileLayout.
    * @param \Kendo\UI\TileLayoutContainer|array,... $value one or more TileLayoutContainer to add.
    * @return \Kendo\UI\TileLayout
    */
    public function addContainer($value) {
        return $this->add('containers', func_get_args());
    }

    /**
    * An object holding values that determine the spacing between the layout items horizontally and vertically.
    * @param \Kendo\UI\TileLayoutGap|array $value
    * @return \Kendo\UI\TileLayout
    */
    public function gap($value) {
        return $this->setProperty('gap', $value);
    }

    /**
    * Determines the height of the layout. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\TileLayout
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * A value indicating whether keyboard navigation will be enabled.
    * @param boolean $value
    * @return \Kendo\UI\TileLayout
    */
    public function navigatable($value) {
        return $this->setProperty('navigatable', $value);
    }

    /**
    * Determines whether the reordering functionality will be enabled.
    * @param boolean $value
    * @return \Kendo\UI\TileLayout
    */
    public function reorderable($value) {
        return $this->setProperty('reorderable', $value);
    }

    /**
    * Determines whether the resizing functionality will be enabled.
    * @param boolean $value
    * @return \Kendo\UI\TileLayout
    */
    public function resizable($value) {
        return $this->setProperty('resizable', $value);
    }

    /**
    * Determines the height of the rows. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\TileLayout
    */
    public function rowsHeight($value) {
        return $this->setProperty('rowsHeight', $value);
    }

    /**
    * Determines the width of the layout. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\TileLayout
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Sets the resize event of the TileLayout.
    * Fired when a tile item is resized.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TileLayout
    */
    public function resize($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('resize', $value);
    }

    /**
    * Sets the reorder event of the TileLayout.
    * Fired when a tile item is reordered.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TileLayout
    */
    public function reorder($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('reorder', $value);
    }


//<< Properties
}

?>
