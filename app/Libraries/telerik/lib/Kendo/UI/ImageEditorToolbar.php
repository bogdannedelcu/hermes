<?php

namespace Kendo\UI;

class ImageEditorToolbar extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Adds ImageEditorToolbarItem to the ImageEditorToolbar.
    * @param \Kendo\UI\ImageEditorToolbarItem|array,... $value one or more ImageEditorToolbarItem to add.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Sets the click option of the ImageEditorToolbar.
    * Fires when the user clicks a command button. Toolbar Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Sets the close option of the ImageEditorToolbar.
    * Fires when the SplitButton's popup closes. Toolbar Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function close($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('close', $value);
    }

    /**
    * Sets the open option of the ImageEditorToolbar.
    * Fires when the Split Button's popup opens. Toolbar Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function open($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('open', $value);
    }

    /**
    * Sets the toggle option of the ImageEditorToolbar.
    * Fires when the user changes the checked state of a toggle button. Toolbar Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function toggle($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('toggle', $value);
    }

    /**
    * Sets the overflowClose option of the ImageEditorToolbar.
    * Fires when the overflow popup container is about to close. Toolbar Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function overflowClose($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('overflowClose', $value);
    }

    /**
    * Sets the overflowOpen option of the ImageEditorToolbar.
    * Fires when the overflow popup container is about to open. Toolbar Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditorToolbar
    */
    public function overflowOpen($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('overflowOpen', $value);
    }

//<< Properties
}

?>
