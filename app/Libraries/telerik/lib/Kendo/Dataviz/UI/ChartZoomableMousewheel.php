<?php

namespace Kendo\Dataviz\UI;

class ChartZoomableMousewheel extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies an axis that should not be zoomed. The supported values are none, x and y.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartZoomableMousewheel
    */
    public function lock($value) {
        return $this->setProperty('lock', $value);
    }

    /**
    * Specifies the zoom rate as percentage of the axis range. The default value is 0.3 or 30% of the axis range.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartZoomableMousewheel
    */
    public function rate($value) {
        return $this->setProperty('rate', $value);
    }

//<< Properties
}

?>
