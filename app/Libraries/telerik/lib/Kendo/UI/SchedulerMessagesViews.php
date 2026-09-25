<?php

namespace Kendo\UI;

class SchedulerMessagesViews extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The text similar to "Day" displayed as scheduler "day" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function day($value) {
        return $this->setProperty('day', $value);
    }

    /**
    * The text similar to "Week" displayed as scheduler "week" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function week($value) {
        return $this->setProperty('week', $value);
    }

    /**
    * The text similar to "Month" displayed as scheduler "month" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function month($value) {
        return $this->setProperty('month', $value);
    }

    /**
    * The text similar to "Agenda" displayed as scheduler "agenda" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function agenda($value) {
        return $this->setProperty('agenda', $value);
    }

    /**
    * The text similar to "Timelnie Month" displayed as scheduler "timelineMonth" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function timelineMonth($value) {
        return $this->setProperty('timelineMonth', $value);
    }

    /**
    * The text similar to "Timelnie Week" displayed as scheduler "timelineWeek" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function timelineWeek($value) {
        return $this->setProperty('timelineWeek', $value);
    }

    /**
    * The text similar to "Year" displayed as scheduler "year" view title.
    * @param string $value
    * @return \Kendo\UI\SchedulerMessagesViews
    */
    public function year($value) {
        return $this->setProperty('year', $value);
    }

//<< Properties
}

?>
