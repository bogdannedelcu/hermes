<?php

namespace Kendo\UI;

class FilterMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The title of the add expression button.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function addExpression($value) {
        return $this->setProperty('addExpression', $value);
    }

    /**
    * The title of the add group button.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function addGroup($value) {
        return $this->setProperty('addGroup', $value);
    }

    /**
    * The text of the option which represents the "and" logical operation.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function _and($value) {
        return $this->setProperty('and', $value);
    }

    /**
    * The text inside the apply button.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function apply($value) {
        return $this->setProperty('apply', $value);
    }

    /**
    * The title of the close button.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function close($value) {
        return $this->setProperty('close', $value);
    }

    /**
    * The title of the fields drop down.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function fields($value) {
        return $this->setProperty('fields', $value);
    }

    /**
    * The title of the operators drop down.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function operators($value) {
        return $this->setProperty('operators', $value);
    }

    /**
    * The text of the option which represents the "or" logical operation.
    * @param string $value
    * @return \Kendo\UI\FilterMessages
    */
    public function _or($value) {
        return $this->setProperty('or', $value);
    }

//<< Properties
}

?>
