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

        // Use a distinct mock-session name for Portal-Web module tests.
        $container
            ->getDefinition('session.storage.factory.mock_file')
            ->setArgument('$name', 'IServPortalWebSession')
        ;
    }
}
