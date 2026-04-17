<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Sample Path setting
 * 'uploads' . DIRECTORY_SEPARATOR . 'user_files' . DIRECTORY_SEPARATOR,
 */
/**
 * File Sizes : Just divide it by 1024 for kb, 1024^2 for mb and 1024^3 for GB. As simple as that.
 */

namespace App\Lib;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\File;
use App\Models\PostMedia;

trait FilesUpload {

    /**
     *
     * @param type $name
     * @param type $path
     * @param type $request
     * @param type $allowedFormats
     *
     * example : $this->singleFile(
      'name',
      'uploads' . DIRECTORY_SEPARATOR . 'files',
      Request $request,
      ['txt','jpg']
      );
     */
    public function singleFile($name, $path, $request, $allowedFormats = [], $allowedSizeMb = "") {
        $file = $request->file($name)->getClientOriginalName();
        //$fileName = urlencode(uniqid() . '_' . $file);
        $fileName = uniqid().'-'.str_replace(" ","-",$file);
        $fileName = str_replace("(","",$fileName);
        $fileName = str_replace(")","",$fileName);

        // Where $file is an instance of Illuminate\Http\UploadFile
        // $extension = $file->getClientOriginalExtension();
        $ext = File::extension($file);

        $size = $request->file($name)->getSize();

        $current_size = $size / 1048576;

        if ($allowedSizeMb != "") {
            if ($current_size > $allowedSizeMb) {
                throw new \Exception('File Size not Supported for field:' . $name);
                return;
            }
        }

        if (sizeof($allowedFormats) > 0) {
            if (!in_array(strtolower($ext), $allowedFormats)) {
                throw new \Exception(trans('general.valid_extensions',['extensions' => implode(',', $allowedFormats),'field' => $name]));
                return;
            }
        }

        $path = $path;
        $destinationPath = public_path($path); // upload path

        File::makeDirectory($destinationPath, 0777, true, true);
        $request->file($name)->move($destinationPath, urlencode($fileName));

        $file = [
            'path' => $path,
            'size' => $current_size,
            'extension' => $ext,
            'name' => $fileName
        ];

        return $file;
    }

    /**
     *
     * @param type $name
     * @param type $path
     * @param type $request
     * @param type $allowedFormats
     * @param type $allowedSizeMb
     * @return type
     * @throws \Exception
     *
     * $upload = $this->multipleFiles(
            'image',
            'uploads' . DIRECTORY_SEPARATOR . 'user_files' . DIRECTORY_SEPARATOR . 'cnic' . DIRECTORY_SEPARATOR,
            $request,
            []
        );
     */
    public function multipleFiles($name, $path, $request, $allowedFormats = [], $allowedSizeMb = "") {
        $files = $request->file($name);
        $data = [] ;

        foreach ($files as $k => $f) {
            $file = $f->getClientOriginalName();
            //$fileName = urlencode(uniqid() . '_' . $file);
            $fileName = uniqid().'-'.str_replace(" ","-",$file);
            

            // Where $file is an instance of Illuminate\Http\UploadFile
            // $extension = $file->getClientOriginalExtension();
            $ext = File::extension($file);
            //$size = $request->file($name)->getSize();
            $size = $f->getSize();

            $current_size = $size / 1048576;

            if ($allowedSizeMb != "") {
                if ($current_size > $allowedSizeMb) {
                    throw new \Exception('File Size not Supported for field:' . $name);
                    return;
                }
            }

            if (sizeof($allowedFormats) > 0) {
                if (!in_array(strtolower($ext), $allowedFormats)) {
                    throw new \Exception('Extension .' . $ext . ' not Supported for field:' . $name);
                    return;
                }
            }

            $path = $path;
            $destinationPath = public_path($path); // upload path
            
            
            File::makeDirectory($destinationPath, 0777, true, true);
            $f->move($destinationPath, urlencode($fileName));

            $data[] = [
                'path' => $path,
                'size' => $current_size,
                'extension' => $ext,
                'name' => $fileName
            ];
        }

        return $data;
    }

}
