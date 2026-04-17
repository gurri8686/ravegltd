<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\SupplierInvoice;

class AmountCoversSupplierInvoices implements Rule
{
	protected $supplier_id;
	
	protected $amount;
	
	protected $negativeInvoiceAllowed = 1;
	
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($supplier_id, $amount)
    {
        $this->supplier_id = $supplier_id;
		$this->amount = $amount;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $payments = SupplierInvoice::unpaidInvoices($this->supplier_id,$value)->toArray();
		//print_r($value); print_r($this->supplier_id); print_r($payments); exit;
		usort($payments, function ($a, $b) {
			return $a['balance_due'] <=> $b['balance_due']; // ascending order
		});
        $invoices = $value;
		$amount = $this->amount;
		$negative_counts = [];
		$total = [];
		/*print_r($payments);print_r($invoices);print_r($this->amount);*/
		foreach($payments as $p){
			$amount -= $p['balance_due'];
			$total[] = $p['balance_due'];
			if($amount < 0){
				$negative_counts[] = $p['id'];
			}
		}
		//print_r(array_sum($total));print_r($payments);print_r($invoices);print_r($this->amount); exit;
		//var_dump($this->amount > array_sum($total)); exit;
		if($this->amount > array_sum($total)){
			return false;
		}
		
		if(sizeof($negative_counts) > $this->negativeInvoiceAllowed){
			return false;
		}
		return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('messages.amount_is_not_covering_invoices');
    }
}
