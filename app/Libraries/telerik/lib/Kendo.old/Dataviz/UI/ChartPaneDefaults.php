<?php

namespace Kendo\Dataviz\UI;

class ChartPaneDefaults extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The background color of all chart panes. Accepts a valid CSS color string, including hex and rgb.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function background($value) {
        return $this->setProperty('background', $value);
    }

    /**
    * The border of all chart panes.
    * @param \Kendo\Dataviz\UI\ChartPaneDefaultsBorder|array $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function border($value) {
        return $this->setProperty('border', $value);
    }

    /**
    * Specifies whether the charts in the panes should be clipped. By default all charts except radar, polar and 100% stacked charts are clipped.
    * @param boolean $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function clip($value) {
        return $this->setProperty('clip', $value);
    }

    /**
    * The default pane height in pixels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * The margin of the panes. A numeric value will set all margins.
    * @param float|\Kendo\Dataviz\UI\ChartPaneDefaultsMargin|array $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function margin($value) {
        return $this->setProperty('margin', $value);
    }

    /**
    * The padding of the panes. A numeric value will set all paddings.
    * @param float|\Kendo\Dataviz\UI\ChartPaneDefaultsPadding|array $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function padding($value) {
        return $this->setProperty('padding', $value);
    }

    /**
    * The title configuration of the all panes.
    * @param string|\Kendo\Dataviz\UI\ChartPaneDefaultsTitle|array $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaults
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

//<< Properties
}

?>
