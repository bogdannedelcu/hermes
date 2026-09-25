<?php

namespace Kendo\UI;

class FloatingActionButtonItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies whether the Item is enabled or not. By default all items are enabled.
    * @param boolean $value
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function enabled($value) {
        return $this->setProperty('enabled', $value);
    }

    /**
    * Sets the click option of the FloatingActionButtonItem.
    * Specifies the click event handler of the speed-dial item.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function click($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('click', $value);
    }

    /**
    * Specifies a set of CSS classes for the speed-dial item.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function cssClass($value) {
        return $this->setProperty('cssClass', $value);
    }

    /**
    * Specifies the name for an existing icon in a Kendo UI theme that is rendered in the speed-dial item.See the Web Font Icons help article for more details on Kendo UI icons.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * Specifies the label for the speed-dial item.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Sets the template option of the FloatingActionButtonItem.
    * Specifies the template used to render the contents of the speed-dial item.The fields which can be used inside the template are: text String - the label of the item (if configured). or icon String - the icon specified for this step (if configured)..
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the FloatingActionButtonItem.
    * Specifies the template used to render the contents of the speed-dial item.The fields which can be used inside the template are: text String - the label of the item (if configured). or icon String - the icon specified for this step (if configured)..
    * @param string $value The template content.
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Specifies the label for the speed-dial item that will be read by assistive technologies.
    * @param string $value
    * @return \Kendo\UI\FloatingActionButtonItem
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

//<< Properties
}

?>
