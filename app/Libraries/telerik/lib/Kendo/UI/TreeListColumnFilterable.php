<?php

namespace Kendo\UI;

class TreeListColumnFilterable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies options for the filter header cell when filter mode is set to 'row'.Can be set to a JavaScript object which represents the filter cell configuration.
    * @param \Kendo\UI\TreeListColumnFilterableCell|array $value
    * @return \Kendo\UI\TreeListColumnFilterable
    */
    public function cell($value) {
        return $this->setProperty('cell', $value);
    }

    /**
    * The property is identical to filterable.operators, but is used for a specific column.
    * @param  $value
    * @return \Kendo\UI\TreeListColumnFilterable
    */
    public function operators($value) {
        return $this->setProperty('operators', $value);
    }

    /**
    * The role data attribute of the widget that is used in the filter menu, or a JavaScript function which initializes that widget.
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\TreeListColumnFilterable
    */
    public function ui($value) {
        return $this->setProperty('ui', $value);
    }

//<< Properties
}

?>
