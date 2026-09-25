<?php

namespace Kendo\UI;

class ExpansionPanel extends \Kendo\UI\Widget {
    public function name() {
        return 'ExpansionPanel';
    }

    protected function createElement() {
        $element = new \Kendo\Html\Element('div');

        $content = $this->getProperty('content');

        if($content) {
            $element->html($content);
        }

        return $element;
    }

    public function html() {
        $element = $this->createElement();

        $this->addAttributes($element);

        return $element->outerHtml();
    }
//>> Properties

    /**
    * A collection of visual animations used when ExpansionPanel is expand or collapsed through user interactions. Setting this option to false will disable all animations. is not a valid configuration.
    * @param boolean|\Kendo\UI\ExpansionPanelAnimation|array $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function animation($value) {
        return $this->setProperty('animation', $value);
    }

    /**
    * The class of the collapse icon.
    * @param string $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function collapseIconClass($value) {
        return $this->setProperty('collapseIconClass', $value);
    }

    /**
    * If set to true the widget will be disabled.
    * @param boolean $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function disabled($value) {
        return $this->setProperty('disabled', $value);
    }

    /**
    * If set to true the widget will be expanded by default.
    * @param boolean $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function expanded($value) {
        return $this->setProperty('expanded', $value);
    }

    /**
    * The class of the collapse icon.
    * @param string $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function expandIconClass($value) {
        return $this->setProperty('expandIconClass', $value);
    }

    /**
    * The height of the widget. Numeric values are treated as pixels.
    * @param float|string $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * The subtitle of the widget.
    * @param string $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function subTitle($value) {
        return $this->setProperty('subTitle', $value);
    }

    /**
    * The title of the widget.
    * @param string $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function title($value) {
        return $this->setProperty('title', $value);
    }

    /**
    * If set to false the user will not be able to expand/collapse the widget.
    * @param boolean $value
    * @return \Kendo\UI\ExpansionPanel
    */
    public function toggleable($value) {
        return $this->setProperty('toggleable', $value);
    }

    /**
    * Sets the expand event of the ExpansionPanel.
    * Fired when the widget is expanded.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ExpansionPanel
    */
    public function expand($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('expand', $value);
    }

    /**
    * Sets the collapse event of the ExpansionPanel.
    * Fired when the widget is collapsed.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ExpansionPanel
    */
    public function collapse($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('collapse', $value);
    }

    /**
    * Sets the complete event of the ExpansionPanel.
    * Fired when the animation during collapse/expand is completed.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ExpansionPanel
    */
    public function complete($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('complete', $value);
    }


//<< Properties

    /**
    * Specifies the associated content for the drawer
    * @param string $value
    * @return \Kendo\UI\Drawer
    */
    public function content($value) {
        return $this->setProperty('content', $value);
    }

/**
    * Starts output bufferring. Any following markup will be set as the content of the ScrollViewItem.
    */
    public function startContent() {
        ob_start();
    }

    /**
    * Stops output bufferring and sets the preceding markup as the content of the ScrollViewItem.
    */
    public function endContent() {
        $this->content(ob_get_clean());
    }
}

?>
