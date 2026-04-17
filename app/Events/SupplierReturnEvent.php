<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupplierReturnEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
	
	public $id = 0;
	
	public $delete = 0;
	
	public $supplier_id;
	
	public $product_id;
	
	public $supplier_invoice_id;
	
	public $supplier_invoice_product_id;
	
	public $quantity;
	
	public $price;
	
	public $type;
	
	public $event;
	
	public $remarks;
	
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($params)
    {	
		if(isset($params['id']) && !empty($params['id'])){
			$this->id = $params['id'];
		}
		if(isset($params['delete']) && !empty($params['delete'])){
			$this->delete = $params['delete'];
		}
        $this->supplier_id = $params['supplier_id'] ?? 0;
		$this->product_id = $params['product_id'] ?? 0;
		$this->supplier_invoice_id = $params['supplier_invoice_id'] ?? 0;
		$this->supplier_invoice_product_id = $params['supplier_invoice_product_id'] ?? 0;
		$this->quantity = $params['quantity'] ?? 0;
		$this->price = $params['price'] ?? 0;
		$this->type = $params['type'] ?? 0;
		$this->event = $params['event'] ?? 0;
		$this->remarks = $params['remarks'] ?? "";
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
