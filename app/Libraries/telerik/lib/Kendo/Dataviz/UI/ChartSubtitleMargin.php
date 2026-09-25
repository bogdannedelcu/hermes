<?php

namespace Kendo\Dataviz\UI;

class ChartSubtitleMargin extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitleMargin
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitleMargin
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitleMargin
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top margin of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitleMargin
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
