<?php

namespace Kendo\UI;

class ActionSheet extends \Kendo\UI\Widget {
    public function name() {
        return 'ActionSheet';
    }
//>> Properties

    /**
    * Adds ActionSheetItem to the ActionSheet.
    * @param \Kendo\UI\ActionSheetItem|array,... $value one or more ActionSheetItem to add.
    * @return \Kendo\UI\ActionSheet
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Specifies the title of the component
    * @param string $value
    * @return \Kendo\UI\ActionSheet
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * Sets the close event of the ActionSheet.
    * Fired when the widget is closed.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ActionSheet
    */
    public function close($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('close', $value);
    }

    /**
    * Sets the open event of the ActionSheet.
    * Fired when the widget is opened.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ActionSheet
    */
    public function open($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('open', $value);
    }


//<< Properties
}

?>
