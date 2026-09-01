<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Common;

use Stsbl\IServ\AdvancedPrivilege\Config\ModuleConfigInterface;

final readonly class StaticModuleConfig implements ModuleConfigInterface
{
    public function __construct(private string $portalBasePath = '/iserv')
    {
    }

    public function portalBasePath(): string
    {
        return $this->portalBasePath;
    }
}
