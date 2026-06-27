<?php

namespace Tests\Unit;

use Tests\TestCase;

class SoftwareVersionConfigTest extends TestCase
{
    public function test_software_version_defaults_to_3_2(): void
    {
        $this->assertSame('3.2', config('app.software_version'));
    }
}
