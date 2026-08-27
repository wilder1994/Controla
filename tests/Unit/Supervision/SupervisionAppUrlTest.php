<?php

declare(strict_types=1);

namespace Tests\Unit\Supervision;

use App\Support\Supervision\SupervisionAppUrl;
use Tests\TestCase;

final class SupervisionAppUrlTest extends TestCase
{
    public function test_uses_configured_pwa_url(): void
    {
        config(['supervision.pwa_url' => 'http://controla_supervision.test/']);

        $this->assertSame('http://controla_supervision.test', SupervisionAppUrl::pwa());
    }

    public function test_derives_pwa_host_from_controla_app_url(): void
    {
        config(['supervision.pwa_url' => '', 'app.url' => 'http://controla.test']);

        $this->assertSame('http://controla_supervision.test', SupervisionAppUrl::pwa());
    }
}
