<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Controller;

use Stsbl\IServ\AdvancedPrivilege\Autocomplete\GodModeUserLookup;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/** Dedicated owner lookup; access is protected globally by AUTHENTICATED_AS_ADMIN. */
#[Route('/api/owner-autocomplete')]
final class AdminOwnerAutocompleteController extends AbstractController
{
    #[Route('', name: 'advanced_privilege_owner_autocomplete', methods: ['GET'])]
    public function __invoke(Request $request, GodModeUserLookup $users): JsonResponse
    {
        if ($request->query->has('values')) {
            $values = explode(',', (string) $request->query->get('values'));
            $uuids = [];
            foreach ($values as $value) {
                [$source, $uuid] = array_pad(explode(':', $value, 2), 2, null);
                if ('user' === $source && is_string($uuid) && '' !== $uuid) {
                    $uuids[] = $uuid;
                }
            }

            return new JsonResponse($users->lookup(array_values(array_unique($uuids))));
        }

        return new JsonResponse($users->search($request->query->getString('query')));
    }
}
