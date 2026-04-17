<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DeleteButton extends Component
{
    public $link;
    public $class;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($link, $class = "delete")
    {
        $this->link = $link;
        $this->class = $class;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.delete-button');
    }
}
