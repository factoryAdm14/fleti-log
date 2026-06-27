<?php

namespace Tests\Support;

use Tests\TestCase;

abstract class FinanceTestCase extends TestCase
{
    use CreatesFinanceSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFinanceSchema();
    }
}
