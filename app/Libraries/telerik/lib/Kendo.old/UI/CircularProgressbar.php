<?php

namespace Kendo\UI;

class CircularProgressbar extends \Kendo\UI\Widget {
    public function name() {
        return 'CircularProgressbar';
    }
//>> Properties

    /**
    * Sets the centerTemplate option of the CircularProgressbar.
    * The template that will be displayed in the center of the progress bar. Template variables: *   value - the value *   color - the matching color for the value
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\CircularProgressbar
    */
    public function centerTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('centerTemplate', $value);
    }

    /**
    * Sets the centerTemplate option of the CircularProgressbar.
    * The template that will be displayed in the center of the progress bar. Template variables: *   value - the value *   color - the matching color for the value
    * @param string $value The template content.
    * @return \Kendo\UI\CircularProgressbar
    */
    public function centerTemplate($value) {
        return $this->setProperty('centerTemplate', $value);
    }

    /**
    * The color of the value pointer. Accepts a valid CSS color string, including hex and rgb.
    * @param string $value
    * @return \Kendo\UI\CircularProgressbar
    */
    public function color($value) {
        return $this->setProperty('color', $value);
    }

    /**
    * Adds CircularProgressbarColor to the CircularProgressbar.
    * @param \Kendo\UI\CircularProgressbarColor|array,... $value one or more CircularProgressbarColor to add.
    * @return \Kendo\UI\CircularProgressbar
    */
    public function addColor($value) {
        return $this->add('colors', func_get_args());
    }

    /**
    * The opacity of the value pointer.
    * @param float $value
    * @return \Kendo\UI\CircularProgressbar
    */
    public function opacity($value) {
        return $this->setProperty('opacity', $value);
    }

    /**
    * A value indicating if transition animations should be played.
    * @param boolean $value
    * @return \Kendo\UI\CircularProgressbar
    */
    public function transitions($value) {
        return $this->setProperty('transitions', $value);
    }

    /**
    * A value indicating whether endloess loading is enabled
    * @param boolean $value
    * @return \Kendo\UI\CircularProgressbar
    */
    public function indeterminate($value) {
        return $this->setProperty('indeterminate', $value);
    }

    /**
    * A value indicating how wide will the pointer be
    * @param float $value
    * @return \Kendo\UI\CircularProgressbar
    */
    public function pointerWidth($value) {
        return $this->setProperty('pointerWidth', $value);
    }

    /**
    * The component value.
    * @param float $value
    * @return \Kendo\UI\CircularProgressbar
    */
    public function value($value) {
        return $this->setProperty('value', $value);
    }


//<< Properties
}

?>
