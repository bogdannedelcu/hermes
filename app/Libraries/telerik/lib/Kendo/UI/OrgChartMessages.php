<?php

namespace Kendo\UI;

class OrgChartMessages extends \Kendo\SerializableObject {
//>> Properties

    /**
    * The label that will be used for the aria-label value of the OrgChart widget.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * The message that will be used for the create action in the edit pop-up Menu.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function create($value) {
        return $this->setProperty('create', $value);
    }

    /**
    * The message that will be used for the edit action in the edit pop-up Menu.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function edit($value) {
        return $this->setProperty('edit', $value);
    }

    /**
    * The message that will be used for the destroy action in the edit pop-up Menu.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function destroy($value) {
        return $this->setProperty('destroy', $value);
    }

    /**
    * The message that will be used for the destroy confirmation pop-up content.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function destroyContent($value) {
        return $this->setProperty('destroyContent', $value);
    }

    /**
    * The message that will be used for the destroy confirmation pop-up title.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function destroyTitle($value) {
        return $this->setProperty('destroyTitle', $value);
    }

    /**
    * The message that will be used for the cancel action button text.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function cancel($value) {
        return $this->setProperty('cancel', $value);
    }

    /**
    * The message that will be used for save action button text.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function save($value) {
        return $this->setProperty('save', $value);
    }

    /**
    * The message that will be used for the aria-label of the edit pop-up Menu.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function menuLabel($value) {
        return $this->setProperty('menuLabel', $value);
    }

    /**
    * The message that will be used for the avatar Upload's label.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function uploadAvatar($value) {
        return $this->setProperty('uploadAvatar', $value);
    }

    /**
    * The message that will be used for the "parent" text.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function parent($value) {
        return $this->setProperty('parent', $value);
    }

    /**
    * The message that will be used for the "name" text.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function name($value) {
        return $this->setProperty('name', $value);
    }

    /**
    * The message that will be used for the "title" text.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * The message that will be used in the Parent DropDownList editor to specify the null option (no parent).
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function none($value) {
        return $this->setProperty('none', $value);
    }

    /**
    * The message that will be used to label the Expand button.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function expand($value) {
        return $this->setProperty('expand', $value);
    }

    /**
    * The message that will be used to label the Collapse button.
    * @param string $value
    * @return \Kendo\UI\OrgChartMessages
    */
    public function collapse($value) {
        return $this->setProperty('collapse', $value);
    }

//<< Properties
}

?>
