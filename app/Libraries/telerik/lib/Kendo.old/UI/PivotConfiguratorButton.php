<?php

namespace Kendo\UI;

class PivotConfiguratorButton extends \Kendo\UI\Widget {
    public function name() {
        return 'PivotConfiguratorButton';
    }
//>> Properties

    /**
    * Use it to set the Id of the configurator that will be shown/hidden when clicking the button.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorButton
    */
    public function configurator($value) {
        return $this->setProperty('configurator', $value);
    }

    /**
    * The text of the button.
    * @param string $value
    * @return \Kendo\UI\PivotConfiguratorButton
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }


//<< Properties
}

?>
