<?php

namespace Kendo\UI;

class PivotConfiguratorV2 extends \Kendo\UI\Widget {
    public function name() {
        return 'PivotConfiguratorV2';
    }
//>> Properties

    /**
    * Sets the data source of the PivotConfiguratorV2.
    * @param array|\Kendo\Data\DataSource $value
    * @return \Kendo\UI\PivotConfiguratorV2
    */
    public function dataSource($value) {
        return $this->setProperty('dataSource', $value);
    }

    /**
    * If set to true the user will be able to filter by using the field menu.
    * @param boolean $value
    * @return \Kendo\UI\PivotConfiguratorV2
    */
    public function filterable($value) {
        return $this->setProperty('filterable', $value);
    }

    /**
    * If set to true the user could sort the widget by using the field menu.
    * @param boolean| $value
    * @return \Kendo\UI\PivotConfiguratorV2
    */
    public function sortable($value) {
        return $this->setProperty('sortable', $value);
    }

    /**
    * The height of the PivotConfiguratorV2. Numeric values are treated as pixels.
    * @param float|string $value
    * @return \Kendo\UI\PivotConfiguratorV2
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * The text messages displayed in the fields sections.
    * @param \Kendo\UI\PivotConfiguratorV2Messages|array $value
    * @return \Kendo\UI\PivotConfiguratorV2
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }


//<< Properties
}

?>
