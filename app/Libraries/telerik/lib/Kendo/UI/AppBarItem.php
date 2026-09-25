<?php

namespace Kendo\UI;

class AppBarItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines a set CSS classes for the item.
    * @param string $value
    * @return \Kendo\UI\AppBarItem
    */
    public function className($value) {
        return $this->setProperty('className', $value);
    }

    /**
    * Sets the template option of the AppBarItem.
    * The template which renders as content for the appbar item. Valid only for the contentItem type
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\AppBarItem
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the AppBarItem.
    * The template which renders as content for the appbar item. Valid only for the contentItem type
    * @param string $value The template content.
    * @return \Kendo\UI\AppBarItem
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Determines the width of the item. Valid only for the spacer items. Numeric values are treated as pixels.
    * @param string|float $value
    * @return \Kendo\UI\AppBarItem
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Defines the type of item
    * @param string $value
    * @return \Kendo\UI\AppBarItem
    */
    public function type($value) {
        return $this->setProperty('type', $value);
    }

//<< Properties
}

?>
