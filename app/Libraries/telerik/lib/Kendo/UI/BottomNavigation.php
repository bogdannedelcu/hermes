<?php

namespace Kendo\UI;

class BottomNavigation extends \Kendo\UI\Widget {
    public function name() {
        return 'BottomNavigation';
    }

    protected function createElement() {
        return new \Kendo\Html\Element('nav');
    }
//>> Properties

    /**
    * Toggles the border of the widget.
    * @param boolean $value
    * @return \Kendo\UI\BottomNavigation
    */
    public function border($value) {
        return $this->setProperty('border', $value);
    }

    /**
    * Toggles the shadow of the widget.
    * @param boolean $value
    * @return \Kendo\UI\BottomNavigation
    */
    public function shadow($value) {
        return $this->setProperty('shadow', $value);
    }

    /**
    * Adds BottomNavigationItem to the BottomNavigation.
    * @param \Kendo\UI\BottomNavigationItem|array,... $value one or more BottomNavigationItem to add.
    * @return \Kendo\UI\BottomNavigation
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Sets the template option of the BottomNavigation.
    * Sets a custom template for the items.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\BottomNavigation
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the BottomNavigation.
    * Sets a custom template for the items.
    * @param string $value The template content.
    * @return \Kendo\UI\BottomNavigation
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Defines the fill of the component
    * @param string $value
    * @return \Kendo\UI\BottomNavigation
    */
    public function fill($value) {
        return $this->setProperty('fill', $value);
    }

    /**
    * Specifies the color of the component
    * @param string $value
    * @return \Kendo\UI\BottomNavigation
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Specifies the iteFLow of the component
    * @param string $value
    * @return \Kendo\UI\BottomNavigation
    */
    public function itemFlow($value) {
        return $this->setProperty('itemFlow', $value);
    }

    /**
    * Specifies the positionMode of the component
    * @param string $value
    * @return \Kendo\UI\BottomNavigation
    */
    public function positionMode($value) {
        return $this->setProperty('positionMode', $value);
    }

    /**
    * Sets the select event of the BottomNavigation.
    * Fires when the user selects an item in BottomNavigation.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\BottomNavigation
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }


//<< Properties
}

?>
