<?php

namespace Kendo\Dataviz\UI;

class ChartSubtitle extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The alignment of the subtitle. "center" - the text is aligned to the middle.; "left" - the text is aligned to the left. or "right" - the text is aligned to the right.. By default, the subtitle has the same alignment as the title.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function align($value) {
        return $this->setProperty('align', $value);
    }

    /**
    * The background color of the subtitle. Accepts a valid CSS color string, including hex and rgb.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function background($value) {
        return $this->setProperty('background', $value);
    }

    /**
    * The border of the subtitle.
    * @param \Kendo\Dataviz\UI\ChartSubtitleBorder|array $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function border($value) {
        return $this->setProperty('border', $value);
    }

    /**
    * The text color of the subtitle. Accepts a valid CSS color string, including hex and rgb.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * The font of the title.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function font($value) {
        return $this->setProperty('font', $value);
    }

    /**
    * The margin of the subtitle. A numeric value will set all margins.
    * @param float|\Kendo\Dataviz\UI\ChartSubtitleMargin|array $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function margin($value) {
        return $this->setProperty('margin', $value);
    }

    /**
    * The padding of the subtitle. A numeric value will set all margins.
    * @param float|\Kendo\Dataviz\UI\ChartSubtitlePadding|array $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function padding($value) {
        return $this->setProperty('padding', $value);
    }

    /**
    * The position of the subtitle. "bottom" - the title is positioned on the bottom. or "top" - the title is positioned on the top.. By default, the subtitle is placed in the same position as the title.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function position($value) {
        return $this->setProperty('position', $value);
    }

    /**
    * The text of the chart subtitle. You can also set the text directly for a title with default options.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * If set to true the chart will display the subtitle. By default the subtitle will be displayed.
    * @param boolean $value
    * @return \Kendo\Dataviz\UI\ChartSubtitle
    */
    public function visible($value) {
        return $this->setProperty('visible', $value);
    }

//<< Properties
}

?>
