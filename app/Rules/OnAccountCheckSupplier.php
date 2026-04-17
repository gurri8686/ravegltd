<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\SupplierPayment;

class OnAccountCheckSupplier implements Rule
{
	protected $supplier_id;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($supplier_id)
    {
        $this->supplier_id = $supplier_id;
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
        $payment = SupplierPayment::creditedPayments($this->supplier_id, $value);
		
		//print_r($payment->toArray()); exit;
		
		if(empty($payment)){
			return false;
		}
		
		if($payment->remaining_amount <= 0){
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
        return 'The validation error message.';
    }
}
