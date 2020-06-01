<?php

namespace Laravel\Cashier\Exceptions;

use Exception;

class AccountAlreadyCreated extends Exception
{
    /**
     * Create a new AccountAlreadyCreated instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @return static
     */
    public static function exists($owner)
    {
        return new static(class_basename($owner)." is already a Stripe account with ID {$owner->stripe_account_id}.");
    }
}
