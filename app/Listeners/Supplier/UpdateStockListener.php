<?php

namespace App\Listeners\Supplier;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\SupplierReturnEvent;
use App\Models\StockProduct;

class UpdateStockListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(SupplierReturnEvent $supplierReturnEvent)
    {	
		if( !empty($supplierReturnEvent->id) ){
			$record = StockProduct
			//where('product_id',$supplierReturnEvent->product_id)
			::where('supplier_id',$supplierReturnEvent->supplier_id)
			->where('id',$supplierReturnEvent->id)
			->where('invoice_id',$supplierReturnEvent->supplier_invoice_id)
			//->where('ref_id',$supplierReturnEvent->customer_invoice_product_id)
			->where('is_archived',0)
			->first();
			
			if(empty($record)){
				return false;
			}
			
			if($supplierReturnEvent->delete == 1){
				if(!empty($record)){
					$record->is_archived = 1;
					$record->save();
					return $record;
				}
			}else{
				if(!empty($record)){
					$record->stock = $supplierReturnEvent->quantity;
					$record->price = $supplierReturnEvent->price;
					$record->save();
					return $record;
				}
			}
			return false;
		}else{
			$stock = new StockProduct();
			$stock->supplier_id = $supplierReturnEvent->supplier_id;
			$stock->product_id = $supplierReturnEvent->product_id;
			$stock->type = $supplierReturnEvent->type;
			$stock->stock = $supplierReturnEvent->quantity;
			$stock->invoice_id = $supplierReturnEvent->supplier_invoice_id;
			$stock->ref_id = $supplierReturnEvent->supplier_invoice_product_id;
			$stock->event = $supplierReturnEvent->event;
			$stock->price = $supplierReturnEvent->price;
			$stock->remarks = $supplierReturnEvent->remarks;
			$stock->save();
			
			return ['stock' => $stock];
		}
    }
}
