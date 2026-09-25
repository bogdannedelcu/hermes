<?php

namespace Kendo\UI;

class PivotContainer extends \Kendo\UI\Widget {
    public function name() {
        return 'PivotContainer';
    }

    public function html() {
        //pivotcontainer does not render content
    }

    public function script($executeOnDomReady = true) {
        $script = array();

        if ($executeOnDomReady) {
            $script[] = 'jQuery(function(){';
        }

        $script[] = 'jQuery("';
        $script[] = $this->id;
        $script[] = '").kendo';
        $script[] = $this->name();
        $script[] = '(';
        $script[] = $this->toJSON();
        $script[] = ');';

        if ($executeOnDomReady) {
            $script[] = '});';
        }

        return implode($script);
    }
//>> Properties

    /**
    * Sets the position of the configurator and button related to the pivot inside the container
    * @param string $value
    * @return \Kendo\UI\PivotContainer
    */
    public function configuratorPosition($value) {
        return $this->setProperty('configuratorPosition', $value);
    }


//<< Properties
}

?>
