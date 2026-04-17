<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\CustomerReturnEvent;
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
    public function handle(CustomerReturnEvent $customerReturnEvent)
    {	
		if( !empty($customerReturnEvent->id) ){
			$record = StockProduct
			//where('product_id',$customerReturnEvent->product_id)
			::where('customer_id',$customerReturnEvent->customer_id)
			->where('id',$customerReturnEvent->id)
			->where('invoice_id',$customerReturnEvent->customer_invoice_id)
			//->where('ref_id',$customerReturnEvent->customer_invoice_product_id)
			->where('is_archived',0)
			->first();
			
			if(empty($record)){
				return false;
			}
			
			if($customerReturnEvent->delete == 1){
				if(!empty($record)){
					$record->is_archived = 1;
					$record->save();
					return $record;
				}
			}else{
				if(!empty($record)){
					$record->stock = $customerReturnEvent->quantity;
					$record->price = $customerReturnEvent->price;
					$record->save();
					return $record;
				}
			}
			return false;
		}else{
			$stock = new StockProduct();
			$stock->customer_id = $customerReturnEvent->customer_id;
			$stock->supplier_invoice_product_id = $customerReturnEvent->supplier_invoice_product_id;
			$stock->supplier_invoice_id = $customerReturnEvent->supplier_invoice_id;
			$stock->product_id = $customerReturnEvent->product_id;
			$stock->type = $customerReturnEvent->type;
			$stock->stock = $customerReturnEvent->quantity;
			$stock->invoice_id = $customerReturnEvent->customer_invoice_id;
			$stock->ref_id = $customerReturnEvent->customer_invoice_product_id;
			$stock->event = $customerReturnEvent->event;
			$stock->price = $customerReturnEvent->price;
			$stock->remarks = $customerReturnEvent->remarks;
			$stock->save();
			
			return ['stock' => $stock];
		}
    }
}
