<?php

namespace Kendo\UI;

class AppBar extends \Kendo\UI\Widget {
    public function name() {
        return 'AppBar';
    }
//>> Properties

    /**
    * Adds AppBarItem to the AppBar.
    * @param \Kendo\UI\AppBarItem|array,... $value one or more AppBarItem to add.
    * @return \Kendo\UI\AppBar
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Defines where in the page the AppBar will be positioned
    * @param string $value
    * @return \Kendo\UI\AppBar
    */
    public function position($value) {
        return $this->setProperty('position', $value);
    }

    /**
    * Defines the type of positioning
    * @param string $value
    * @return \Kendo\UI\AppBar
    */
    public function positionMode($value) {
        return $this->setProperty('positionMode', $value);
    }

    /**
    * Specifies the color of the component
    * @param string $value
    * @return \Kendo\UI\AppBar
    */
    public function themeColor($value) {
        return $this->setProperty('themeColor', $value);
    }

    /**
    * Sets the resize event of the AppBar.
    * Fired when the window is resized.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\AppBar
    */
    public function resize($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('resize', $value);
    }


//<< Properties
}

?>
