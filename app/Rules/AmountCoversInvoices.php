<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\CustomerInvoice;

/**
 *	@tutorial | 'invoices' => ['required', 'array', new AmountCoversInvoices($request->customer_id, $this->amount)],
 */
class AmountCoversInvoices implements Rule
{

	protected $customer_id;

	protected $amount;

	protected $creditAmount;

	protected $negativeInvoiceAllowed = 1;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($customer_id, $amount, $creditAmount = 0)
    {
        $this->customer_id = $customer_id;
		$this->amount = $amount;
		$this->creditAmount = (float)($creditAmount ?? 0);
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
		$payments = CustomerInvoice::unpaidInvoices($this->customer_id,$value)->toArray();
		usort($payments, function ($a, $b) {
			return $a['balance_due'] <=> $b['balance_due']; // ascending order
		});
        $invoices = $value;
		$effectiveAmount = $this->amount + $this->creditAmount;
		$amount = $effectiveAmount;
		$negative_counts = [];
		$total = [];

		foreach($payments as $p){
			$amount -= $p['balance_due'];
			$total[] = $p['balance_due'];
			if($amount < 0){
				$negative_counts[] = $p['id'];
			}
		}

		// allow to spend full amount.
		if($effectiveAmount > array_sum($total)){
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
