<?php

namespace Kendo\UI;

class ImageEditorSaveAs extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the filename of the saved file.
    * @param string $value
    * @return \Kendo\UI\ImageEditorSaveAs
    */
    public function fileName($value) {
        return $this->setProperty('fileName', $value);
    }

    /**
    * If set to true, the content will be forwarded to proxyURL even if the browser supports saving files locally.
    * @param boolean $value
    * @return \Kendo\UI\ImageEditorSaveAs
    */
    public function forceProxy($value) {
        return $this->setProperty('forceProxy', $value);
    }

    /**
    * The URL of the server side proxy which will stream the file to the end user. A proxy will be used when the browser isn't capable of saving files locally. Such browsers are IE version 9 and lower and Safari.
    * @param string $value
    * @return \Kendo\UI\ImageEditorSaveAs
    */
    public function proxyURL($value) {
        return $this->setProperty('proxyURL', $value);
    }

    /**
    * A name or keyword indicating where to display the document returned from the proxy.
    * @param string $value
    * @return \Kendo\UI\ImageEditorSaveAs
    */
    public function proxyTarget($value) {
        return $this->setProperty('proxyTarget', $value);
    }

//<< Properties
}

?>
