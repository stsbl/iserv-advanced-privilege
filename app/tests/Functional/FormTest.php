<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Functional;

use IServ\Bundle\Autocomplete\Endpoint\Domain\Endpoints;
use IServ\Bundle\Autocomplete\Endpoint\EndpointsProviderInterface;
use IServ\Bundle\Autocomplete\Endpoint\StaticEndpointsProvider;
use IServ\Library\IdmApiClient\Hydrator\HydratorInterface;
use IServ\Library\IdmApiClient\IdmClientInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\AdvancedPrivilege\Form\GroupMutationType;
use Stsbl\IServ\AdvancedPrivilege\Form\OwnerMutationType;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Stsbl\IServ\AdvancedPrivilege\Service\IdmReferenceProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

#[CoversClass(GroupMutationType::class)]
#[CoversClass(OwnerMutationType::class)]
final class FormTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $client = $this->createMock(IdmClientInterface::class);
        $client->method('performRequest')->willReturnCallback(static function (string $method, string $url, HydratorInterface $hydrator): array {
            self::assertSame('GET', $method);

            return $hydrator->hydrate(str_contains($url, '/privileges') ? [[
                'hexUuid' => 'privilege-id', 'name' => 'Manage groups',
            ]] : [[
                'hexUuid' => 'flag-id', 'name' => 'has_home', 'title' => 'Has home directory',
            ]]);
        });
        self::getContainer()->set(IdmReferenceProvider::class, new IdmReferenceProvider($client));
        self::getContainer()->set(EndpointsProviderInterface::class, new StaticEndpointsProvider(new Endpoints('/autocomplete', '/lookup', '/learn')));
    }

    public function testGroupMutationFormMapsSelectedReferences(): void
    {
        $form = self::getContainer()->get(FormFactoryInterface::class)->createNamed('assign', GroupMutationType::class, null, [
            'mutation_action' => GroupMutation::ASSIGN,
        ]);
        $form->submit([
            'target' => 'contains',
            'pattern' => 'Admin',
            'privileges' => ['privilege-id'],
            'flags' => ['flag-id'],
            'action' => GroupMutation::ASSIGN,
        ]);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true, false));
        self::assertInstanceOf(GroupMutation::class, $mutation = $form->getData());
        self::assertSame(GroupMutation::ASSIGN, $mutation->action);
        self::assertSame(['privilege-id'], $mutation->privileges);
        self::assertSame(['flag-id'], $mutation->flags);
    }

    public function testGroupMutationRequiresAReference(): void
    {
        $form = self::getContainer()->get(FormFactoryInterface::class)->createNamed('assign', GroupMutationType::class, null, [
            'mutation_action' => GroupMutation::ASSIGN,
        ]);
        $form->submit(['target' => 'all', 'action' => GroupMutation::ASSIGN]);

        self::assertFalse($form->isValid());
        self::assertStringContainsString('Please select at least one privilege or group flag.', (string) $form->getErrors(true, false));
    }

    public function testOwnerMutationFormAllowsRemovingTheOwner(): void
    {
        $form = self::getContainer()->get(FormFactoryInterface::class)->createNamed('owner', OwnerMutationType::class, null);
        $form->submit(['target' => 'all', 'owner' => '']);

        self::assertTrue($form->isValid(), (string) $form->getErrors(true, false));
        self::assertInstanceOf(OwnerMutation::class, $mutation = $form->getData());
        self::assertNull($mutation->ownerUuid());
    }
}
