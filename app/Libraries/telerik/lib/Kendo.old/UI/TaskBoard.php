<?php

namespace Kendo\UI;

class TaskBoard extends \Kendo\UI\Widget {
    public function name() {
        return 'TaskBoard';
    }
//>> Properties

    /**
    * Controls whether to bind the TaskBoard to the data source on initialization.
    * @param boolean $value
    * @return \Kendo\UI\TaskBoard
    */
    public function autoBind($value) {
        return $this->setProperty('autoBind', $value);
    }

    /**
    * Adds TaskBoardCardMenuItem to the TaskBoard.
    * @param \Kendo\UI\TaskBoardCardMenuItem|array,... $value one or more TaskBoardCardMenuItem to add.
    * @return \Kendo\UI\TaskBoard
    */
    public function addCardMenuItem($value) {
        return $this->add('cardMenu', func_get_args());
    }

    /**
    * Sets the DataSource for the Columns of the TaskBoard. Can be bound to a remote service or local data.
    * @param |array| $value
    * @return \Kendo\UI\TaskBoard
    */
    public function columns($value) {
        return $this->setProperty('columns', $value);
    }

    /**
    * Defines the settings for the columns.
    * @param \Kendo\UI\TaskBoardColumnSettings|array $value
    * @return \Kendo\UI\TaskBoard
    */
    public function columnSettings($value) {
        return $this->setProperty('columnSettings', $value);
    }

    /**
    * The field used to order cards (number based). If not set, cards will be rendered in the order they are fetched. And ordering will not be applied to the DataSource and respectively, not synced with the remote data source.
    * @param string $value
    * @return \Kendo\UI\TaskBoard
    */
    public function dataOrderField($value) {
        return $this->setProperty('dataOrderField', $value);
    }

    /**
    * The category field of the card.
    * @param string $value
    * @return \Kendo\UI\TaskBoard
    */
    public function dataCategoryField($value) {
        return $this->setProperty('dataCategoryField', $value);
    }

    /**
    * The description field of the card.
    * @param string $value
    * @return \Kendo\UI\TaskBoard
    */
    public function dataDescriptionField($value) {
        return $this->setProperty('dataDescriptionField', $value);
    }

    /**
    * Sets the data source of the TaskBoard.
    * @param array|\Kendo\Data\DataSource $value
    * @return \Kendo\UI\TaskBoard
    */
    public function dataSource($value) {
        return $this->setProperty('dataSource', $value);
    }

    /**
    * The field of the data item that provides the status of the card. Mapped with the status of the columns.
    * @param string $value
    * @return \Kendo\UI\TaskBoard
    */
    public function dataStatusField($value) {
        return $this->setProperty('dataStatusField', $value);
    }

    /**
    * The title field of the card.
    * @param string $value
    * @return \Kendo\UI\TaskBoard
    */
    public function dataTitleField($value) {
        return $this->setProperty('dataTitleField', $value);
    }

    /**
    * Toggles the editing in the TaskBoard. Both for columns and cards.
    * @param boolean|\Kendo\UI\TaskBoardEditable|array $value
    * @return \Kendo\UI\TaskBoard
    */
    public function editable($value) {
        return $this->setProperty('editable', $value);
    }

    /**
    * Configures the height of the TaskBoard wrapper.
    * @param string|float $value
    * @return \Kendo\UI\TaskBoard
    */
    public function height($value) {
        return $this->setProperty('height', $value);
    }

    /**
    * Toggles the previewPane in the TaskBoard.
    * @param boolean|\Kendo\UI\TaskBoardPreviewPane|array $value
    * @return \Kendo\UI\TaskBoard
    */
    public function previewPane($value) {
        return $this->setProperty('previewPane', $value);
    }

    /**
    * Toggles the reordering of cards in the TaskBoard.
    * @param boolean $value
    * @return \Kendo\UI\TaskBoard
    */
    public function reorderable($value) {
        return $this->setProperty('reorderable', $value);
    }

    /**
    * Adds TaskBoardResource to the TaskBoard.
    * @param \Kendo\UI\TaskBoardResource|array,... $value one or more TaskBoardResource to add.
    * @return \Kendo\UI\TaskBoard
    */
    public function addResource($value) {
        return $this->add('resources', func_get_args());
    }

    /**
    * Toggles the selection of the TaskBoard.
    * @param boolean $value
    * @return \Kendo\UI\TaskBoard
    */
    public function selectable($value) {
        return $this->setProperty('selectable', $value);
    }

    /**
    * Sets the template option of the TaskBoard.
    * Controls the rendering of the card.
    * @param string $value The id of the element which represents the kendo template.
    * @return \Kendo\UI\TaskBoard
    */
    public function templateId($value) {
        $value = new \Kendo\Template($value);

        return $this->setProperty('template', $value);
    }

    /**
    * Sets the template option of the TaskBoard.
    * Controls the rendering of the card.
    * @param string $value The template content.
    * @return \Kendo\UI\TaskBoard
    */
    public function template($value) {
        return $this->setProperty('template', $value);
    }

    /**
    * Configures the Tools of the TaskBoard
    * @param boolean|\Kendo\UI\TaskBoardToolbar|array $value
    * @return \Kendo\UI\TaskBoard
    */
    public function toolbar($value) {
        return $this->setProperty('toolbar', $value);
    }

    /**
    * Configures the width of the TaskBoard wrapper.
    * @param string|float $value
    * @return \Kendo\UI\TaskBoard
    */
    public function width($value) {
        return $this->setProperty('width', $value);
    }

    /**
    * Provides configuration options for the messages present in the TaskBoard widget.
    * @param \Kendo\UI\TaskBoardMessages|array $value
    * @return \Kendo\UI\TaskBoard
    */
    public function messages($value) {
        return $this->setProperty('messages', $value);
    }

    /**
    * Sets the columnsDataBinding event of the TaskBoard.
    * Fired before the TaskBoard binds the columns' data source.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function columnsDataBinding($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('columnsDataBinding', $value);
    }

    /**
    * Sets the columnsDataBound event of the TaskBoard.
    * Fired when the TaskBoard's columns are bound to their data source.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function columnsDataBound($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('columnsDataBound', $value);
    }

    /**
    * Sets the select event of the TaskBoard.
    * Fired when the user selects a card in the TaskBoard.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function select($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('select', $value);
    }

    /**
    * Sets the dataBinding event of the TaskBoard.
    * Fired before the TaskBoard binds to its data source.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function dataBinding($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBinding', $value);
    }

    /**
    * Sets the dataBound event of the TaskBoard.
    * Fired when the TaskBoard is bound to data from its data source.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function dataBound($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('dataBound', $value);
    }

    /**
    * Sets the deleteCard event of the TaskBoard.
    * Fired when the user deletes a card.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function deleteCard($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('deleteCard', $value);
    }

    /**
    * Sets the deleteColumn event of the TaskBoard.
    * Fired when the user deletes a column.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function deleteColumn($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('deleteColumn', $value);
    }

    /**
    * Sets the editCard event of the TaskBoard.
    * Fired when the user edits or creates a card.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function editCard($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('editCard', $value);
    }

    /**
    * Sets the editColumn event of the TaskBoard.
    * Fired when the user edits or creates a column.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function editColumn($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('editColumn', $value);
    }

    /**
    * Sets the execute event of the TaskBoard.
    * Fires when a command is executed.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function execute($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('execute', $value);
    }

    /**
    * Sets the move event of the TaskBoard.
    * Fired when the user moves a card.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function move($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('move', $value);
    }

    /**
    * Sets the moveEnd event of the TaskBoard.
    * Fired when the user dropped a card in a column.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function moveEnd($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('moveEnd', $value);
    }

    /**
    * Sets the moveStart event of the TaskBoard.
    * Fired when the user started moving a card.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function moveStart($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('moveStart', $value);
    }

    /**
    * Sets the change event of the TaskBoard.
    * Fired when the user changed the card order or status by dragging.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function change($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('change', $value);
    }

    /**
    * Sets the saveCard event of the TaskBoard.
    * Fired when the user saves a card.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function saveCard($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('saveCard', $value);
    }

    /**
    * Sets the saveColumn event of the TaskBoard.
    * Fired when the user saves a column.
    * @param string|\Kendo\JavaScriptFunction $value Can be a JavaScript function definition or name.
    * @return \Kendo\UI\TaskBoard
    */
    public function saveColumn($value) {
        if (is_string($value)) {
            $value = new \Kendo\JavaScriptFunction($value);
        }

        return $this->setProperty('saveColumn', $value);
    }


//<< Properties
}

?>
