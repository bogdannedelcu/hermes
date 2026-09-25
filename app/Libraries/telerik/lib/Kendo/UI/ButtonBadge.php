<?php

namespace Kendo\UI;

class ButtonBadge extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies alignment of the badge relative to button. Valid position options are: top start, top end, bottom start, bottom end. works in conjunction with badge.position.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function align($value) {
        return $this->setProperty('align', $value);
    }

    /**
    * Specifies wether or not to render additional "cutout" border around the badge.
    * @param boolean $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function cutoutBorder($value) {
        return $this->setProperty('cutoutBorder', $value);
    }

    /**
    * Specifies how theme colors apply to a badge. Valid options are solid (default) and outline.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function fill($value) {
        return $this->setProperty('fill', $value);
    }

    /**
    * Defines the name for an existing icon in a Kendo UI theme or SVG content. The icon is rendered inside the badge by a span.k-icon or span.k-svg-icon element.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * If text is a number, it will cap that number.
    * @param float $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function max($value) {
        return $this->setProperty('max', $value);
    }

    /**
    * Specifies position of the badge relative to the edge of the button. Valid placemnt options are: inline, edge, inside, outside.Note: position configuration, other than inline, requires the badge to be aligned. See badge.align for more details.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function position($value) {
        return $this->setProperty('position', $value);
    }

    /**
    * Specifies the shape of the badge. Valid options are: rectangle, rounded, pill, circle, dot.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function shape($value) {
        return $this->setProperty('shape', $value);
    }

    /**
    * Specifies the size of the badge. Valid options are small, medium and large.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Sets the template option of the ButtonBadge.
    * The template which renders the content of the badge.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\ButtonBadge
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the ButtonBadge.
    * The template which renders the content of the badge.
    * @param string $value The template content.
    * @return \Kendo\UI\ButtonBadge
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * The text of the badge. Valid input includes string, number or object with toString method. Default is empty string.
    * @param string|float $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * Specifies the color of the component. Valid options are inherit, default, primary, secondary, tertiary, info, success, warning, error, dark, light, inverted.
    * @param string $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * If set to false the badge will not be displayed.
    * @param boolean $value
    * @return \Kendo\UI\ButtonBadge
    */
    public function visible($value) {
        return $this->setProperty('visible', $value);
    }

//<< Properties
}

?>
