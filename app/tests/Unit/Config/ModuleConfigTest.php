<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit\Config;

use IServ\Library\Config\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Config\ModuleConfig;

#[CoversClass(ModuleConfig::class)]
final class ModuleConfigTest extends TestCase
{
    public function testItUsesConfiguredPortalBasePath(): void
    {
        self::assertSame('/portal', (new ModuleConfig(new Config(['PortalBasePath' => '/portal'])))->portalBasePath());
    }

    public function testItUsesIservAsFallback(): void
    {
        self::assertSame('/iserv', (new ModuleConfig(new Config([])))->portalBasePath());
    }
}
