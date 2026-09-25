<?php

namespace Kendo\UI;

class PivotConfiguratorV2Messages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The text of the apply button.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function applyButtonText($value) {
        return $this->setProperty('applyButtonText', $value);
    }

    /**
    * The text of the cancel button.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function cancelButtonText($value) {
        return $this->setProperty('cancelButtonText', $value);
    }

    /**
    * The text messages displayed in the measure fields section when empty.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function measures($value) {
        return $this->setProperty('measures', $value);
    }

    /**
    * The text messages displayed in the column fields section when empty.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * The text messages displayed in the row fields section when empty.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function rows($value) {
        return $this->setProperty('rows', $value);
    }

    /**
    * The title message in the configurator.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * The text messages displayed in the field menu.
    * @param \Kendo\UI\PivotConfiguratorV2MessagesFieldMenu|array $value
    * @return \Kendo\UI\PivotConfiguratorV2Messages
    */
    public function fieldMenu($value) {
        return $this->setProperty('fieldMenu', $value);
    }

//<< Properties
}

?>
