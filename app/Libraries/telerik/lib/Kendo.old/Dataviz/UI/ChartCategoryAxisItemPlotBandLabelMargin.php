<?php

namespace Kendo\Dataviz\UI;

class ChartCategoryAxisItemPlotBandLabelMargin extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom margin of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelMargin
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left margin of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelMargin
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right margin of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelMargin
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top margin of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelMargin
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
