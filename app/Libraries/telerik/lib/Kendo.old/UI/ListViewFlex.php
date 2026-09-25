<?php

namespace Kendo\UI;

class ListViewFlex extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the direction flex items are placed in the flex container. Think of flex items as primarily laying out either in horizontal rows or vertical columns. Valid values are: row: This is equivalent to flex-direction: row. This establishes the main-axis to be horizontal, thus defining the direction flex items are placed in the flex container: left to right in ltr; right to left in rtl.; row-reverse: This is equivalent to flex-direction: row-reverse. This establishes the main-axis to be horizontal, thus defining the direction flex items are placed in the flex container: right to left in ltr; left to right in rtl.; column: This is equivalent to flex-direction: column. This establishes the main-axis to be vertical, thus defining the direction flex items are placed in the flex container: top to bottom. or column-reverse: This is equivalent to flex-direction: column-reverse. This establishes the main-axis to be vertical, thus defining the direction flex items are placed in the flex container: bottom to top..
    * @param string $value
    * @return \Kendo\UI\ListViewFlex
    */
    public function direction($value) {
        return $this->setProperty('direction', $value);
    }

    /**
    * By default, flex items will all try to fit onto one line. Customizing the property defines how items wrap or not within flex continer. Valid values are: wrap: This is equivalent to flex-wrap: wrap. It allows flex items to wrap as needed onto multiple lines, from top to bottom.; nowrap: This is equivalent to flex-wrap: nowrap. All flex items will be on one line. or wrap-reverse:This is equivalent to flex-wrap: wrap-reverse. It allows flex items to wrap as needed onto multiple lines, from bottom to top..
    * @param string $value
    * @return \Kendo\UI\ListViewFlex
    */
    public function wrap($value) {
        return $this->setProperty('wrap', $value);
    }

//<< Properties
}

?>
