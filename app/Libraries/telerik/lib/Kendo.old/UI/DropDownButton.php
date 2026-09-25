<?php

namespace Kendo\UI;

class DropDownButton extends \Kendo\UI\Widget {
    public function name() {
        return 'DropDownButton';
    }

    protected function createElement() {
        $element = new \Kendo\Html\Element('button');
        $element->text($this->getProperty('content'));
        return $element;
    }

    /**
    * Specifies the text of the button.
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function content($value) {
        return $this->setProperty('content', $value);
    }
//>> Properties

    /**
    * Indicates whether the DropDownButton should be enabled or disabled. By default, it is enabled, unless a disabled="disabled" attribute is detected.
    * @param boolean $value
    * @return \Kendo\UI\DropDownButton
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Controls how the color is applied to the button. Valid values are: "solid", "outline", "flat", "link", and "none". Default value is "solid".
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Defines a name of an existing icon in the Kendo UI theme sprite. The icon will be applied as background image of a span element inside the DropDownButton. The span element can be added automatically by the widget, or an existing element can be used, if it has a k-icon CSS class applied. For a list of available icon names, please refer to the Icons demo.
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Defines a CSS class - or multiple classes separated by spaced - which are applied to a span element inside the DropDownButton. Allows the usage of custom icons.
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function iconClass($value) {
        return $this->setProperty('iconClass', $value);
    }

    /**
    * Defines a URL, which will be used for an img element inside the DropDownButton. The URL can be relative or absolute. In case it is relative, it will be evaluated with relation to the web page URL.The img element can be added automatically by the widget, or an existing element can be used, if it has a k-image CSS class applied.
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function imageUrl($value) {
        return $this->setProperty('imageUrl', $value);
    }

    /**
    * Adds DropDownButtonItem to the DropDownButton.
    * @param \Kendo\UI\DropDownButtonItem|array,... $value one or more DropDownButtonItem to add.
    * @return \Kendo\UI\DropDownButton
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Sets the itemTemplate option of the DropDownButton.
    * Specifies a custom template for the menu items.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\DropDownButton
    */
    public function itemTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('itemTemplate', $value);
    }

    /**
    * Sets the itemTemplate option of the DropDownButton.
    * Specifies a custom template for the menu items.
    * @param string $value The template content.
    * @return \Kendo\UI\DropDownButton
    */
    public function itemTemplate($value) {
        return $this->setProperty('itemTemplate', $value);
    }

    /**
    * The options that will be used for the popup initialization. For more details about the available options refer to Popup documentation.
    * @param \Kendo\UI\DropDownButtonPopup|array $value
    * @return \Kendo\UI\DropDownButton
    */
    public function popup($value) {
        return $this->setProperty('popup', $value);
    }

    /**
    * Controls what border radius is applied to a button. Valid values are: "small", "medium", "large", "full", and "none". Default value is "medium".
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * Controls the overall physical size of a button. Valid values are:  "small", "medium", "large", and "none". Default value is "medium".
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Defines a CSS class (or multiple classes separated by spaces), which will be used for applying a background image to a span element inside the DropDownButton. In case you want to use an icon from the Kendo UI theme sprite background image, it is easier to use the icon property.The span element can be added automatically by the widget, or an existing element can be used, if it has a k-sprite CSS class applied.
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function spriteCssClass($value) {
        return $this->setProperty('spriteCssClass', $value);
    }

    /**
    * Controls the main color applied to the button. Valid values are:  "base", "primary", "secondary", "tertiary", "info", "success", "warning", "error", "dark", "light", "inverse", and "none". Default value is "base".
    * @param string $value
    * @return \Kendo\UI\DropDownButton
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Allows localization of the strings that are used in the widget.
    * @param \Kendo\UI\DropDownButtonMessages|array $value
    * @return \Kendo\UI\DropDownButton
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Sets the click event of the DropDownButton.
    * Fires when the DropDownButton or any if its items is clicked with the mouse, touched on a touch device, or ENTER (or SPACE) is pressed while the DropDownButton or an item is focused.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\DropDownButton
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Sets the open event of the DropDownButton.
    * Fires when the menu button is opened.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\DropDownButton
    */
    public function open($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('open', $value);
    }

    /**
    * Sets the close event of the DropDownButton.
    * Fires when the menu button is closed.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\DropDownButton
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
