<?php

namespace Kendo\UI;

class SchedulerMessagesRecurrenceEditor extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Applicable in Scheduler Adaptive rendering scenario. The text for the cancel button on the scheduler recurrence editor header.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function cancel($value) {
        return $this->setProperty('cancel', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor daily messages. Use this option to customize or localize the scheduler recurrence editor daily messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorDaily|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function daily($value) {
        return $this->setProperty('daily', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor end messages. Use this option to customize or localize the scheduler recurrence editor end messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorEnd|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function end($value) {
        return $this->setProperty('end', $value);
    }

    /**
    * Applicable in Scheduler Adaptive rendering scenario. The text for the endTitle (end repeat on) on the scheduler recurrence editor.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function endTitle($value) {
        return $this->setProperty('endTitle', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor frequencies messages. Use this option to customize or localize the scheduler recurrence editor frequencies messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorFrequencies|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function frequencies($value) {
        return $this->setProperty('frequencies', $value);
    }

    /**
    * Applicable in Scheduler Adaptive rendering scenario. The text for the headerTitle (text in the header) on the scheduler recurrence editor.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function headerTitle($value) {
        return $this->setProperty('headerTitle', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor monthly messages. Use this option to customize or localize the scheduler recurrence editor monthly messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorMonthly|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function monthly($value) {
        return $this->setProperty('monthly', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor offsetPositions messages. Use this option to customize or localize the scheduler recurrence editor offsetPositions messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorOffsetPositions|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function offsetPositions($value) {
        return $this->setProperty('offsetPositions', $value);
    }

    /**
    * The text of the Recurence editor dropdown title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function recurrenceEditorTitle($value) {
        return $this->setProperty('recurrenceEditorTitle', $value);
    }

    /**
    * Applicable in Scheduler Adaptive rendering scenario. The text for the repeatTitle (repeat on) on the scheduler recurrence editor.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function repeatTitle($value) {
        return $this->setProperty('repeatTitle', $value);
    }

    /**
    * Applicable in Scheduler Adaptive rendering scenario. The text for the update button on the scheduler recurrence editor header.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function update($value) {
        return $this->setProperty('update', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor weekly messages. Use this option to customize or localize the scheduler recurrence editor weekly messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorWeekly|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function weekly($value) {
        return $this->setProperty('weekly', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor week days messages. Use these options to customize or localize the scheduler recurrence editor weekdays messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorWeekdays|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function weekdays($value) {
        return $this->setProperty('weekdays', $value);
    }

    /**
    * The configuration of the scheduler recurrence editor yearly messages. Use this option to customize or localize the scheduler recurrence editor yearly messages.
    * @param \Kendo\UI\SchedulerMessagesRecurrenceEditorYearly|array $value
    * @return \Kendo\UI\SchedulerMessagesRecurrenceEditor
    */
    public function yearly($value) {
        return $this->setProperty('yearly', $value);
    }

//<< Properties
}

?>
