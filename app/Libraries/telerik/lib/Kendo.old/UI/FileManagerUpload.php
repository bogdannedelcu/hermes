<?php

namespace Kendo\UI;

class FileManagerUpload extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the cancel option of the FileManagerUpload.
    * Fires when the upload was cancelled while in progress. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function cancel($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('cancel', $value);
    }

    /**
    * Sets the clear option of the FileManagerUpload.
    * Fires when the files are cleared by clicking on the Clear button. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function clear($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('clear', $value);
    }

    /**
    * Sets the complete option of the FileManagerUpload.
    * Fires when all active uploads complete—either successfully or with errors. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function complete($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('complete', $value);
    }

    /**
    * Sets the error option of the FileManagerUpload.
    * Fires when an upload or remove operation fails. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function error($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('error', $value);
    }

    /**
    * Sets the pause option of the FileManagerUpload.
    * Fires when the files are cleared by clicking the Pause button. The button is visible if chunksize is set. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function pause($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('pause', $value);
    }

    /**
    * Sets the progress option of the FileManagerUpload.
    * Fires when the data about the progress of the upload is available. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function progress($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('progress', $value);
    }

    /**
    * Sets the resume option of the FileManagerUpload.
    * Fires when the files are resumed through clicking the Resume button. The button is visible if chunksize is set and the file upload is paused. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function resume($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('resume', $value);
    }

    /**
    * Sets the remove option of the FileManagerUpload.
    * Fires when an uploaded file is about to be removed. If the event is canceled, the remove operation is prevented. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function remove($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('remove', $value);
    }

    /**
    * Sets the select option of the FileManagerUpload.
    * Fires when a file is selected. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }

    /**
    * Sets the success option of the FileManagerUpload.
    * Fires when an upload or remove operation is completed successfully. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function success($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('success', $value);
    }

    /**
    * Sets the upload option of the FileManagerUpload.
    * Fires when one or more files are about to be uploaded. The canceling of the event prevents the upload. Upload Events.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\FileManagerUpload
    */
    public function upload($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('upload', $value);
    }

    /**
    * Configures the asynchronous upload of files. For more details, refer to the article on the [async mode](/web/upload/modes#asynchronous-mode) of the Upload.
    * @param \Kendo\UI\FileManagerUploadAsync|array $value
    * @return \Kendo\UI\FileManagerUpload
    */
    public function async($value) {
        return $this->setProperty('async', $value);
    }

    /**
    * Enables the selection of folders instead of files.
    * @param boolean $value
    * @return \Kendo\UI\FileManagerUpload
    */
    public function directory($value) {
        return $this->setProperty('directory', $value);
    }

    /**
    * Sets the strings rendered by the Upload.
    * @param \Kendo\UI\FileManagerUploadLocalization|array $value
    * @return \Kendo\UI\FileManagerUpload
    */
    public function localization($value) {
        return $this->setProperty('localization', $value);
    }

    /**
    * Enables (if set to `true`) or disables (if set to `false`) the selection of multiple files. If set to `false`, the user can select only one file at a time.
    * @param boolean $value
    * @return \Kendo\UI\FileManagerUpload
    */
    public function multiple($value) {
        return $this->setProperty('multiple', $value);
    }

    /**
    * Enables (if set to `true`) or disables (if set to `false`) the display of a file listing for the file upload. The disabling of a file listing might be useful if you want to customize the UI. To build your own UI, use the client-side events.
    * @param boolean $value
    * @return \Kendo\UI\FileManagerUpload
    */
    public function showFileList($value) {
        return $this->setProperty('showFileList', $value);
    }

    /**
    * Configures the validation options for uploaded files.
    * @param \Kendo\UI\FileManagerUploadValidation|array $value
    * @return \Kendo\UI\FileManagerUpload
    */
    public function validation($value) {
        return $this->setProperty('validation', $value);
    }

//<< Properties
}

?>
