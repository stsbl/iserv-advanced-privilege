<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use IServ\Library\IdmApiClient\IdmClient;
use IServ\Library\IdmApiClient\IdmClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Symfony\Component\HttpClient\Psr18Client;
use Stsbl\IServ\AdvancedPrivilege\Idm\RequestSatCredentials;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
    ;

    $services->load('Stsbl\\IServ\\AdvancedPrivilege\\', '../src/*')
        ->exclude(['../src/{DependencyInjection,Tests}/', '../src/Kernel.php'])
    ;

    $services->set(Psr18Client::class);
    $services->alias(ClientInterface::class, Psr18Client::class);
    $services->set(Psr17Factory::class);
    $services->alias(RequestFactoryInterface::class, Psr17Factory::class);

    $services->set(IdmClient::class)
        ->args([
            '$baseUrl' => 'http://localhost:987/',
            '$credentials' => service(RequestSatCredentials::class),
            '$defaultHeaders' => ['User-Agent' => 'STSBL/AdvancedPrivilege'],
        ])
    ;
    $services->alias(IdmClientInterface::class, IdmClient::class);
};
