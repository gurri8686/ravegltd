<?php

namespace App\View\Components\Backend;

use Illuminate\View\Component;

/**
 * <x-backend.settings 
    :auth=\Auth::user() 
    :links="[
        ['a' => 'Bootstrap Cards', 'href' => route('home')]
    ]"
    ></x-backend.settings>
 */
class Settings extends Component {

    public $auth;
    
    public $links;
    
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($auth, $links = []) {
        $this->auth = $auth;
        $this->links = $links;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render() {
        return view('components.backend.settings');
    }

}
