<?php

return [
    'name' => 'FinanceManagement',
    'default_commission_percent' => 15,
    'min_withdraw_amount' => 50,
    'balance_release_days' => 0,
    'modes' => ['commission', 'subscription', 'hybrid'],
    'plan_expiry_rules' => ['revert_commission', 'block_rides', 'grace_period'],
];
