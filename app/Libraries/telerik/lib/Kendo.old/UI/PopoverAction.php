<?php

namespace Kendo\UI;

class PopoverAction extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the click option of the PopoverAction.
    * A handler function to be called when the action button is clicked
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PopoverAction
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Sets the iconClass option of the PopoverAction.
    * The CSS class that will be used to display the icon inside the button
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\PopoverAction
    */
    public function iconClass($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('iconClass', $value);
    }

    /**
    * The text displayed in the action button
    * @param string $value
    * @return \Kendo\UI\PopoverAction
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

//<< Properties
}

?>
