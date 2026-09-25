<?php

namespace Kendo\UI;

class Avatar extends \Kendo\UI\Widget {
    public function name() {
        return 'Avatar';
    }
//>> Properties

    /**
    * A text description of the Avatar image. When type="image" is configured this value will be used to populate the alt attribute of the <img> element.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function alt($value) {
        return $this->setProperty('alt', $value);
    }

    /**
    * Specifies whether the avatar should render border around its container element. Default is false.
    * @param boolean $value
    * @return \Kendo\UI\Avatar
    */
    public function border($value) {
        return $this->setProperty('border', $value);
    }

    /**
    * Adds additional custom classes to the Avatar container element.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function className($value) {
        return $this->setProperty('className', $value);
    }

    /**
    * Specifies the appearance fill style of the Avatar. The available values are "outline", "solid" (default), and null.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function fillMode($value) {
        return $this->setProperty('fillMode', $value);
    }

    /**
    * Specifies an icon name to be used if the avatar type is set to icon. For a list of available icon names, please refer to the Web Font Icons article.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Specifies an image URL or dataURL that would be used to populate the src attribute of the avatar <img> element.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function image($value) {
        return $this->setProperty('image', $value);
    }

    /**
    * Could be one of the predefined shapes available for the widget container: "full" (default), "small", "medium", "large", or null.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function rounded($value) {
        return $this->setProperty('rounded', $value);
    }

    /**
    * The Avatar allows you to set predefined sizes. The available size values are "small", "medium" (default), "large", or null.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Sets additional CSS styles to the Avatar container element.
    * @param  $value
    * @return \Kendo\UI\Avatar
    */
    public function style($value) {
        return $this->setProperty('style', $value);
    }

    /**
    * Will be used to populated Avatar content when its type is set to text
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * The Avatar allows you to specify predefined theme colors for background of its container. The available themeColor values are:     - primary (Default) - Applies coloring based on primary theme color.     - base - Applies base theme color.     - secondary - Applies coloring based on secondary theme color.     - tertiary - Applies coloring based on tertiary theme color.     - inherit - Applies inherited coloring value.     - info - Applies coloring based on info theme color.     - success - Applies coloring based on success theme color.     - warning - Applies coloring based on warning theme color.     - error - Applies coloring based on error theme color.     - dark - Applies coloring based on dark theme color.     - light - Applies coloring based on light theme color.     - inverse - Applies coloring based on inverted theme color.     - null - Does not apply theme color class.
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Could be one of the three predefined types for the widget: icon, image, or text (default).
    * @param string $value
    * @return \Kendo\UI\Avatar
    */
    public function type($value) {
        return $this->setProperty('type', $value);
    }


//<< Properties
}

?>
