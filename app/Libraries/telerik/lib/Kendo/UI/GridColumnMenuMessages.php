<?php

namespace Kendo\UI;

class GridColumnMenuMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The text of the button which applies the columns filter.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function apply($value) {
        return $this->setProperty('apply', $value);
    }

    /**
    * The title of the button that displays the ColumnMenu.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function buttonTitle($value) {
        return $this->setProperty('buttonTitle', $value);
    }

    /**
    * The text message displayed for the column selection menu item.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * The text message displayed for the filter menu item.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function filter($value) {
        return $this->setProperty('filter', $value);
    }

    /**
    * The text message displayed in the column menu for locking a column.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function lock($value) {
        return $this->setProperty('lock', $value);
    }

    /**
    * The text of the button which resets the columns filter.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function reset($value) {
        return $this->setProperty('reset', $value);
    }

    /**
    * The text message displayed for the menu item which performs ascending sort.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function sortAscending($value) {
        return $this->setProperty('sortAscending', $value);
    }

    /**
    * The text message displayed for the menu item which performs descending sort.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function sortDescending($value) {
        return $this->setProperty('sortDescending', $value);
    }

    /**
    * The text message displayed in the column menu for the column position item.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function setColumnPosition($value) {
        return $this->setProperty('setColumnPosition', $value);
    }

    /**
    * The text message displayed in the menu header (available in mobile mode only).
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function settings($value) {
        return $this->setProperty('settings', $value);
    }

    /**
    * The text message displayed in the column menu for sticking a column.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function stick($value) {
        return $this->setProperty('stick', $value);
    }

    /**
    * The text message displayed in the column menu for unsticking a column.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function unstick($value) {
        return $this->setProperty('unstick', $value);
    }

    /**
    * The text message displayed in the column menu for unlocking a column.
    * @param string $value
    * @return \Kendo\UI\GridColumnMenuMessages
    */
    public function unlock($value) {
        return $this->setProperty('unlock', $value);
    }

//<< Properties
}

?>
