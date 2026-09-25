<?php

namespace Kendo\Dataviz\UI;

class StockChartSubtitlePadding extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitlePadding
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitlePadding
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitlePadding
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\StockChartSubtitlePadding
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
