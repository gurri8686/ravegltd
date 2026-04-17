<?php
/**
 * https://github.com/netflie/whatsapp-cloud-api	
 */
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;

class WhatsappController extends Controller
{
    use CustomResponse;
}
