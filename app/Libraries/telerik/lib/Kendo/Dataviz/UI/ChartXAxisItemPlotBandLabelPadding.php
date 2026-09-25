<?php

namespace Kendo\Dataviz\UI;

class ChartXAxisItemPlotBandLabelPadding extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartXAxisItemPlotBandLabelPadding
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartXAxisItemPlotBandLabelPadding
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartXAxisItemPlotBandLabelPadding
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top padding of the label.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartXAxisItemPlotBandLabelPadding
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
