<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DataTableRemoveRow extends Component {

    public $datatable;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($datatable) {
            $this->datatable = $datatable;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render() {
        return view('components.data-table-remove-row');
    }

}
