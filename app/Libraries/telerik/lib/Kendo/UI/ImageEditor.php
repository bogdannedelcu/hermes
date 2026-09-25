<?php

namespace Kendo\UI;

class ImageEditor extends \Kendo\UI\Widget {
    public function name() {
        return 'ImageEditor';
    }
//>> Properties

    /**
    * Configures the width of the ImageEditor.
    * @param float|string $value
    * @return \Kendo\UI\ImageEditor
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Configures the width of the ImageEditor.
    * @param float|string $value
    * @return \Kendo\UI\ImageEditor
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Sets the url (or base64 string) for the image to open.
    * @param string $value
    * @return \Kendo\UI\ImageEditor
    */
    public function imageUrl($value) {
        return $this->setProperty('imageUrl', $value);
    }

    /**
    * Sets the saveAs options for the save command. Utilizes the kendo.saveAs method.
    * @param \Kendo\UI\ImageEditorSaveAs|array $value
    * @return \Kendo\UI\ImageEditor
    */
    public function saveAs($value) {
        return $this->setProperty('saveAs', $value);
    }

    /**
    * Configures the Toolbar of the ImageEditor
    * @param boolean|\Kendo\UI\ImageEditorToolbar|array $value
    * @return \Kendo\UI\ImageEditor
    */
    public function toolbar($value) {
        return $this->setProperty('toolbar', $value);
    }

    /**
    * Defines the text of the localizable UI parts of the FileManager.
    * @param \Kendo\UI\ImageEditorMessages|array $value
    * @return \Kendo\UI\ImageEditor
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Sets the imageLoaded event of the ImageEditor.
    * Fired when image is loaded. Can prevent the image rendering by calling the preventDefault() method.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditor
    */
    public function imageLoaded($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('imageLoaded', $value);
    }

    /**
    * Sets the imageRendered event of the ImageEditor.
    * Fired when canvas is rendered with the image. This event is triggered by commands and when drawCanvas method is called.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditor
    */
    public function imageRendered($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('imageRendered', $value);
    }

    /**
    * Sets the execute event of the ImageEditor.
    * Fired when a command is executed.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditor
    */
    public function execute($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('execute', $value);
    }

    /**
    * Sets the error event of the ImageEditor.
    * Fired when an error happened with the image loading.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ImageEditor
    */
    public function error($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('error', $value);
    }


//<< Properties
}

?>
