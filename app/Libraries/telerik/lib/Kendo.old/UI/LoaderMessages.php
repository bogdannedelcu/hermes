<?php

namespace Kendo\UI;

class LoaderMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The aria label text for the loader.
    * @param string $value
    * @return \Kendo\UI\LoaderMessages
    */
    public function loading($value) {
        return $this->setProperty('loading', $value);
    }

//<< Properties
}

?>
