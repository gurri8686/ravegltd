<?php

namespace App\View\Components\FileUploader\Krajee;

use Illuminate\View\Component;

/**
 * usage
 * <x-file-uploader.krajee.includes />
 * @source : https://plugins.krajee.com/file-basic-usage-demo
 * @source: https://plugins.krajee.com/file-input
 * @example: $("#input-b5").fileinput({showCaption: false, dropZoneEnabled: false, showUpload:false,maxFileCount: 1,allowedFileExtensions: ["jpg", "png", "gif"]});
 */
class Includes extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.file_uploader.krajee.includes');
    }
}
