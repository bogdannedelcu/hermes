<?php

namespace Kendo\UI;

class GanttColumnSortable extends \Kendo\SerializableObject {
    /**
    * The sortable configuration of the widget.
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\GanttColumnSortable
    */
    public function compare($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('compare', $value);
    }
}

?>