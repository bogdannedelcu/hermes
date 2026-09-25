<?php

namespace Kendo\UI;

class ScrollViewPageable extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Sets the ARIATemplate option of the ScrollViewPageable.
    * Specifies a template is used to populate the aria-label for each pager element.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\ScrollViewPageable
    */
    public function ARIATemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('ARIATemplate', $value);
    }

    /**
    * Sets the ARIATemplate option of the ScrollViewPageable.
    * Specifies a template is used to populate the aria-label for each pager element.
    * @param string $value The template content.
    * @return \Kendo\UI\ScrollViewPageable
    */
    public function ARIATemplate($value) {
        return $this->setProperty('ARIATemplate', $value);
    }

//<< Properties
}

?>
