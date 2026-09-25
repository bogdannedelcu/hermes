<?php

namespace Kendo\UI;

class TreeListFilterable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * If set to true, the filter menu will allow the user to input a second criteria.
    * @param boolean $value
    * @return \Kendo\UI\TreeListFilterable
    */
    public function extra($value) {
        return $this->setProperty('extra', $value);
    }

    /**
    * The text messages that are displayed in the filter menu. Use it to customize or localize the filter menu messages.
    * @param \Kendo\UI\TreeListFilterableMessages|array $value
    * @return \Kendo\UI\TreeListFilterable
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * If set to row the user would be able to filter via extra row added below the headers. By default filtering is using the menu mode.Can also be set to the following string values: "row" - the user can filter via extra row within the header.; "menu" - the user can filter via the menu after clicking the filter icon. or "menu, row" - the user can filter with both modes above enabled..
    * @param string $value
    * @return \Kendo\UI\TreeListFilterable
    */
    public function mode($value) {
        return $this->setProperty('mode', $value);
    }

    /**
    * The text of the filter operators that are displayed in the filter menu.
    * @param \Kendo\UI\TreeListFilterableOperators|array $value
    * @return \Kendo\UI\TreeListFilterable
    */
    public function operators($value) {
        return $this->setProperty('operators', $value);
    }

//<< Properties
}

?>
