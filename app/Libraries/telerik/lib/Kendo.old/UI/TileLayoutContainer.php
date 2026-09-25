<?php

namespace Kendo\UI;

class TileLayoutContainer extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the bodyTemplate option of the TileLayoutContainer.
    * The template which renders as content for the tile item.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TileLayoutContainer
    */
    public function bodyTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('bodyTemplate', $value);
    }

    /**
    * Sets the bodyTemplate option of the TileLayoutContainer.
    * The template which renders as content for the tile item.
    * @param string $value The template content.
    * @return \Kendo\UI\TileLayoutContainer
    */
    public function bodyTemplate($value) {
        return $this->setProperty('bodyTemplate', $value);
    }

    /**
    * A value that determines how many columns will the tile item span.
    * @param float $value
    * @return \Kendo\UI\TileLayoutContainer
    */
    public function colSpan($value) {
        return $this->setProperty('colSpan', $value);
    }

    /**
    * Holds the configuration settings for the header.
    * @param \Kendo\UI\TileLayoutContainerHeader|array $value
    * @return \Kendo\UI\TileLayoutContainer
    */
    public function header($value) {
        return $this->setProperty('header', $value);
    }

    /**
    * A value that determines how many rows will the tile item span.
    * @param float $value
    * @return \Kendo\UI\TileLayoutContainer
    */
    public function rowSpan($value) {
        return $this->setProperty('rowSpan', $value);
    }

//<< Properties
}

?>
