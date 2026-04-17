<?php

namespace App\View\Components\Backend;

use Illuminate\View\Component;
use App\Models\Menu;

class Sidebar extends Component {

    public $menu;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct() {
        $this->menu = Menu::where('status','=','1')
                ->where('parent_id','=','0')
                ->with('childLevel_1')
                ->orderBy('order','ASC')
                ->get();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render() {
        return view('components.backend.sidebar');
    }

}
