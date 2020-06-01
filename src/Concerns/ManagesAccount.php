<?php

namespace Laravel\Cashier\Concerns;

use Laravel\Cashier\Cashier;
use Stripe\Account as StripeAccount;
use Illuminate\Support\Facades\Crypt;
use Laravel\Cashier\Exceptions\InvalidAccount;
use Laravel\Cashier\Exceptions\AccountAlreadyCreated;
use Stripe\StripeClient;

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
        return !is_null($this->stripe_account_id);
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
        if (!$this->hasStripeAccountId()) {
            throw InvalidAccount::notYetCreated($this);
        }
    }

    public function getExpressLink()
    {
        return config('cashier.connect') . '?' . http_build_query([
            'client_id' => config('cashier.client_id'),
            'redirect_url' => config('cashier.redirect_url'),
            'state' => Crypt::encrypt($this->id)
        ]);
    }
    
    public function createExpressAccount($code)
    {
        $post = [
            'client_secret' => config('cashier.secret'),
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];
        
        $ch = curl_init('https://connect.stripe.com/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        
        // execute!
        $response = curl_exec($ch);
        
        // close the connection, release resources used
        curl_close($ch);
        
        $data = json_decode($response, true);

        if(isset($data['error'])){
            throw new Exception("Error Processing Request");
        }

        $this->stripe_account_id = $data['stripe_user_id'];

        $this->save();

        return $data;
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

        if (!array_key_exists('email', $options) && $email = $this->stripeEmail()) {
            $options['email'] = $email;
        }

        $options['type'] = 'custom';
        $options['requested_capabilities'] = [
            'card_payments',
            'transfers',
        ];

        // Here we will create the account instance on Stripe and store the ID of the
        // user from Stripe. This ID will correspond with the Stripe user instances
        // and allow us to retrieve users from Stripe later when we need to work.
        $account = StripeAccount::create(
            $options,
            $this->stripeOptions()
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
    public function updateStripeAccount(array $options = [])
    {
        return StripeAccount::update(
            $this->stripe_account_id,
            $options,
            $this->stripeOptions()
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
}
