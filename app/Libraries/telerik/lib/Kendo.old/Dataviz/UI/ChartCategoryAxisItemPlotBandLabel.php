<?php

namespace Kendo\Dataviz\UI;

class ChartCategoryAxisItemPlotBandLabel extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The position of the plotband label.The supported values are: "left" - the plotband label is positioned on the left; "right" - the plotband label is positioned on the right or "center" - the plotband label is positioned in the center.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function align($value) {
        return $this->setProperty('align', $value);
    }

    /**
    * The background color of the label. Accepts a valid CSS color string, including hex and rgb.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function background($value) {
        return $this->setProperty('background', $value);
    }

    /**
    * The border of the label.
    * @param \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelBorder|array $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function border($value) {
        return $this->setProperty('border', $value);
    }

    /**
    * The text color of the label. Accepts a valid CSS color string, including hex and rgb.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * The font style of the label.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function font($value) {
        return $this->setProperty('font', $value);
    }

    /**
    * The margin of the label. A numeric value will set all margins.
    * @param float|\Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelMargin|array $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function margin($value) {
        return $this->setProperty('margin', $value);
    }

    /**
    * The padding of the label. A numeric value will set all paddings.
    * @param float|\Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabelPadding|array $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function padding($value) {
        return $this->setProperty('padding', $value);
    }

    /**
    * The position of the label.The supported values are: "top" - the axis label is positioned on the top; "bottom" - the axis label is positioned on the bottom or "center" - the axis label is positioned in the center.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function position($value) {
        return $this->setProperty('position', $value);
    }

    /**
    * The rotation angle of the label. By default the label is not rotated.
    * @param float $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function rotation($value) {
        return $this->setProperty('rotation', $value);
    }

    /**
    * The text of the label.
    * @param string $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * If set to false the chart will not display the label.
    * @param boolean $value
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function visible($value) {
        return $this->setProperty('visible', $value);
    }

    /**
    * Sets the visual option of the ChartCategoryAxisItemPlotBandLabel.
    * A function that can be used to create a custom visual for the label. The available argument fields are: text - the label text.; rect - the kendo.geometry.Rect that defines where the visual should be rendered.; sender - the chart instance (may be undefined).; options - the label options. or createVisual - a function that can be used to get the default visual..
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\Dataviz\UI\ChartCategoryAxisItemPlotBandLabel
    */
    public function visual($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('visual', $value);
    }

//<< Properties
}

?>
