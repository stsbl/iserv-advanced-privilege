<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Controller;

use IServ\Library\ModuleResponse\ResponseContent;
use IServ\Library\ModuleResponse\ResponseContentBuilder;
use Stsbl\IServ\AdvancedPrivilege\Form\GroupMutationType;
use Stsbl\IServ\AdvancedPrivilege\Form\OwnerMutationType;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Stsbl\IServ\AdvancedPrivilege\Service\BulkMutationHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('')]
final class AdvancedPrivilegeController extends AbstractController
{
    public function __construct(private readonly FormFactoryInterface $forms)
    {
    }

    #[Route('', name: 'advanced_privilege_index', methods: ['GET'])]
    public function index(): ResponseContent
    {
        $content = $this->renderView('advanced_privilege/index.html.twig', [
            'assign_form' => $this->groupForm(GroupMutation::ASSIGN)->createView(),
            'revoke_form' => $this->groupForm(GroupMutation::REVOKE)->createView(),
            'owner_form' => $this->ownerForm()->createView(),
        ]);

        return ResponseContentBuilder::createFromContent($content, ResponseContent::TYPE_MODULE)
            ->setTitle(_('Advanced privilege assignment'))
            ->addBreadcrumb(_('Privileges'))
            ->addBreadcrumb(_('Advanced privilege assignment'))
            ->getResponseContent()
        ;
    }

    #[Route('/apply', name: 'advanced_privilege_apply', methods: ['POST'])]
    public function apply(Request $request, BulkMutationHandler $handler): JsonResponse
    {
        foreach ([GroupMutation::ASSIGN, GroupMutation::REVOKE] as $action) {
            $form = $this->groupForm($action);
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                if (!$form->isValid()) {
                    return $this->formErrors($form);
                }
                /** @var GroupMutation $mutation */
                $mutation = $form->getData();

                return new JsonResponse(['updated' => $handler->updateGroups($mutation)]);
            }
        }
        $form = $this->ownerForm();
        $form->handleRequest($request);
        if (!$form->isSubmitted()) {
            return new JsonResponse(['error' => _('Invalid form submission.')], 400);
        }
        if (!$form->isValid()) {
            return $this->formErrors($form);
        }
        /** @var OwnerMutation $mutation */
        $mutation = $form->getData();

        return new JsonResponse(['updated' => $handler->updateOwner($mutation)]);
    }

    /** @psalm-suppress MixedReturnTypeCoercion */
    /** @return \Symfony\Component\Form\FormInterface<OwnerMutation|null> */
    private function ownerForm(): \Symfony\Component\Form\FormInterface
    {
        return $this->forms->createNamed('owner', OwnerMutationType::class, null, [
            'action' => $this->generateUrl('advanced_privilege_apply'),
            'method' => 'POST',
        ]);
    }

    /** @psalm-suppress MixedReturnTypeCoercion */
    /** @return \Symfony\Component\Form\FormInterface<GroupMutation|null> */
    private function groupForm(string $action): \Symfony\Component\Form\FormInterface
    {
        return $this->forms->createNamed($action, GroupMutationType::class, null, [
            'action' => $this->generateUrl('advanced_privilege_apply'),
            'method' => 'POST',
            'mutation_action' => $action,
        ]);
    }

    /**
     * @template TData
     * @param \Symfony\Component\Form\FormInterface<TData> $form
     */
    private function formErrors(\Symfony\Component\Form\FormInterface $form): JsonResponse
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $errors[] = $error->getMessage();
        }

        return new JsonResponse(['error' => implode("\n", $errors)], 422);
    }
}
