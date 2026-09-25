<?php

namespace Kendo\Dataviz\UI;

class CircularGaugeGaugeArea extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The background of the gauge area. Any valid CSS color string will work here, including hex and rgb.
    * @param string $value
    * @return \Kendo\Dataviz\UI\CircularGaugeGaugeArea
    */
    public function background($value) {
        return $this->setProperty('background', $value);
    }

    /**
    * The border of the gauge area.
    * @param \Kendo\Dataviz\UI\CircularGaugeGaugeAreaBorder|array $value
    * @return \Kendo\Dataviz\UI\CircularGaugeGaugeArea
    */
    public function border($value) {
        return $this->setProperty('border', $value);
    }

    /**
    * The height of the gauge area.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeGaugeArea
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * The margin of the gauge area.
    * @param float|\Kendo\Dataviz\UI\CircularGaugeGaugeAreaMargin|array $value
    * @return \Kendo\Dataviz\UI\CircularGaugeGaugeArea
    */
    public function margin($value) {
        return $this->setProperty('margin', $value);
    }

    /**
    * The width of the gauge area.
    * @param float $value
    * @return \Kendo\Dataviz\UI\CircularGaugeGaugeArea
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

//<< Properties
}

?>
