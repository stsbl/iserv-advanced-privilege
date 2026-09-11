<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use IServ\Bundle\Autocomplete\Endpoint\Domain\Endpoints;
use IServ\Bundle\Autocomplete\Endpoint\EndpointsProviderInterface;
use IServ\Bundle\Autocomplete\Endpoint\StaticEndpointsProvider;
use IServ\Library\Config\Config;
use IServ\Library\IdmApiClient\IdmClientInterface;
use Stsbl\IServ\AdvancedPrivilege\Config\ModuleConfigInterface;
use Stsbl\IServ\AdvancedPrivilege\Tests\Common\StaticIdmClient;
use Stsbl\IServ\AdvancedPrivilege\Tests\Common\StaticModuleConfig;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->set(Config::class)->arg('$config', ['PortalBasePath' => '/iserv']);
    $services->set(ModuleConfigInterface::class, StaticModuleConfig::class);
    $services->set(IdmClientInterface::class, StaticIdmClient::class)->public();
    $services->set(Endpoints::class)->args(['$autocomplete' => '/autocomplete', '$lookup' => '/lookup', '$learn' => '/learn']);
    $services->set(EndpointsProviderInterface::class)->class(StaticEndpointsProvider::class)->args([service(Endpoints::class)]);
};
