<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\CustomerInvoice;

/**
 *	@tutorial | 'invoices' => ['required', 'array', new InvoicesBelongToCustomer($request->customer_id)],
 */
class InvoicesBelongToCustomer implements Rule
{	
	protected int|string $customerId;
    
	protected array $invalidInvoices = [];
	
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($customerId)
    {
        $this->customerId = $customerId;
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
        // $value should be an array of invoice IDs
        if (!is_array($value) || empty($value)) {
            return false;
        }
		
        // Get invoice IDs that do NOT belong to this customer
        $this->invalidInvoices = CustomerInvoice::whereIn('id', $value)
            ->where('customer_id', '!=', $this->customerId)
            ->pluck('id')
            ->toArray();
			
        // Validation passes only if no invalid invoices
        return count($this->invalidInvoices) === 0;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if (count($this->invalidInvoices) > 0) {
            return 'The following invoices do not belong to the selected customer: ' 
                . implode(', ', $this->invalidInvoices);
        }

        return 'Invalid invoice selection.';
    }
}
