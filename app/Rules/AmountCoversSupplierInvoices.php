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
		$payments = SupplierInvoice::unpaidInvoices($this->supplier_id, $value)->toArray();
		$total = array_sum(array_column($payments, 'balance_due'));

		// Block only if amount exceeds the selected invoices total. Partial
		// payments (amount <= total) are allowed.
		if ($this->amount > $total) {
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
