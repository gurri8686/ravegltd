<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\CustomerInvoice;
use App\Models\CustomerPayment;

class CanDeleteCustomerInvoiceProduct implements Rule
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
        $invoice = CustomerPayment::where('customer_invoice_id',$value)
			//->where('initiated',0)
			->first();
		
		if(empty($invoice)){
			return true;
		}
		
		return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('validation.customer_invoice_product_can_not_be_deleted', [
			'id' => $this->invoiceId,
		]);
    }
}
