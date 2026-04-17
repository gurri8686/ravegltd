<?php

namespace App\View\Components;

use Illuminate\View\Component;

/**
 * <x-seo-tags
    :tags="[
        'title' => 'test',
        'keywords' => 'keywords'
    ]"
    ></x-seo-tags>
 */
class SeoTags extends Component {

    public $tags;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($tags) {
        $this->tags = $tags;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render() {
        return view('components.seo-tags');
    }

}
