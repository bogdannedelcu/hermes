<?php

namespace Kendo\UI;

class Loader extends \Kendo\UI\Widget {
    public function name() {
        return 'Loader';
    }
//>> Properties

    /**
    * Allows localization of the strings that are used in the widget.
    * @param \Kendo\UI\LoaderMessages|array $value
    * @return \Kendo\UI\Loader
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * If set to false the loader will not be displayed.
    * @param boolean $value
    * @return \Kendo\UI\Loader
    */
    public function visible($value) {
        return $this->setProperty('visible', $value);
    }

    /**
    * Defines the size of the loader
    * @param string $value
    * @return \Kendo\UI\Loader
    */
    public function size($value) {
        return $this->setProperty('size', $value);
    }

    /**
    * Defines the theme color of the loader
    * @param string $value
    * @return \Kendo\UI\Loader
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Defines the type of the loader
    * @param string $value
    * @return \Kendo\UI\Loader
    */
    public function type($value) {
        return $this->setProperty('type', $value);
    }


//<< Properties
}

?>
