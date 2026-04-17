<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AjaxTableData extends Component
{
    public $tableId = '';
    public $route = '';
    public $method = '';


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($tableId, $route, $method = "") {
        $this->tableId = $tableId;
        $this->route = $route;
        $this->method = $method;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ajax-table-data');
    }
}
