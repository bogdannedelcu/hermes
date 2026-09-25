<?php

namespace Kendo\Dataviz\UI;

class ChartPaneDefaultsPadding extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom padding of the chart panes.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaultsPadding
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left padding of the chart panes.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaultsPadding
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right padding of the chart panes.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaultsPadding
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top padding of the chart panes.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartPaneDefaultsPadding
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
