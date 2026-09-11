<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Config;

use IServ\Library\Config\Config;

final readonly class ModuleConfig implements ModuleConfigInterface
{
    public function __construct(private Config $config)
    {
    }

    public function portalBasePath(): string
    {
        return $this->config->getOptionalString('PortalBasePath') ?? '/iserv';
    }
}
