<?php

namespace Kendo\UI;

class ActionSheetItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the click option of the ActionSheetItem.
    * Specifies the click event handler of the item.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ActionSheetItem
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Specifies the description of the item.
    * @param string $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function description($value) {
        return $this->setProperty('description', $value);
    }

    /**
    * If set to true, the item will be disabled.
    * @param boolean $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function disabled($value) {
        return $this->setProperty('disabled', $value);
    }

    /**
    * Specifies the group of the item. Items can be segregated in two groups - top and bottom.
    * @param string $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function group($value) {
        return $this->setProperty('group', $value);
    }

    /**
    * Specifies the icon's class of the item.
    * @param string $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function iconClass($value) {
        return $this->setProperty('iconClass', $value);
    }

    /**
    * The icon color. Available options are inherit, default, primary, secondary, tertiary, info, success, warning, error, dark, light, inverted or any hexadecimal value.
    * @param string $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function iconColor($value) {
        return $this->setProperty('iconColor', $value);
    }

    /**
    * The icon size in pixels.
    * @param float $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function iconSize($value) {
        return $this->setProperty('iconSize', $value);
    }

    /**
    * Specifies the main text of the item
    * @param string $value
    * @return \Kendo\UI\ActionSheetItem
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

//<< Properties
}

?>
