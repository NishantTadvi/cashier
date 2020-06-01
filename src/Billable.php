<?php

namespace Laravel\Cashier;

use Laravel\Cashier\Concerns\ManagesAccount;
use Laravel\Cashier\Concerns\ManagesCustomer;
use Laravel\Cashier\Concerns\ManagesInvoices;
use Laravel\Cashier\Concerns\PerformsCharges;
use Laravel\Cashier\Concerns\ManagesSubscriptions;
use Laravel\Cashier\Concerns\ManagesPaymentMethods;

trait Billable
{
    use ManagesCustomer;
    use ManagesAccount;
    use ManagesInvoices;
    use ManagesPaymentMethods;
    use ManagesSubscriptions;
    use PerformsCharges;
}
