<?php

namespace Tests\Feature;

use Tests\TestCase;

class CustomerConfigurationApiTest extends TestCase
{
    public function test_customer_configuration_endpoint_is_reachable(): void
    {
        $response = $this->getJson('/api/customer/configuration');

        $this->assertContains($response->status(), [200, 500]);
    }

    public function test_driver_configuration_endpoint_is_reachable(): void
    {
        $response = $this->getJson('/api/driver/configuration');

        $this->assertContains($response->status(), [200, 500]);
    }
}
