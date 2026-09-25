<?php

namespace Kendo\Dataviz\UI;

class ChartCategoryAxisItemPlotBand extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The color of the plot band.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBand
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * The start position of the plot band in axis units.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBand
    */
    public function from($value) {
        return $this->setProperty('from', $value);
    }

    /**
    * The label configuration of the plotband.
    * @param \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel|array $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBand
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * The opacity of the plot band.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBand
    */
    public function opacity($value) {
        return $this->setProperty('opacity', $value);
    }

    /**
    * The end position of the plot band in axis units.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBand
    */
    public function to($value) {
        return $this->setProperty('to', $value);
    }

//<< Properties
}

?>
