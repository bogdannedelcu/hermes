<?php

namespace Kendo\UI;

class BottomNavigationItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The URL (href) to which the item will navigate to.
    * @param string $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function url($value) {
        return $this->setProperty('url', $value);
    }

    /**
    * Contextual data to be used for events within the component.
    * @param  $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function data($value) {
        return $this->setProperty('data', $value);
    }

    /**
    * Defines the name for an existing icon in a Kendo UI theme.See web font icons help article for more details on Kendo UI icons.
    * @param string $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * The text of the item. It is optional - if not set, no text will be rendered.
    * @param string $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * If false, the text will be able to render HTML.
    * @param boolean $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function encoded($value) {
        return $this->setProperty('encoded', $value);
    }

    /**
    * Defines the class name added to the icon element.
    * @param string $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function iconClass($value) {
        return $this->setProperty('iconClass', $value);
    }

    /**
    * Defines the class names of the item's element.
    * @param string $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function cssClass($value) {
        return $this->setProperty('cssClass', $value);
    }

    /**
    * Defines custom attributes of the item's element.
    * @param  $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

    /**
    * Toggles the enabled state of the item.
    * @param boolean $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Toggles the selected state of the item.
    * @param boolean $value
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function selected($value) {
        return $this->setProperty('selected', $value);
    }

    /**
    * Sets the template option of the BottomNavigationItem.
    * Sets a custom template for the item (overrides the template option).
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the BottomNavigationItem.
    * Sets a custom template for the item (overrides the template option).
    * @param string $value The template content.
    * @return \Kendo\UI\BottomNavigationItem
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

//<< Properties
}

?>
