<?php


namespace Lune\Html\Table;


class Column
{
    private $id;
    private $label;
    private $handlers;
    private $sortable;

    public function __construct($id, $label, $handler = null, $sortable = false)
    {
        $this->id = $id;
        $this->label = $label;
        $this->handlers = $this->createHandlers($handler);
        $this->sortable = $sortable;
    }

    private function createHandlers($handler = null)
    {
        if (is_null($handler)) {
            $id = $this->id;
            return $this->createHandlers(function (array $row) use ($id) {
                return $row[$id]??'';
            }, $id);
        }

        if (!is_array($handler) || is_callable($handler)) {
            return [$handler];
        }

        return $handler;
    }


    public function renderCells($row = []):array
    {
        $cells = [];
        foreach ($this->handlers as $handler) {
            $cells[] = $handler($row);
        }
        return $cells;
    }

    /**
     * @return boolean
     */
    public function isSortable(): bool
    {
        return $this->sortable;
    }


    public function getLabel():string
    {
        return $this->label;
    }


    public function count()
    {
        return count($this->handlers);
    }
}
