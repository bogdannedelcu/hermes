<?php

namespace Kendo\UI;

class TileLayoutContainerHeader extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the template option of the TileLayoutContainerHeader.
    * The template that will be rendered as a header.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TileLayoutContainerHeader
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the TileLayoutContainerHeader.
    * The template that will be rendered as a header.
    * @param string $value The template content.
    * @return \Kendo\UI\TileLayoutContainerHeader
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * The text that will be rendered as a header.
    * @param string $value
    * @return \Kendo\UI\TileLayoutContainerHeader
    */
    public function text($value) {
        return $this->setProperty('text', $value);
    }

//<< Properties
}

?>
