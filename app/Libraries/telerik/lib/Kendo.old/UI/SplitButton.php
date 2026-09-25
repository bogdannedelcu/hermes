<?php

namespace Kendo\UI;

class SplitButton extends \Kendo\UI\Widget {
    public function name() {
        return 'SplitButton';
    }

    protected function createElement() {
        $element = new \Kendo\Html\Element('button');
        $element->text($this->getProperty('content'));
        return $element;
    }

    /**
    * Specifies the text of the main button.
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function content($value) {
        return $this->setProperty('content', $value);
    }
//>> Properties

    /**
    * The icon rendered for the arrow button of the SplitButton.
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function arrowIcon($value) {
        return $this->setProperty('arrowIcon', $value);
    }

    /**
    * Indicates whether the SplitButton should be enabled or disabled. By default, it is enabled, unless a disabled="disabled" attribute is detected.
    * @param boolean $value
    * @return \Kendo\UI\SplitButton
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Controls how the color is applied to the button. Valid values are: "solid", "outline", "flat", "link", and "none". Default value is "solid".
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Defines a name of an existing icon in the Kendo UI theme sprite. The icon will be applied as background image of a span element inside the SplitButton. The span element can be added automatically by the widget, or an existing element can be used, if it has a k-icon CSS class applied. For a list of available icon names, please refer to the Icons demo.
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Defines a CSS class - or multiple classes separated by spaced - which are applied to a span element inside the SplitButton. Allows the usage of custom icons.
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function iconClass($value) {
        return $this->setProperty('iconClass', $value);
    }

    /**
    * Defines a URL, which will be used for an img element inside the SplitButton. The URL can be relative or absolute. In case it is relative, it will be evaluated with relation to the web page URL.The img element can be added automatically by the widget, or an existing element can be used, if it has a k-image CSS class applied.
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function imageUrl($value) {
        return $this->setProperty('imageUrl', $value);
    }

    /**
    * Adds SplitButtonItem to the SplitButton.
    * @param \Kendo\UI\SplitButtonItem|array,... $value one or more SplitButtonItem to add.
    * @return \Kendo\UI\SplitButton
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Sets the itemTemplate option of the SplitButton.
    * Specifies a custom template for the menu items.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\SplitButton
    */
    public function itemTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('itemTemplate', $value);
    }

    /**
    * Sets the itemTemplate option of the SplitButton.
    * Specifies a custom template for the menu items.
    * @param string $value The template content.
    * @return \Kendo\UI\SplitButton
    */
    public function itemTemplate($value) {
        return $this->setProperty('itemTemplate', $value);
    }

    /**
    * The options that will be used for the popup initialization. For more details about the available options refer to Popup documentation.
    * @param \Kendo\UI\SplitButtonPopup|array $value
    * @return \Kendo\UI\SplitButton
    */
    public function popup($value) {
        return $this->setProperty('popup', $value);
    }

    /**
    * Controls what border radius is applied to a button. Valid values are: "small", "medium", "large", "full", and "none". Default value is "medium".
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * Controls the overall physical size of a button. Valid values are:  "small", "medium", "large", and "none". Default value is "medium".
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Defines a CSS class (or multiple classes separated by spaces), which will be used for applying a background image to a span element inside the SplitButton. In case you want to use an icon from the Kendo UI theme sprite background image, it is easier to use the icon property.The span element can be added automatically by the widget, or an existing element can be used, if it has a k-sprite CSS class applied.
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function spriteCssClass($value) {
        return $this->setProperty('spriteCssClass', $value);
    }

    /**
    * Controls the main color applied to the button. Valid values are:  "base", "primary", "secondary", "tertiary", "info", "success", "warning", "error", "dark", "light", "inverse", and "none". Default value is "base".
    * @param string $value
    * @return \Kendo\UI\SplitButton
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Allows localization of the strings that are used in the widget.
    * @param \Kendo\UI\SplitButtonMessages|array $value
    * @return \Kendo\UI\SplitButton
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Sets the click event of the SplitButton.
    * Fires when the SplitButton or any if its items is clicked with the mouse, touched on a touch device, or ENTER (or SPACE) is pressed while the SplitButton or an item is focused.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\SplitButton
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Sets the open event of the SplitButton.
    * Fires when the menu button is opened.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\SplitButton
    */
    public function open($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('open', $value);
    }

    /**
    * Sets the close event of the SplitButton.
    * Fires when the menu button is closed.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\SplitButton
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
