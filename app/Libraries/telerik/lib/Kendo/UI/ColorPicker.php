<?php

namespace Kendo\UI;

class ColorPicker extends \Kendo\UI\Widget {
    protected function name() {
        return 'ColorPicker';
    }

    protected function createElement() {
        $input = new \Kendo\Html\Element('input', true);

        $value = $this->getProperty('value');

        $input->attr('type', 'color');

        if (isset($value)) {
            $input->attr('value', $value);
        }

        return $input;
    }
//>> Properties

    /**
    * Specifies whether the widget should display the Apply / Cancel buttons.
    * @param boolean $value
    * @return \Kendo\UI\ColorPicker
    */
    public function buttons($value) {
        return $this->setProperty('buttons', $value);
    }

    /**
    * Enables the contrast tool in the ColorGradient.
    * @param boolean|\Kendo\UI\ColorPickerContrastTool|array $value
    * @return \Kendo\UI\ColorPicker
    */
    public function contrastTool($value) {
        return $this->setProperty('contrastTool', $value);
    }

    /**
    * Specifies whether selection of a color in the palette view closes the popup. Applied only when buttons are set to false and the currently selected view is palette.
    * @param boolean $value
    * @return \Kendo\UI\ColorPicker
    */
    public function closeOnSelect($value) {
        return $this->setProperty('closeOnSelect', $value);
    }

    /**
    * The number of columns to show in the color dropdown when a pallete is specified. This is automatically initialized for the "basic" and "websafe" palettes. If you use a custom palette then you can set this to some value that makes sense for your colors.
    * @param float $value
    * @return \Kendo\UI\ColorPicker
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * Sets the available input formats in the gradient input editor. Only "hex" and "rgb" are valid values.
    * @param array $value
    * @return \Kendo\UI\ColorPicker
    */
    public function formats($value) {
        return $this->setProperty('formats', $value);
    }

    /**
    * Sets a value controlling how the color is applied. Can also be set to the following string values: "solid"; "flat"; "outline" or null.
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Whether to render the input in the ColorGradient component.
    * @param boolean $value
    * @return \Kendo\UI\ColorPicker
    */
    public function input($value) {
        return $this->setProperty('input', $value);
    }

    /**
    * The size of a color cell.
    * @param float|\Kendo\UI\ColorPickerTileSize|array $value
    * @return \Kendo\UI\ColorPicker
    */
    public function tileSize($value) {
        return $this->setProperty('tileSize', $value);
    }

    /**
    * Allows localization of the strings that are used in the widget.
    * @param \Kendo\UI\ColorPickerMessages|array $value
    * @return \Kendo\UI\ColorPicker
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Only for the HSV selector.  If true, the widget will display the opacity slider. Note that currently in HTML5 the <input type="color"> does not support opacity.
    * @param boolean $value
    * @return \Kendo\UI\ColorPicker
    */
    public function opacity($value) {
        return $this->setProperty('opacity', $value);
    }

    /**
    * Displays the color preview element and the previously selected color for comparison. With buttons set to false, both elements will update at the same time.
    * @param boolean $value
    * @return \Kendo\UI\ColorPicker
    */
    public function preview($value) {
        return $this->setProperty('preview', $value);
    }

    /**
    * Sets a value controlling the border radius. Can also be set to the following string values: "small"; "medium"; "large"; "full" or null.
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * A CSS class name to display an icon in the color picker button.  If specified, the HTML for the element will look like this:
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function toolIcon($value) {
        return $this->setProperty('toolIcon', $value);
    }

    /**
    * The initially selected color. Note that when initializing the widget from an <input> element, the initial color will be decided by the field instead.
    * @param string| $value
    * @return \Kendo\UI\ColorPicker
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }

    /**
    * The available views in the FlatColorPicker. Valid values are "gradient" and "palette".
    * @param array $value
    * @return \Kendo\UI\ColorPicker
    */
    public function views($value) {
        return $this->setProperty('views', $value);
    }

    /**
    * Sets a value controlling size of the component. Can also be set to the following string values: "small"; "medium"; "large" or null.
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Defines the palettes that can be used in the color picker
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function palette($value) {
        return $this->setProperty('palette', $value);
    }

    /**
    * Defines the initially selected view in the ColorPicker.
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function view($value) {
        return $this->setProperty('view', $value);
    }

    /**
    * Defines the format of the gradient input editor
    * @param string $value
    * @return \Kendo\UI\ColorPicker
    */
    public function format($value) {
        return $this->setProperty('format', $value);
    }

    /**
    * Sets the change event of the ColorPicker.
    * Fires when a color was selected, either by clicking on it (in the simple picker), by clicking ENTER or by pressing "Apply" in the HSV picker.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ColorPicker
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the select event of the ColorPicker.
    * Fires as a new color is displayed in the drop-down picker.  This is not necessarily the "final" value; for example this event triggers when the sliders in the HSV selector are dragged, but then pressing ESC would cancel the selection and the color will revert to the original value.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ColorPicker
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }

    /**
    * Sets the open event of the ColorPicker.
    * Fires when the picker popup is opening.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ColorPicker
    */
    public function open($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('open', $value);
    }

    /**
    * Sets the close event of the ColorPicker.
    * Fires when the picker popup is closing.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ColorPicker
    */
    public function close($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('close', $value);
    }


//<< Properties
}

?>
