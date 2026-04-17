<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;

class IsCustomerInvoicePaid implements Rule
{
	protected $invoiceId;
	
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        $this->invoiceId = $value;
		$detail = CustomerInvoice::invoiceDetail($value);
		//echo '<pre>'; print_r($detail->toArray()); exit;
		if(empty($detail)){
			return true;
		}
		
		if($detail->balance_due >= 0){
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
        return __('validation.customer_invoice_is_fully_paid', [
			'id' => $this->invoiceId,
		]);
    }
}
