<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege;

use IServ\Library\AppKernel\Kernel as BaseKernel;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class Kernel extends BaseKernel implements CompilerPassInterface
{
    protected function getModule(): string
    {
        return 'stsbl/advanced-privilege';
    }

    public function process(ContainerBuilder $container): void
    {
        if ($this->environment !== 'test') {
            return;
        }

        // We have to work around some nasty PoWeb session stuff, which breaks our tests.
        // TODO: Remove after #68370 got implemented and AuthBundle updated.
        $container
            ->getDefinition('session.storage.factory.mock_file')
            ->setArgument('$name', 'IServPortalWebSession')
        ;
    }
}
