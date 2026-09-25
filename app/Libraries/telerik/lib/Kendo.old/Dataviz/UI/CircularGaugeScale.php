<?php

namespace Kendo\Dataviz\UI;

class CircularGaugeScale extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Configures the scale labels.
    * @param \Kendo\Dataviz\UI\CircularGaugeScaleLabels|array $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function labels($value) {
        return $this->setProperty('labels', $value);
    }

    /**
    * Configures the scale major ticks.
    * @param \Kendo\Dataviz\UI\CircularGaugeScaleMajorTicks|array $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function majorTicks($value) {
        return $this->setProperty('majorTicks', $value);
    }

    /**
    * The interval between major divisions.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function majorUnit($value) {
        return $this->setProperty('majorUnit', $value);
    }

    /**
    * The maximum value of the scale.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function max($value) {
        return $this->setProperty('max', $value);
    }

    /**
    * The minimum value of the scale.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function min($value) {
        return $this->setProperty('min', $value);
    }

    /**
    * Configures the scale minor ticks.
    * @param \Kendo\Dataviz\UI\CircularGaugeScaleMinorTicks|array $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function minorTicks($value) {
        return $this->setProperty('minorTicks', $value);
    }

    /**
    * The interval between minor divisions.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function minorUnit($value) {
        return $this->setProperty('minorUnit', $value);
    }

    /**
    * The default color for the ranges.
    * @param string $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function rangePlaceholderColor($value) {
        return $this->setProperty('rangePlaceholderColor', $value);
    }

    /**
    * The width of the range indicators.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function rangeSize($value) {
        return $this->setProperty('rangeSize', $value);
    }

    /**
    * The distance from the range indicators to the ticks.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function rangeDistance($value) {
        return $this->setProperty('rangeDistance', $value);
    }

    /**
    * Reverses the scale direction - values are increase anticlockwise.
    * @param boolean $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function reverse($value) {
        return $this->setProperty('reverse', $value);
    }

    /**
    * The start angle of the gauge. The gauge is rendered clockwise(0 degrees are the 180 degrees in the polar coordinate system)
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeScale
    */
    public function startAngle($value) {
        return $this->setProperty('startAngle', $value);
    }

//<< Properties
}

?>
