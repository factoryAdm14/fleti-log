<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminAuthPagesTest extends TestCase
{
    public function test_admin_login_page_renders(): void
    {
        $response = $this->get('/admin/auth/login');

        $response->assertOk();
        $response->assertSee('Software Version', false);
        $response->assertSee(config('app.software_version'), false);
    }
}
