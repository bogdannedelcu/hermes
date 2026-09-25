<?php

namespace Kendo\UI;

class FormItem extends \Kendo\SerializableObject {
//>> Properties

    /**
    * Defines the type of the item. Available options: "group".
    * @param string $value
    * @return \Kendo\UI\FormItem
    */
    public function type($value) {
        return $this->setProperty('type', $value);
    }

    /**
    * Maps to the model field which will be configured and sets the name of the input.
    * @param string $value
    * @return \Kendo\UI\FormItem
    */
    public function field($value) {
        return $this->setProperty('field', $value);
    }

    /**
    * Defines the editor widget type. Available options are: DropDown widgets - "AutoComplete", "DropDownList", "ComboBox", "MultiSelect", "DropDownTree", "MultiColumnComboBox"; DatePicker widgets - "DateInput", "DatePicker", "DateTimePicker", "TimePicker"; Input widgets - "TextBox", "TextArea", "NumericTextBox", "MaskedTextBox", "RadioGroup", "CheckBoxGroup", "Switch", "Rating", "Slider", "ColorPicker", "hidden" or Editor widget - "Editor".
    * @param string|\Kendo\JavaScriptFunction $value
    * @return \Kendo\UI\FormItem
    */
    public function editor($value) {
        return $this->setProperty('editor', $value);
    }

    /**
    * Defines the widget configuration for the specified items.editor.
    * @param  $value
    * @return \Kendo\UI\FormItem
    */
    public function editorOptions($value) {
        return $this->setProperty('editorOptions', $value);
    }

    /**
    * Specified the validation rules for the field.
    * @param  $value
    * @return \Kendo\UI\FormItem
    */
    public function validation($value) {
        return $this->setProperty('validation', $value);
    }

    /**
    * Defines the field label.
    * @param string|\Kendo\UI\FormItemLabel|array $value
    * @return \Kendo\UI\FormItem
    */
    public function label($value) {
        return $this->setProperty('label', $value);
    }

    /**
    * Defines the field id.
    * @param string $value
    * @return \Kendo\UI\FormItem
    */
    public function id($value) {
        return $this->setProperty('id', $value);
    }

    /**
    * Defines the field title.
    * @param string $value
    * @return \Kendo\UI\FormItem
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * Defines the hint text that will be shown underneath the form editor.
    * @param string $value
    * @return \Kendo\UI\FormItem
    */
    public function hint($value) {
        return $this->setProperty('hint', $value);
    }

    /**
    * Defines the field size when grid layout is used.
    * @param float $value
    * @return \Kendo\UI\FormItem
    */
    public function colSpan($value) {
        return $this->setProperty('colSpan', $value);
    }

    /**
    * Defines the attributes that are applied to the input element.
    * @param  $value
    * @return \Kendo\UI\FormItem
    */
    public function attributes($value) {
        return $this->setProperty('attributes', $value);
    }

//<< Properties

/**
    * Adds FormItem to the Form.
    * @param \Kendo\UI\FormItem|array,... $value one or more FormItem to add.
    * @return \Kendo\UI\FormItem
    */
    public function addItem($value) {
        return $this->add('items', func_get_args());
    }

    /**
    * Specify the layout of Form content. Valid options are: grid: This is equivalent to display: grid. It defines the form element as a grid container and establishes a new grid formatting context for its contents..
    * @param string $value
    * @return \Kendo\UI\FormItem
    */
    public function layout($value) {
        return $this->setProperty('layout', $value);
    }

    /**
    * Grid layout settings.
    * @param \Kendo\UI\FormGrid|array $value
    * @return \Kendo\UI\FormItem
    */
    public function grid($value) {
        return $this->setProperty('grid', $value);
    }

    public function editorFunction($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('editor', $value);
    }
}

?>
