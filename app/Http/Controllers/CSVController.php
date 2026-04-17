<?php
/**
 * https://docs.laravel-excel.com/3.1/getting-started/installation.html
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;

class CSVController extends Controller
{
    use CustomResponse;
}
