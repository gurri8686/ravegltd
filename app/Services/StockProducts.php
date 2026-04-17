<?php

namespace App\Services;
use Illuminate\Http\Request;
use App\Lib\Response as CustomResponse;
use Validator;
use Response;
use Session;
use DB;
use App\Models\StockProduct;

class StockProducts{
	
	public function recordStock($data){
		return StockProduct::createOrUpdateStock($data);
	}
	
	public function removeStock($data){
		return StockProduct::removeStock($data);
	}
	
	public function recordCommon($data){
		return StockProduct::record($data);
	}
}