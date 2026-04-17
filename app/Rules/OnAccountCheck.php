<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\CustomerPayment;

/**
 *	@tutorial | 'invoices' => ['required', 'array', new OnAccountCheck($request->customer_id)],
 */
class OnAccountCheck implements Rule
{
	protected $customer_id;
	
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($customer_id)
    {
        $this->customer_id = $customer_id;
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
        $payment = CustomerPayment::creditedPayments($this->customer_id, $value);
		
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
