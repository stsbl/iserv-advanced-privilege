<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Functional;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Kernel;
use Symfony\Component\Routing\RouterInterface;

#[CoversClass(\Stsbl\IServ\AdvancedPrivilege\Kernel::class)]
final class RoutingTest extends TestCase
{
    public function testAdministrationRoutesUseTheThirdPartyModulePath(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();

        try {
            $router = $kernel->getContainer()->get('router');
            self::assertInstanceOf(RouterInterface::class, $router);
            self::assertSame('/admin/advanced-privilege', $router->getRouteCollection()->get('advanced_privilege_index')?->getPath());
            self::assertSame('/admin/advanced-privilege/apply', $router->getRouteCollection()->get('advanced_privilege_apply')?->getPath());
            self::assertSame('/api/owner-autocomplete', $router->getRouteCollection()->get('advanced_privilege_owner_autocomplete')?->getPath());
        } finally {
            $kernel->shutdown();
        }
    }
}
