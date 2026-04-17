<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Supplier;

class ValidSupplier implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
	protected string $messageText = 'Invalid Supplier.';
	
    public function __construct()
    {
        
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
        return Supplier::where('id', $value)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->messageText;
    }
}
