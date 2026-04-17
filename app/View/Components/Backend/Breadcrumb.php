<?php

namespace App\View\Components\Backend;

use Illuminate\View\Component;

/**
 * <x-backend.breadcrumb
    :auth=\Auth::user() 
    :links="[
        ['a' => 'Home', 'href' => route('home')],
        ['a' => 'User', 'href' => '']
    ]"
    ></x-backend.breadcrumb>
 */
class Breadcrumb extends Component {
    
    public $auth;
    public $links;
    public $title;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($auth, $links = [], $title = "") {
        $this->auth = $auth;
        $this->links = $links;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render() {
        return view('components.backend.breadcrumb');
    }

}
