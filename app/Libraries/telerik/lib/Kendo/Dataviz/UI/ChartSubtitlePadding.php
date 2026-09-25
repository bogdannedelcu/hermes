<?php

namespace Kendo\Dataviz\UI;

class ChartSubtitlePadding extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The bottom padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitlePadding
    */
    public function bottom($value) {
        return $this->setProperty('bottom', $value);
    }

    /**
    * The left padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitlePadding
    */
    public function left($value) {
        return $this->setProperty('left', $value);
    }

    /**
    * The right padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitlePadding
    */
    public function right($value) {
        return $this->setProperty('right', $value);
    }

    /**
    * The top padding of the subtitle.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartSubtitlePadding
    */
    public function top($value) {
        return $this->setProperty('top', $value);
    }

//<< Properties
}

?>
