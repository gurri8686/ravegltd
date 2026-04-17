<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * <x-ajax-form-data
    formId='store'
    :route='route("backend.modules.store")'
    />
 */
class AjaxFormData extends Component {

    public $formId = '';
    public $route = '';
    public $method = '';

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($formId, $route, $method = "") {
        $this->formId = $formId;
        $this->route = $route;
        $this->method = $method;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render() {
        return view('components.ajax-form-data');
    }

}
