<?php

namespace Kendo\UI;

class ExpansionPanelAnimation extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The visual animation(s) that will be used when ExpansionPanel items are closed.
    * @param \Kendo\UI\ExpansionPanelAnimationCollapse|array $value
    * @return \Kendo\UI\ExpansionPanelAnimation
    */
    public function collapse($value) {
        return $this->setProperty('collapse', $value);
    }

    /**
    * The visual animation(s) that will be used when opening items.
    * @param \Kendo\UI\ExpansionPanelAnimationExpand|array $value
    * @return \Kendo\UI\ExpansionPanelAnimation
    */
    public function expand($value) {
        return $this->setProperty('expand', $value);
    }

//<< Properties
}

?>
