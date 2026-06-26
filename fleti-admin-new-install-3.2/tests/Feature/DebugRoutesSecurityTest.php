<?php

namespace Tests\Feature;

use Tests\TestCase;

class DebugRoutesSecurityTest extends TestCase
{
    public function test_debug_sender_route_is_not_registered_in_production_mode(): void
    {
        $this->get('/sender')->assertNotFound();
    }

    public function test_debug_demo_route_is_not_registered_in_production_mode(): void
    {
        $this->get('/update-data-test')->assertNotFound();
    }

    public function test_debug_sms_route_is_not_registered_in_production_mode(): void
    {
        $this->get('/sms-test')->assertNotFound();
    }
}
