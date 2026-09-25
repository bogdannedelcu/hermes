<?php

namespace Kendo\Dataviz\UI;

class StockChartSubtitleMargin extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitleMargin
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitleMargin
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitleMargin
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitleMargin
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
