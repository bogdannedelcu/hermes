<?php

namespace Kendo\Dataviz\UI;

class QRCodeOverlay extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The height of the overlay in pixels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\QRCodeOverlay
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Available options are image and swiss. When set to image you have to specify the url source of the image. If set to swiss a Swiss QR Code is created.
    * @param string $value
    * @return \Kendo\Dataviz\UI\QRCodeOverlay
    */
    public function type($value) {
        return $this->setProperty('type', $value);
    }

    /**
    * The URL of the displayed overlay image.
    * @param string $value
    * @return \Kendo\Dataviz\UI\QRCodeOverlay
    */
    public function imageUrl($value) {
        return $this->setProperty('imageUrl', $value);
    }

    /**
    * The width of the overlay in pixels.
    * @param float $value
    * @return \Kendo\Dataviz\UI\QRCodeOverlay
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

//<< Properties
}

?>
