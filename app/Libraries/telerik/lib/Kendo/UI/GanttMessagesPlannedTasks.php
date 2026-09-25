<?php

namespace Kendo\UI;

class GanttMessagesPlannedTasks extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The text that would be displayed on the switch allowing the user to turn on and off the PlannedTasks view in the Gantt Timeline.
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function switchText($value) {
        return $this->setProperty('switchText', $value);
    }

    /**
    * The text rendered in the Tooltip that would be displayed for tasks which have finished in advance compared to their plannedEnd.
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function offsetTooltipAdvanced($value) {
        return $this->setProperty('offsetTooltipAdvanced', $value);
    }

    /**
    * The text rendered in the Tooltip that would be displayed for tasks which have finished with delay compared to their plannedEnd.
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function offsetTooltipDelay($value) {
        return $this->setProperty('offsetTooltipDelay', $value);
    }

    /**
    * The text for "seconds" displayed in the Advanced/Delayed Tooltip (see above).
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function seconds($value) {
        return $this->setProperty('seconds', $value);
    }

    /**
    * The text for "minutes" displayed in the Advanced/Delayed Tooltip (see above).
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function minutes($value) {
        return $this->setProperty('minutes', $value);
    }

    /**
    * The text for "hours" displayed in the Advanced/Delayed Tooltip (see above).
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function hours($value) {
        return $this->setProperty('hours', $value);
    }

    /**
    * The text for "days" displayed in the Advanced/Delayed Tooltip (see above).
    * @param string $value
    * @return \Kendo\UI\GanttMessagesPlannedTasks
    */
    public function days($value) {
        return $this->setProperty('days', $value);
    }

//<< Properties
}

?>
