<?php

namespace Kendo\UI;

class SplitButtonItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds custom attributes to the LI element of the menu button.
    * @param  $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

    /**
    * Sets the click option of the SplitButtonItem.
    * Adds unique click callback for the menu item.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\SplitButtonItem
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Sets the data option of the SplitButtonItem.
    * Adds a custom data callback to be added to the context of menu item - useful to attach context dynamically.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\SplitButtonItem
    */
    public function data($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('data', $value);
    }

    /**
    * Toggles the enabled state of the item.
    * @param boolean $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Indicates wether the item should hidden.
    * @param boolean $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function hidden($value) {
        return $this->setProperty('hidden', $value);
    }

    /**
    * Specifies the icon of the item.
    * @param string $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Specifies the id of the item.
    * @param string $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function id($value) {
        return $this->setProperty('id', $value);
    }

    /**
    * Specifies the image of the item.
    * @param string $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function imageUrl($value) {
        return $this->setProperty('imageUrl', $value);
    }

    /**
    * Specifies custom css class added to the srite icon element of the item.
    * @param string $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function spriteCssClass($value) {
        return $this->setProperty('spriteCssClass', $value);
    }

    /**
    * Specifies the text of the item. ### items.url StringSpecifies the url of the item - it will render a element and will navigate the browser on click.
    * @param string $value
    * @return \Kendo\UI\SplitButtonItem
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

//<< Properties
}

?>
