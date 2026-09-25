<?php

namespace Kendo\UI;

class OrgChartEditable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Allows the user to create new items as children of existing ones. If set to false, creating new items will not be available in the item edit pop-up menu.
    * @param boolean $value
    * @return \Kendo\UI\OrgChartEditable
    */
    public function create($value) {
        return $this->setProperty('create', $value);
    }

    /**
    * Allows the user to delete items. If set to false, delete item will not be available in the item edit pop-up menu.
    * @param boolean $value
    * @return \Kendo\UI\OrgChartEditable
    */
    public function destroy($value) {
        return $this->setProperty('destroy', $value);
    }

    /**
    * Allows the user to edit the title, name, and avatar of the item. If set to false, editing those fields will not be available in the pop-up editor of the widget.
    * @param boolean $value
    * @return \Kendo\UI\OrgChartEditable
    */
    public function fields($value) {
        return $this->setProperty('fields', $value);
    }

    /**
    * A kendo.ui.Form configuration object that allows customization of the Form on the pop-up editor.
    * @param  $value
    * @return \Kendo\UI\OrgChartEditable
    */
    public function form($value) {
        return $this->setProperty('form', $value);
    }

    /**
    * Allows editing the structure (id/parentId relation). If the parentId has also configuration in the form configuration object, the editable.parent value will be taken into account. If set to false, editing the id/parentId relation will not be available.
    * @param boolean $value
    * @return \Kendo\UI\OrgChartEditable
    */
    public function parent($value) {
        return $this->setProperty('parent', $value);
    }

//<< Properties
}

?>
