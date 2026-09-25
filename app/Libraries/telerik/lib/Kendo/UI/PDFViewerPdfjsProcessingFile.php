<?php

namespace Kendo\UI;

class PDFViewerPdfjsProcessingFile extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Specifies the the URL where the predefined Adobe CMaps are located. Further info in the PDF.js API ref.
    * @param string $value
    * @return \Kendo\UI\PDFViewerPdfjsProcessingFile
    */
    public function cMapUrl($value) {
        return $this->setProperty('cMapUrl', $value);
    }

    /**
    * Specifies if the Adobe CMaps are binary packed. Further info in the PDF.js API ref.
    * @param boolean $value
    * @return \Kendo\UI\PDFViewerPdfjsProcessingFile
    */
    public function cMapPacked($value) {
        return $this->setProperty('cMapPacked', $value);
    }

    /**
    * Specifies the data to be passed to the pdfjs processor. Accepts blob, byte array or base64 string.
    * @param string $value
    * @return \Kendo\UI\PDFViewerPdfjsProcessingFile
    */
    public function data($value) {
        return $this->setProperty('data', $value);
    }

    /**
    * Specifies the url to be passed to the pdfjs processor.
    * @param string $value
    * @return \Kendo\UI\PDFViewerPdfjsProcessingFile
    */
    public function url($value) {
        return $this->setProperty('url', $value);
    }

//<< Properties
}

?>
