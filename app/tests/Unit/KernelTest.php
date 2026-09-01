<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Stsbl\IServ\AdvancedPrivilege\Kernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[CoversClass(Kernel::class)]
final class KernelTest extends TestCase
{
    public function testTestKernelUsesDistinctPortalWebSessionName(): void
    {
        $kernel = new Kernel('test', true);
        $container = new ContainerBuilder();
        $container->setDefinition('session.storage.factory.mock_file', new Definition());
        $kernel->process($container);

        self::assertSame('IServPortalWebSession', $container->getDefinition('session.storage.factory.mock_file')->getArgument('$name'));
        $method = new \ReflectionMethod($kernel, 'getModule');
        self::assertSame('stsbl/advanced-privilege', $method->invoke($kernel));
    }

    public function testNonTestKernelLeavesSessionDefinitionAlone(): void
    {
        $kernel = new Kernel('prod', false);
        $container = new ContainerBuilder();
        $container->setDefinition('session.storage.factory.mock_file', new Definition());
        $kernel->process($container);

        self::assertArrayNotHasKey('$name', $container->getDefinition('session.storage.factory.mock_file')->getArguments());
    }
}
