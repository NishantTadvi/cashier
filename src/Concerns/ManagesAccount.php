<?php

namespace Laravel\Cashier\Concerns;

use Laravel\Cashier\Cashier;
use Laravel\Cashier\Exceptions\AccountAlreadyCreated;
use Laravel\Cashier\Exceptions\InvalidAccount;
use Stripe\Account as StripeAccount;

trait ManagesAccount
{
    /**
     * Retrieve the Stripe customer ID.
     *
     * @return string|null
     */
    public function stripeAccountId()
    {
        return $this->stripe_account_id;
    }

    /**
     * Determine if the entity has a Stripe customer ID.
     *
     * @return bool
     */
    public function hasStripeAccountId()
    {
        return ! is_null($this->stripe_account_id);
    }

    /**
     * Determine if the entity has a Stripe customer ID and throw an exception if not.
     *
     * @return void
     *
     * @throws \Laravel\Cashier\Exceptions\InvalidAccount
     */
    protected function assertAccountExists()
    {
        if (! $this->hasStripeAccountId()) {
            throw InvalidAccount::notYetCreated($this);
        }
    }

    /**
     * Create a Stripe customer for the given model.
     *
     * @param  array  $options
     * @return \Stripe\Account
     *
     * @throws \Laravel\Cashier\Exceptions\AccountAlreadyCreated
     */
    public function createAsStripeAccount(array $options = [])
    {
        if ($this->hasStripeAccountId()) {
            throw AccountAlreadyCreated::exists($this);
        }

        if (! array_key_exists('email', $options) && $email = $this->stripeEmail()) {
            $options['email'] = $email;
        }

        $options['type'] = 'custom';

        // Here we will create the account instance on Stripe and store the ID of the
        // user from Stripe. This ID will correspond with the Stripe user instances
        // and allow us to retrieve users from Stripe later when we need to work.
        $account = StripeAccount::create(
            $options, $this->stripeOptions()
        );

        $this->stripe_account_id = $account->id;

        $this->save();

        return $account;
    }

    /**
     * Update the underlying Stripe customer information for the model.
     *
     * @param  array  $options
     * @return \Stripe\Account
     */
    public function updateStripeCustomer(array $options = [])
    {
        return StripeAccount::update(
            $this->stripe_account_id, $options, $this->stripeOptions()
        );
    }

    /**
     * Get the Stripe customer instance for the current user or create one.
     *
     * @param  array  $options
     * @return \Stripe\Account
     */
    public function createOrGetStripeAccount(array $options = [])
    {
        if ($this->hasStripeAccountId()) {
            return $this->asStripeAccount();
        }

        return $this->createAsStripeAccount($options);
    }

    /**
     * Get the Stripe customer for the model.
     *
     * @return \Stripe\Account
     */
    public function asStripeAccount()
    {
        $this->assertAccountExists();

        return StripeAccount::retrieve($this->stripe_account_id, $this->stripeOptions());
    }

    /**
     * Get the email address used to create the customer in Stripe.
     *
     * @return string|null
     */
    public function stripeEmail()
    {
        return $this->email;
    }

    /**
     * Get the Stripe supported currency used by the entity.
     *
     * @return string
     */
    public function preferredCurrency()
    {
        return config('cashier.currency');
    }

    /**
     * Get the default Stripe API options for the current Billable model.
     *
     * @param  array  $options
     * @return array
     */
    public function stripeOptions(array $options = [])
    {
        return Cashier::stripeOptions($options);
    }
}
