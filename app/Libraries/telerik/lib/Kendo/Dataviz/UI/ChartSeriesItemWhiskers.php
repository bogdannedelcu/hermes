<?php

namespace Kendo\Dataviz\UI;

class ChartSeriesItemWhiskers extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The color of the whiskers.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSeriesItemWhiskers
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * The dash type of the whiskers.The following dash types are supported: "dash" - a line consisting of dashes; "dashDot" - a line consisting of a repeating pattern of dash-dot; "dot" - a line consisting of dots; "longDash" - a line consisting of a repeating pattern of long-dash; "longDashDot" - a line consisting of a repeating pattern of long-dash-dot; "longDashDotDot" - a line consisting of a repeating pattern of long-dash-dot-dot or "solid" - a solid line.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSeriesItemWhiskers
    */
    public function dashType($value) {
        return $this->setProperty('dashType', $value);
    }

    /**
    * The opacity of the whiskers.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSeriesItemWhiskers
    */
    public function opacity($value) {
        return $this->setProperty('opacity', $value);
    }

    /**
    * The width of the whiskers.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSeriesItemWhiskers
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

//<< Properties
}

?>
