<?php

namespace Kendo\UI;

class ListView extends \Kendo\UI\Widget {
    protected function name() {
        return 'ListView';
    }

    protected function createElement() {
        $tagName = $this->getProperty('tagName');
        if ($tagName == NULL) {
            $tagName = 'div';
        }

        return new \Kendo\Html\Element($tagName);
    }

    public function html() {
        $html = parent::html();

        if ($this->getProperty('pageable')) {
            $pager = new \Kendo\Html\Element('div');
            $pager->attr('id', "{$this->id}_pager");


            $html = "$html {$pager->outerHtml()}";
        }

        return $html;
    }

    public function properties() {
        $properties = parent::properties();

        //remove tagName from init script
        unset($properties['tagName']);

        //adjust pager settings
        if(array_key_exists("pageable", $properties)) {
            $pageable = $properties['pageable'];
            if ($pageable) {
                if (!is_array($pageable)) {
                    $pageable = array();
                }

                $pageable['pagerId'] = "{$this->id}_pager";
                $properties['pageable'] = $pageable;
            }
        }

        return $properties;
    }

//>> Properties

    /**
    * If set to false the widget will not bind to the data source during initialization. In this case data binding will occur when the change event of the data source is fired. By default the widget will bind to the data source specified in the configuration.
    * @param boolean $value
    * @return \Kendo\UI\ListView
    */
    public function autoBind($value) {
        return $this->setProperty('autoBind', $value);
    }

    /**
    * Renders border around the listview element.
    * @param boolean $value
    * @return \Kendo\UI\ListView
    */
    public function bordered($value) {
        return $this->setProperty('bordered', $value);
    }

    /**
    * Renders border around the listview items. Valid values are: all: renders borders around listview items.; horizontal: renders top border of listview items. Useful when setting layout: "flex" and flex.direction: column. or vertical: renders top border of listview items. Useful when setting layout: "flex" and flex.direction: row..
    * @param string $value
    * @return \Kendo\UI\ListView
    */
    public function borders($value) {
        return $this->setProperty('borders', $value);
    }

    /**
    * Defines the type of element that holds the listview content.
    * @param string $value
    * @return \Kendo\UI\ListView
    */
    public function contentElement($value) {
        return $this->setProperty('contentElement', $value);
    }

    /**
    * Sets the data source of the ListView.
    * @param array|\Kendo\Data\DataSource $value
    * @return \Kendo\UI\ListView
    */
    public function dataSource($value) {
        return $this->setProperty('dataSource', $value);
    }

    /**
    * The height of the listview. Numeric values are treated as pixels.
    * @param float|string $value
    * @return \Kendo\UI\ListView
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Specify the layout of listview content. Valid options are: flex: This is equivalent to display: flex. It defines a flex container and enables a flex context for all its direct children. Think of flex items as primarily laying out either in horizontal rows or vertical columns. or grid: This is equivalent to display: grid. It defines the element as a grid container and establishes a new grid formatting context for its contents..
    * @param string $value
    * @return \Kendo\UI\ListView
    */
    public function layout($value) {
        return $this->setProperty('layout', $value);
    }

    /**
    * Flex layout settings
    * @param \Kendo\UI\ListViewFlex|array $value
    * @return \Kendo\UI\ListView
    */
    public function flex($value) {
        return $this->setProperty('flex', $value);
    }

    /**
    * Grid layout settings.
    * @param \Kendo\UI\ListViewGrid|array $value
    * @return \Kendo\UI\ListView
    */
    public function grid($value) {
        return $this->setProperty('grid', $value);
    }

    /**
    * If set to true the listview will display a scrollbar when the content exceeds the listview height value. By default scrolling is disabled.It could be also set to endless in order to enable the endless scrolling functionality. In endless scrolling mode the height should be configured to display a scrollbar. Scrolling to the end of the scrollbar will load more items (equal to the pageSize number) and append them to the listview DOM element utill all items are loaded and displayed.
    * @param boolean|string $value
    * @return \Kendo\UI\ListView
    */
    public function scrollable($value) {
        return $this->setProperty('scrollable', $value);
    }

    /**
    * Indicates whether keyboard navigation is enabled/disabled.
    * @param boolean $value
    * @return \Kendo\UI\ListView
    */
    public function navigatable($value) {
        return $this->setProperty('navigatable', $value);
    }

    /**
    * Indicates whether selection is enabled/disabled. Possible values:
    * @param boolean|string $value
    * @return \Kendo\UI\ListView
    */
    public function selectable($value) {
        return $this->setProperty('selectable', $value);
    }

    /**
    * Sets the template option of the ListView.
    * The id of the template used for rendering the items in the listview.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\ListView
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the ListView.
    * The id of the template used for rendering the items in the listview.
    * @param string $value The template content.
    * @return \Kendo\UI\ListView
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Sets the editTemplate option of the ListView.
    * Specifies ListView item template in edit mode.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\ListView
    */
    public function editTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('editTemplate', $value);
    }

    /**
    * Sets the editTemplate option of the ListView.
    * Specifies ListView item template in edit mode.
    * @param string $value The template content.
    * @return \Kendo\UI\ListView
    */
    public function editTemplate($value) {
        return $this->setProperty('editTemplate', $value);
    }

    /**
    * Sets the altTemplate option of the ListView.
    * Template to be used for rendering the alternate items in the listview.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\ListView
    */
    public function altTemplateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('altTemplate', $value);
    }

    /**
    * Sets the altTemplate option of the ListView.
    * Template to be used for rendering the alternate items in the listview.
    * @param string $value The template content.
    * @return \Kendo\UI\ListView
    */
    public function altTemplate($value) {
        return $this->setProperty('altTemplate', $value);
    }

    /**
    * Indicates whether paging is enabled/disabled.
    * @param boolean|\Kendo\UI\ListViewPageable|array $value
    * @return \Kendo\UI\ListView
    */
    public function pageable($value) {
        return $this->setProperty('pageable', $value);
    }

    /**
    * Specifies ListView wrapper element tag name.
    * @param string $value
    * @return \Kendo\UI\ListView
    */
    public function tagName($value) {
        return $this->setProperty('tagName', $value);
    }

    /**
    * Sets the cancel event of the ListView.
    * Fired when the user clicks the "cancel" button.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function cancel($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('cancel', $value);
    }

    /**
    * Sets the change event of the ListView.
    * Fires when the ListView selection has changed.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the dataBound event of the ListView.
    * Fires when the ListView has received data from the DataSource and it is already rendered.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function dataBound($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBound', $value);
    }

    /**
    * Sets the dataBinding event of the ListView.
    * Fires when the ListView is about to be bound.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function dataBinding($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBinding', $value);
    }

    /**
    * Sets the edit event of the ListView.
    * Fires when the ListView enters edit mode.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function edit($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('edit', $value);
    }

    /**
    * Sets the remove event of the ListView.
    * Fires before the list view item is put in edit mode. If the event is not prevented, the ListView will call the DataSource sync method.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function remove($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('remove', $value);
    }

    /**
    * Sets the save event of the ListView.
    * Fired when a data item is saved.The event handler function context (available via the this keyword) will be set to the widget instance.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\ListView
    */
    public function save($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('save', $value);
    }


//<< Properties
}

?>
