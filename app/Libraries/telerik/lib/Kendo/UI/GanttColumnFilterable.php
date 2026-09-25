<?php

namespace Kendo\UI;

class GanttColumnFilterable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The role data attribute of the widget that is used in the filter menu, or a JavaScript function which initializes that widget.
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\GanttColumnFilterable
    */
    public function ui($value) {
        return $this->setProperty('ui', $value);
    }

//<< Properties
}

?>
