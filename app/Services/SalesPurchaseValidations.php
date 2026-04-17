<?php

namespace App\Services;
use Illuminate\Http\Request;

use App\Models\SupplierInvoiceProduct;
use App\Models\CustomerInvoiceProduct;
use App\Lib\Response;
use App\Models\StockProduct;

class SalesPurchaseValidations
{
	use Response;
	
	protected $supplier;
	
	protected $customer;
	
	protected function supplierCustomer($invoice_id, $supplier_id, $product_id){
		$this->supplier = SupplierInvoiceProduct::where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("product_id", $product_id)
			->first();
			
		if(empty($this->supplier)){
			throw new \Exception(__("exception.invalidSupplierInvoice"));
		}
		
		$this->customer = CustomerInvoiceProduct::where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("product_id", $product_id)
			->get();
	}
	
    public function canDeletePurchaseEntryFromInvoice($invoice_product_id, $invoice_id, $supplier_id, $product_id , $mode = "" )
    {
		//echo $invoice_product_id.'-'.$invoice_id.'-'.$supplier_id.'-'.$product_id; exit;
		//$this->supplierCustomer($invoice_id, $supplier_id, $product_id);
		$customer = CustomerInvoiceProduct::
			where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("supplier_invoice_product_id", $invoice_product_id)
			->where("product_id", $product_id)
			->get();
			
		if(sizeof($customer) <= 0 || empty($customer)){
			return true;
		}
		
		if($mode == "edit"){
			throw new \Exception(__("exception.canEditPurchaseEntryFromInvoice"));
		}else{
			throw new \Exception(__("exception.canDeletePurchaseEntryFromInvoice"));
		}
    }
	
	public function canDeletePurchaseEntryFromProductInvoiceId($invoice_id )
    {
		$supplier = SupplierInvoiceProduct::where("id",$invoice_id)->first();
			
		if(empty($supplier)){
			throw new \Exception(__("exception.invalidSupplierInvoice"));
		}
		
		print_r($supplier->toArray()); exit;
		
		$this->customer = CustomerInvoiceProduct::where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("product_id", $product_id)
			->get();
	
		$this->supplierCustomer($invoice_id, $supplier_id, $product_id);
		if(sizeof($this->customer) <= 0 || empty($this->customer)){
			return true;
		}
		throw new \Exception(__("exception.canDeletePurchaseEntryFromInvoice"));
    }
	
	/**
	 * @description | For edit case only.
	 */
	public function canEditPurchaseQtyFromInvoice($invoice_product_id, $invoice_id, $supplier_id, $product_id, $qty)
    {
		//echo $invoice_product_id.'-'.$invoice_id.'-'.$supplier_id.'-'.$product_id.'-'; exit;
		$stock = StockProduct::stock(['product_id' => $product_id, 'invoice_id' => [$invoice_id], 'ref_id' => $invoice_product_id]);
		if(empty($stock)){
			return true;
		}		
		
		if(isset($stock['customer_stocks'])){
			if($qty < $stock['customer_stocks']['net_stock']){
				throw new \Exception(__("exception.canEditPurchaseQtyFromInvoice",["customer" => $stock['customer_stocks']['net_stock']]));
			}
		}
		
		return true;
		
		exit;
		
		$supplier = SupplierInvoiceProduct::
			where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("id", $invoice_product_id)
			->where("product_id", $product_id)
			->first();
			
		if(empty($supplier)){
			throw new \Exception(__("exception.invalidSupplierInvoice"));
		}
		
		$customers = CustomerInvoiceProduct::
			where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("supplier_invoice_product_id", $invoice_product_id)
			->where("product_id", $product_id)
			->get();
			
		$quantity = [];
		if(sizeof($customers) > 0){
			foreach($customers as $customer){
				$quantity[] = $customer->quantity;
			}
		}
		print_r($quantity); exit;
		$supplier_total = $supplier->quantity;
		$customer_total = array_sum($quantity);
		$diff = $supplier_total - $customer_total;
		
		//echo $supplier_total.'-'.$customer_total.'-'.$diff; exit;
		
		if($qty < $customer_total){
			throw new \Exception(__("exception.canEditPurchaseQtyFromInvoice",["customer" => $customer_total]));
		}
		
		return true;
		
    }
	
	/*public function canAddSaleEntryFromInvoice($invoice_id, $supplier_id, $product_id, $qty)
    {
		$this->supplierCustomer($invoice_id, $supplier_id, $product_id);
		$quantity = [];
		if(sizeof($this->customer) > 0){
			foreach($this->customer as $customer){
				$quantity[] = $customer->quantity;
			}
		}
		$supplier_total = $this->supplier->quantity;
		$customer_total = array_sum($quantity);
		$diff = $supplier_total - $customer_total;
		
		if($qty > $diff){
			throw new \Exception(__("exception.canAddSaleEntryFromInvoice",["qty" => $diff]));
		}
		
		return true;
    }*/
	public function canAddSaleEntryFromInvoice($supplier_invoice_product_id, $qty)
    {
		$supplier = SupplierInvoiceProduct::where('id',$supplier_invoice_product_id)->first();
		$customers = CustomerInvoiceProduct::where('supplier_invoice_product_id', $supplier_invoice_product_id)
			->where('is_archive',0)->get();
	
		$quantity = [];
		if(sizeof($customers) > 0){
			foreach($customers as $customer){
				$quantity[] = $customer->quantity;
			}
		}
		$supplier_total = $supplier->quantity;
		$customer_total = array_sum($quantity);
		$diff = $supplier_total - $customer_total;
		
		if($qty > $diff){
			throw new \Exception(__("exception.canAddSaleEntryFromInvoice",["qty" => $diff]));
		}
		
		return true;
    }
	
	public function canEditSaleEntryQtyFromInvoice($supplier_invoice_product_id, $invoice_product_id, $qty){
		$supplier = SupplierInvoiceProduct::where('id',$supplier_invoice_product_id)->first();
		$customers = CustomerInvoiceProduct
			::where('supplier_invoice_product_id', $supplier_invoice_product_id)
			->where('id','!=',$invoice_product_id)
			->where('is_archive',0)->get();
	
		$quantity = [];
		if(sizeof($customers) > 0){
			foreach($customers as $customer){
				$quantity[] = $customer->quantity;
			}
		}
		$supplier_total = $supplier->quantity;
		$customer_total = array_sum($quantity);
		$diff = $supplier_total - $customer_total;
		
		if($qty > $diff){
			throw new \Exception(__("exception.canAddSaleEntryFromInvoice",["qty" => $diff]));
		}
		
		return true;
	}
	
	public function canDeleteSaleEntryFromInvoice()
    {
		
    }
	
	/*public function canEditSaleEntryQtyFromInvoice($invoice_id, $customer_invoice_id, $supplier_id, $product_id, $qty)
    {
		$this->supplierCustomer($invoice_id, $supplier_id, $product_id);
		$this->customer = CustomerInvoiceProduct::where("supplier_invoice_id", $invoice_id)
			->where("supplier_id", $supplier_id)
			->where("product_id", $product_id)
			->where("customer_invoice_id",'!=',$customer_invoice_id)
			->get();
		
		if(empty($this->customer)){
			throw new \Exception(__("exception.invalidCustomerInvoice"));
		}
		//echo '<pre>';
		//print_r($this->customer); exit;	
		$quantity = [];
		if(sizeof($this->customer) > 0){
			foreach($this->customer as $customer){
				$quantity[] = $customer->quantity;
			}
		}
		
		$supplier_total = $this->supplier->quantity;
		$customer_total = array_sum($quantity);
		$diff = $supplier_total - $customer_total;
				
		if($qty > $diff){
			throw new \Exception(__("exception.canAddSaleEntryFromInvoice",["qty" => $diff]));
		}
		
		return true;
    }*/
	
	public function canDumpPurchaseEntryQtyFromInvoice($invoice_id, $supplier_id, $product_id, $qty)
    {
		$this->supplierCustomer($invoice_id, $supplier_id, $product_id);
		$quantity = [];
		if(sizeof($this->customer) > 0){
			foreach($this->customer as $customer){
				$quantity[] = $customer->quantity;
			}
		}
		$supplier_total = $this->supplier->quantity;
		$customer_total = array_sum($quantity);
		$diff = $supplier_total - $customer_total;
		
		if($qty < $customer_total){
			throw new \Exception(__("exception.canEditPurchaseQtyFromInvoice",["customer" => $customer_total]));
		}
		
		return true;
    }
	
	public function checkPaymentProcessedForInvoice($customer_invoice_id){
		
	}
}
