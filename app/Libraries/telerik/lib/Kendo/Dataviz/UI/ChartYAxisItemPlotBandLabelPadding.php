<?php

namespace Kendo\Dataviz\UI;

class ChartYAxisItemPlotBandLabelPadding extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartYAxisItemPlotBandLabelPadding
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartYAxisItemPlotBandLabelPadding
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartYAxisItemPlotBandLabelPadding
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartYAxisItemPlotBandLabelPadding
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
