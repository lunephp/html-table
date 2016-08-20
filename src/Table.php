<?php


namespace Lune\Html\Table;


class Table
{
    private $columns = [];
    private $rows = [];

    private $sort_field = null;
    private $sort_order = SORT_ASC;

    private $sort_field_name = 'sort';
    private $sort_order_name = 'order';

    private $empty_text;


    public function __construct($columns = [], $rows = [], $sort_field_name = 'sort', $sort_order_name = 'order')
    {
        foreach ($columns as $id => $column) {
            $column = array_merge(['label' => '', 'handler' => null, 'sortable' => false], $column);
            $this->addColumn($id, $column['label'], $column['handler'], $column['sortable']);
        }

        foreach ($rows as $row) {
            $this->addRow($row);
        }

        $this->setSortFieldName($sort_field_name);
        $this->setSortOrderName($sort_order_name);


        $this->setSortField(filter_input(INPUT_GET, $this->getSortFieldName()));

        $order = filter_input(INPUT_GET, $this->getSortOrderName());
        $this->setSortOrder($order == 'desc' ? SORT_DESC : SORT_ASC);
    }

    public function addColumn($id, $label, $handler = null, $sortable = false)
    {
        $this->columns[$id] = new Column($id, $label, $handler, $sortable);
    }


    public function getEmptyText()
    {
        return $this->empty_text;
    }


    public function setEmptyText($empty_text)
    {
        $this->empty_text = $empty_text;
    }

    public function setSortOrderName(string $sort_order_name)
    {
        $this->sort_order_name = $sort_order_name;
    }


    public function getSortOrderName(): string
    {
        return $this->sort_order_name;
    }

    public function setSortFieldName(string $sort_field_name)
    {
        $this->sort_field_name = $sort_field_name;
    }

    public function setSortOrder(int $sort_order = SORT_ASC)
    {
        $this->sort_order = $sort_order == SORT_DESC ? SORT_DESC : SORT_ASC;
    }

    public function getSortFieldName(): string
    {
        return $this->sort_field_name;
    }

    public function setSortField($sort_field)
    {
        $this->sort_field = $sort_field;
    }

    public function getSortField()
    {
        $sf = $this->sort_field;
        if (array_key_exists($this->sort_field, $this->columns) && $this->getColumn($this->sort_field)->isSortable()) {
            return $this->sort_field;
        }

    }

    public function getSortOrder(): int
    {
        return $this->sort_order;
    }

    public function addRow($row)
    {
        $this->rows[] = $row;
    }

    public function addRows(array $rows = [])
    {
        foreach($rows as $row){
            $this->addRow($row)
        }
    }


    protected function getColumn($id):Column
    {
        if (!array_key_exists($id, $this->columns)) {
            throw new \OutOfBoundsException("Column {$id} does not exist");
        }

        return $this->columns[$id];
    }

    protected function getRow(array $row)
    {
        $cells = [];
        foreach ($this->columns as $id => $column) {
            $cells = array_merge($cells, $column->renderCells($row));
        }
        return array_map(function ($cell) {
            return '<td>' . $cell . '</td>';
        }, $cells);
    }


    protected function getHeaderLabel($id)
    {
        $label = $this->getColumn($id)->getLabel();

        if ($this->getColumn($id)->isSortable()) {
            return $this->sortable($label, $id);
        }
        return $label;

    }

    protected function getHeaderCells()
    {
        $cells = [];
        foreach (array_keys($this->columns) as $id) {
            $cells[] = $this->getHeaderCell($id);
        }
        return $cells;
    }

    public function getHeader()
    {
        $cells = $this->getHeaderCells();
        return '<thead>' . implode(PHP_EOL, $this->getHeaderCells()) . '</thead>';
    }

    protected function getRows()
    {
        return array_map(function ($row) {
            return '<tr>' . implode('', $this->getRow($row)) . '</tr>';
        }, $this->rows);
        $rows = [];
        foreach ($this->rows as $row) {
            $rows[] = '<tr>' . implode(PHP_EOL, $this->getRow($row)) . '</tr>';
        }


        return $rows;
    }

    public function getBody()
    {

        $rows = $this->getRows();
        if (empty($rows)) {

            $colcount = 0;

            array_map(function ($col) use (&$colcount) {
                $colcount += $col->count();
            }, $this->columns);

            return '<tbody class="empty"><tr><td colspan="' . $colcount . '">' . $this->getEmptyText() . '</td></tr></tbody>';
        }

        return '<tbody>' . implode(PHP_EOL, $rows) . '</tbody>';
    }

    protected function getHeaderCell($id)
    {
        $label = $this->getHeaderLabel($id);


        $cell = "<th";
        if ($this->getColumn($id)->isSortable()) {
            $cell .= ' class="' . $this->getSortableClasses($id) . '"';
        }
        $cols = $this->getColumn($id)->count();


        if ($cols > 1) {
            $cell .= ' colspan="' . $cols . '"';
        }
        $cell .= ">{$label}</th>";

        return $cell;
    }

    private function sortable($label, $id)
    {
        $parameters = array_merge($_GET, [
            $this->sort_field_name => $id,
            $this->sort_order_name => ($this->sort_order == SORT_DESC ? 'asc' : 'desc')
        ]);

        $class = $this->getSortableClasses($id);
        $link = '?' . http_build_query($parameters);
        return '<a href="' . $link . '" class="' . $class . '">' . $label . '</a>';
    }


    private function getSortableClasses($id)
    {
        $class = 'sortable';
        if ($this->sort_field == $id) {
            $class .= " sorted";
            $class .= " sorted-" . ($this->sort_order == SORT_DESC ? 'desc' : 'asc');
        }

        return $class;
    }


    public function render()
    {
        $content = $this->getHeader() . $this->getBody();

        return '<table>' . $content . '</table>';
    }
}
