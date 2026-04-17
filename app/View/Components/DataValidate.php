<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DataValidate extends Component
{
    public $field = "";
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->field = $field;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.data-validate');
    }
}
