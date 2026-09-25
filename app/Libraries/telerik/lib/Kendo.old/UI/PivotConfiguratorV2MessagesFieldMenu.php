<?php

namespace Kendo\UI;

class PivotConfiguratorV2MessagesFieldMenu extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The text message displayed for the apply button in the includes menu item.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function apply($value) {
        return $this->setProperty('apply', $value);
    }

    /**
    * The text message displayed for the menu item which performs ascending sort.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function sortAscending($value) {
        return $this->setProperty('sortAscending', $value);
    }

    /**
    * The text message displayed for the menu item which performs descending sort.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function sortDescending($value) {
        return $this->setProperty('sortDescending', $value);
    }

    /**
    * The text messages of the fields filter menu item.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function filterFields($value) {
        return $this->setProperty('filterFields', $value);
    }

    /**
    * The text messages of the filter button.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function filter($value) {
        return $this->setProperty('filter', $value);
    }

    /**
    * The text messages of the include menu item.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function _include($value) {
        return $this->setProperty('include', $value);
    }

    /**
    * The text of the clear filter expressions button.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function clear($value) {
        return $this->setProperty('clear', $value);
    }

    /**
    * The text of the reset button in the filter includes menu item.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function reset($value) {
        return $this->setProperty('reset', $value);
    }

    /**
    * The text of the filter operators displayed in the filter menu.
    * @param \Kendo\UI\PivotConfiguratorV2MessagesFieldMenuOperators|array $value
    * @return \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu
    */
    public function operators($value) {
        return $this->setProperty('operators', $value);
    }

//<< Properties
}

?>
