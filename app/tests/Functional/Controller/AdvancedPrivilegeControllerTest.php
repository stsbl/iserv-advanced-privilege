<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Tests\Functional\Controller;

use IServ\Bundle\TestBrowser\Test\TestBrowser;
use IServ\Library\IdmApiClient\Exception\ClientException;
use IServ\Library\IdmApiClient\IdmClientInterface;
use IServ\Library\UserToken\Test\User\TestUserBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Stsbl\IServ\AdvancedPrivilege\Controller\AdvancedPrivilegeController;
use Stsbl\IServ\AdvancedPrivilege\Controller\AdminOwnerAutocompleteController;
use Stsbl\IServ\AdvancedPrivilege\Tests\Common\StaticIdmClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

#[CoversClass(AdvancedPrivilegeController::class)]
#[CoversClass(AdminOwnerAutocompleteController::class)]
final class AdvancedPrivilegeControllerTest extends WebTestCase
{
    public function testAdministratorCanRenderAndApplyTheAssignmentForm(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        $crawler = $client->request('GET', '/admin/advanced-privilege');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filterXPath('//form[@name="assign"]'));

        $form = $crawler->filterXPath('//form[@name="assign"]')->form();
        $client->request('POST', '/admin/advanced-privilege/apply', [
            'assign' => [
                'target' => 'all',
                'privileges' => ['privilege-id'],
                'flags' => [],
                'action' => 'assign',
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());
        self::assertSame(1, json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['updated']);
        $requests = self::getContainer()->get(IdmClientInterface::class)->requests;
        self::assertTrue(array_any($requests, static fn(array $request): bool => 'POST' === $request['method'] && str_contains($request['url'], '/privileges')));
    }

    public function testAdministratorCanGetGroupAndOwnerPreviews(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());

        $client->request('GET', '/admin/advanced-privilege/groups-preview?target=contains&pattern=Admin');
        self::assertResponseIsSuccessful();
        self::assertSame('Admins', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['groups'][0]['name']);

        $client->request('GET', '/api/owner-autocomplete?query=ada');
        self::assertResponseIsSuccessful();
        $suggestion = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)[0];
        self::assertSame('Ada Admin', $suggestion['label']);
        self::assertSame('user:9b8241a1-5f7d-4d42-a8c6-2d2d6605ef68', $suggestion['value']);

        $client->request('GET', '/api/owner-autocomplete?values=user:user-id');
        self::assertResponseIsSuccessful();
        self::assertSame('Ada Admin', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)[0]['label']);
    }



    public function testPreviewReturnsAffectedGroupsAndInvalidRegexErrors(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());

        $client->request('POST', '/admin/advanced-privilege/preview', ['assign' => [
            'target' => 'all', 'privileges' => ['privilege-id'], 'flags' => [], 'action' => 'assign',
        ]]);
        self::assertResponseIsSuccessful();
        $preview = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('Admins', $preview['groups'][0]['name']);
        self::assertSame(['Assign privilege: Manage groups'], $preview['changes']);

        $client->request('GET', '/admin/advanced-privilege/groups-preview?target=matches&pattern=%28');
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Invalid regular expression:', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);
    }


    public function testApplyReportsFormAndOwnerResults(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());

        $client->request('POST', '/admin/advanced-privilege/apply', ['assign' => [
            'target' => 'all', 'privileges' => [], 'flags' => [], 'action' => 'assign',
        ]]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Please select at least one privilege or group flag.', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);

        $client->request('POST', '/admin/advanced-privilege/apply', ['owner' => [
            'target' => 'all', 'owner' => '',
        ]]);
        self::assertResponseIsSuccessful();
        self::assertSame(['No matching groups found.'], json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['messages']);
    }


    public function testIdmFailuresAreReturnedAsClearJsonErrors(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Conflict (409)');

        $client->request('GET', '/admin/advanced-privilege/groups-preview?target=all');
        self::assertResponseStatusCodeSame(502);
        self::assertSame('IDM rejected the requested changes: Conflict (409)', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);

        self::ensureKernelShutdown();
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Forbidden (403)');
        $client->request('POST', '/admin/advanced-privilege/apply', ['assign' => [
            'target' => 'all', 'privileges' => ['privilege-id'], 'flags' => [], 'action' => 'assign',
        ]]);
        self::assertResponseStatusCodeSame(502);
        self::assertSame('IDM rejected the requested changes: Forbidden (403)', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);
    }


    public function testOwnerPreviewRejectsEmptySubmissionAndAcceptsOwnerRemoval(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());

        $client->request('POST', '/admin/advanced-privilege/preview', []);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('Invalid form submission.', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);

        $client->request('POST', '/admin/advanced-privilege/preview', ['owner' => ['target' => 'all', 'owner' => '']]);
        self::assertResponseIsSuccessful();
        $preview = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([], $preview['groups']);
        self::assertSame(['Remove owner'], $preview['changes']);
    }


    public function testIdmFailuresDuringMutationAreReturnedAsJsonErrors(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Unavailable (503)');
        $idm->exceptionOnUrl = '/groups?';

        $client->request('POST', '/admin/advanced-privilege/apply', ['assign' => [
            'target' => 'all', 'privileges' => ['privilege-id'], 'flags' => [], 'action' => 'assign',
        ]]);
        self::assertResponseStatusCodeSame(502);
        self::assertSame('IDM rejected the requested changes: Unavailable (503)', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);

        self::ensureKernelShutdown();
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Unavailable (503)');
        $idm->exceptionOnUrl = '/groups?';
        $client->request('POST', '/admin/advanced-privilege/preview', ['assign' => [
            'target' => 'all', 'privileges' => ['privilege-id'], 'flags' => [], 'action' => 'assign',
        ]]);
        self::assertResponseStatusCodeSame(502);
    }


    public function testIdmFailuresDuringOwnerMutationsAreReturnedAsJsonErrors(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Unavailable (503)');
        $idm->exceptionOnUrl = '/groups?';
        $client->request('POST', '/admin/advanced-privilege/apply', ['owner' => ['target' => 'all', 'owner' => '']]);
        self::assertResponseStatusCodeSame(502);

        self::ensureKernelShutdown();
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Unavailable (503)');
        $idm->exceptionOnUrl = '/groups?';
        $client->request('POST', '/admin/advanced-privilege/preview', ['owner' => ['target' => 'all', 'owner' => '']]);
        self::assertResponseStatusCodeSame(502);
    }


    public function testOwnerFormSubmissionErrorsAreJsonResponses(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());

        $client->request('POST', '/admin/advanced-privilege/apply', []);
        self::assertResponseStatusCodeSame(400);
        self::assertSame('Invalid form submission.', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);

        $invalid = ['owner' => ['target' => 'contains', 'pattern' => '', 'owner' => '']];
        $client->request('POST', '/admin/advanced-privilege/apply', $invalid);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Pattern should not be empty.', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['error']);

        $client->request('POST', '/admin/advanced-privilege/preview', $invalid);
        self::assertResponseStatusCodeSame(422);

        $client->request('POST', '/admin/advanced-privilege/preview', ['assign' => ['target' => 'all', 'privileges' => [], 'flags' => [], 'action' => 'assign']]);
        self::assertResponseStatusCodeSame(422);
    }

    public function testPreviewHandlesIdmFailureWhileBuildingChoices(): void
    {
        /** @var TestBrowser $client */
        $client = self::createClient();
        $client->loginAdmin(TestUserBuilder::create()->autousername()->getUser());
        /** @var StaticIdmClient $idm */
        $idm = self::getContainer()->get(IdmClientInterface::class);
        $idm->exception = new ClientException('Unavailable (503)');
        $client->request('POST', '/admin/advanced-privilege/preview', ['assign' => ['target' => 'all', 'privileges' => ['privilege-id'], 'action' => 'assign']]);
        self::assertResponseStatusCodeSame(502);
    }

}
