<?php

namespace Laravel\Cashier\Exceptions;

use Exception;

class InvalidAccount extends Exception
{
    /**
     * Create a new InvalidAccount instance.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $owner
     * @return static
     */
    public static function notYetCreated($owner)
    {
        return new static(class_basename($owner).' is not a Stripe account yet. See the createAsStripeAccount method.');
    }
}
