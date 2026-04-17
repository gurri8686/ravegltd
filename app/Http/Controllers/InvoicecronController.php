<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class InvoicecronController extends Controller
{
  public function invoicedeletecron(Request $request)
  {
    try{
    $path = public_path();
      $cdate = date('Y-m-d');
     foreach (glob("pdffile/*") as $filename) {
       $folder = explode('/',$filename);
       if($folder[1] != $cdate){
         $dpath = $path.'/'.$filename;
          foreach (glob($filename."/*") as $file) {
               if($file){
                  $filepath = $path.'/'.$file;
                    unlink($filepath);
               }
             }

              rmdir($dpath);
       }



     }
   } catch (\Exception $ex) {

       return $this->exceptionResponse($ex);
   }

     echo "data deleted success fully";


  }
}
