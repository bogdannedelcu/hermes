<?php

namespace Kendo\UI;

class SkeletonContainer extends \Kendo\UI\Widget {
    public function name() {
        return 'SkeletonContainer';
    }
//>> Properties

    /**
    * Defines the settings for the CSS Grid used for layouting the skeleton shapes.
    * @param \Kendo\UI\SkeletonContainerGrid|array $value
    * @return \Kendo\UI\SkeletonContainer
    */
    public function grid($value) {
        return $this->setProperty('grid', $value);
    }

    /**
    * Determines the height of the component. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\SkeletonContainer
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Sets the template option of the SkeletonContainer.
    * Defines a template that will be used to visualize the skeleton shapes. If both grid and template are defined the grid takes precedence.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\SkeletonContainer
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the SkeletonContainer.
    * Defines a template that will be used to visualize the skeleton shapes. If both grid and template are defined the grid takes precedence.
    * @param string $value The template content.
    * @return \Kendo\UI\SkeletonContainer
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Determines the width of the component. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\SkeletonContainer
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Determines whether the items will be animated
    * @param string $value
    * @return \Kendo\UI\SkeletonContainer
    */
    public function animation($value) {
        return $this->setProperty('animation', $value);
    }


//<< Properties
}

?>
