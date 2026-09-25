<?php

namespace Kendo\Dataviz\UI;

class CircularGaugeScaleLabelsMargin extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The top margin of the labels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScaleLabelsMargin
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

    /**
    * The bottom margin of the labels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScaleLabelsMargin
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left margin of the labels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScaleLabelsMargin
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right margin of the labels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScaleLabelsMargin
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

//<< Properties
}

?>
