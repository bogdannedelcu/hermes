<?php

namespace Kendo\UI;

class TaskBoardCardMenuItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The name of the button.
    * @param string $value
    * @return \Kendo\UI\TaskBoardCardMenuItem
    */
    public function name($value) {
        return $this->setProperty('name', $value);
    }

    /**
    * The text of the button.
    * @param string $value
    * @return \Kendo\UI\TaskBoardCardMenuItem
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

    /**
    * The icon of the button.
    * @param string $value
    * @return \Kendo\UI\TaskBoardCardMenuItem
    */
    public function icon($value) {
        return $this->setProperty('icon', $value);
    }

    /**
    * The class name of the icon element.
    * @param string $value
    * @return \Kendo\UI\TaskBoardCardMenuItem
    */
    public function spriteCssClass($value) {
        return $this->setProperty('spriteCssClass', $value);
    }

    /**
    * The command of the button.
    * @param string $value
    * @return \Kendo\UI\TaskBoardCardMenuItem
    */
    public function command($value) {
        return $this->setProperty('command', $value);
    }

    /**
    * The command options of the button.
    * @param string $value
    * @return \Kendo\UI\TaskBoardCardMenuItem
    */
    public function options($value) {
        return $this->setProperty('options', $value);
    }

//<< Properties
}

?>
