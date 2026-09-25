<?php

namespace Kendo\UI;

class ImageEditorMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the localization messages for the toolbar.
    * @param \Kendo\UI\ImageEditorMessagesToolbar|array $value
    * @return \Kendo\UI\ImageEditorMessages
    */
    public function toolbar($value) {
        return $this->setProperty('toolbar', $value);
    }

    /**
    * Defines the localization Pane tools.
    * @param \Kendo\UI\ImageEditorMessagesPanes|array $value
    * @return \Kendo\UI\ImageEditorMessages
    */
    public function panes($value) {
        return $this->setProperty('panes', $value);
    }

    /**
    * Defines the localization for the common fields.
    * @param \Kendo\UI\ImageEditorMessagesCommon|array $value
    * @return \Kendo\UI\ImageEditorMessages
    */
    public function common($value) {
        return $this->setProperty('common', $value);
    }

//<< Properties
}

?>
