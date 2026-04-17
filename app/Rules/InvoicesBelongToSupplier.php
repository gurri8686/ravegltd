<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\SupplierInvoice;

class InvoicesBelongToSupplier implements Rule
{
	
	protected int|string $supplierId;
    
	protected array $invalidInvoices = [];
	
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($supplierId)
    {
        $this->supplierId = $supplierId;
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
		
		//print_r($value); print_r($this->supplierId); exit;
		
        // Get invoice IDs that do NOT belong to this customer
        $this->invalidInvoices = SupplierInvoice::whereIn('id', $value)
            ->where('supplier_id', '!=', $this->supplierId)
            ->pluck('id')
            ->toArray();
		
		//print_r($this->invalidInvoices); exit;
			
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
            return 'The following invoices do not belong to the selected supplier: ' 
                . implode(', ', $this->invalidInvoices);
        }

        return 'Invalid invoice selection.';
    }
}
