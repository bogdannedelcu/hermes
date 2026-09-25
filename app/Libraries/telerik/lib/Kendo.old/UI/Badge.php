<?php

namespace Kendo\UI;

class Badge extends \Kendo\UI\Widget {
    public function name() {
        return 'Badge';
    }

    protected function createElement() {
        return new \Kendo\Html\Element('span', true);
    }
//>> Properties

    /**
    * Specifies wether or not to render additional "cutout" border around the badge.
    * @param boolean $value
    * @return \Kendo\UI\Badge
    */
    public function cutoutBorder($value) {
        return $this->setProperty('cutoutBorder', $value);
    }

    /**
    * Defines the name for an existing icon in a Kendo UI theme or SVG content. The icon is rendered inside the badge by a span.k-icon or span.k-svg-icon element.See web font icons help article for more details on Kendo UI icons.
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * If text is a number, it will cap that number.
    * @param float $value
    * @return \Kendo\UI\Badge
    */
    public function max($value) {
        return $this->setProperty('max', $value);
    }

    /**
    * Sets the template option of the Badge.
    * The template which renders the content of the badge.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\Badge
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the Badge.
    * The template which renders the content of the badge.
    * @param string $value The template content.
    * @return \Kendo\UI\Badge
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * The text of the badge. Valid input includes string, number or object with toString method. Default is empty string.
    * @param string|float $value
    * @return \Kendo\UI\Badge
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * If set to false the badge will not be displayed.
    * @param boolean $value
    * @return \Kendo\UI\Badge
    */
    public function visible($value) {
        return $this->setProperty('visible', $value);
    }

    /**
    * Defines the size of the badge
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Defines the alignment of the badge
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function align($value) {
        return $this->setProperty('align', $value);
    }

    /**
    * Defines the position of the badge
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function position($value) {
        return $this->setProperty('position', $value);
    }

    /**
    * Defines the shape of the badge
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function shape($value) {
        return $this->setProperty('shape', $value);
    }

    /**
    * Defines the color of the badge
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Defines how theme colors are applied to a badge
    * @param string $value
    * @return \Kendo\UI\Badge
    */
    public function fill($value) {
        return $this->setProperty('fill', $value);
    }


//<< Properties
}

?>
