<?php

namespace Kendo\UI;

class ExpansionPanelAnimationCollapse extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The number of milliseconds used for the visual animation when a ExpansionPanel item is closed.
    * @param float $value
    * @return \Kendo\UI\ExpansionPanelAnimationCollapse
    */
    public function duration($value) {
        return $this->setProperty('duration', $value);
    }

    /**
    * A whitespace-delimited string of animation effects that are utilized when a ExpansionPanel item is closed. Available options are "fadeOut" and "zoomOut".
    * @param string $value
    * @return \Kendo\UI\ExpansionPanelAnimationCollapse
    */
    public function effects($value) {
        return $this->setProperty('effects', $value);
    }

//<< Properties
}

?>
