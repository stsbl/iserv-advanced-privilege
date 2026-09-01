<?php

declare(strict_types=1);

namespace Stsbl\IServ\AdvancedPrivilege\Controller;

use IServ\Library\ModuleResponse\ResponseContent;
use IServ\Library\Breadcrumb\Breadcrumb;
use IServ\Bundle\TranslationGettext\Asset\TranslationAssetLoader;
use IServ\Bundle\AdminIntegration\Controller\AbstractAdminController;
use IServ\Bundle\AdminIntegration\Menu\AdminBreadcrumbsInterface;
use Stsbl\IServ\AdvancedPrivilege\Form\GroupMutationType;
use Stsbl\IServ\AdvancedPrivilege\Form\OwnerMutationType;
use Stsbl\IServ\AdvancedPrivilege\Model\GroupMutation;
use Stsbl\IServ\AdvancedPrivilege\Model\OwnerMutation;
use Stsbl\IServ\AdvancedPrivilege\Service\BulkMutationHandler;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/admin')]
final class AdvancedPrivilegeController extends AbstractAdminController
{
    public function __construct(
        private readonly FormFactoryInterface $forms,
        private readonly AdminBreadcrumbsInterface $adminBreadcrumbs,
        private readonly Packages $assets,
        private readonly TranslationAssetLoader $translationAssets,
    ) {
    }

    #[Route('/advanced-privilege', name: 'advanced_privilege_index', methods: ['GET'])]
    public function index(): ResponseContent
    {
        $content = $this->renderView('advanced_privilege/index.html.twig', [
            'assign_form' => $this->groupForm(GroupMutation::ASSIGN)->createView(),
            'revoke_form' => $this->groupForm(GroupMutation::REVOKE)->createView(),
            'owner_form' => $this->ownerForm()->createView(),
        ]);

        $builder = $this->createResponseBuilder($content)
            ->addStylesheet($this->assets->getUrl('js/form.css', 'iserv-form'))
            ->addScript($this->assets->getUrl('js/form.js', 'iserv-form'))
            ->addStylesheet($this->assets->getUrl('js/autocomplete.css', 'iserv-autocomplete'))
            ->addScript($this->assets->getUrl('js/autocomplete.js', 'iserv-autocomplete'))
            ->addScript($this->assets->getUrl('js/advanced-privilege.js'))
        ;
        $this->translationAssets->loadIntoBuilder($builder, ['iserv-js', 'iserv-form']);

        return $builder
            ->addBreadcrumb($this->adminBreadcrumbs->root())
            ->addBreadcrumb(Breadcrumb::createLink(_('Privileges'), '/iserv/admin/privileges'))
            ->setTitle(_('Advanced privilege assignment'))
            ->addBreadcrumb(_('Advanced privilege assignment'))
            ->getResponseContent()
        ;
    }

    #[Route('/advanced-privilege/apply', name: 'advanced_privilege_apply', methods: ['POST'])]
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

                $updated = $handler->updateGroups($mutation);

                return new JsonResponse(['updated' => $updated, 'messages' => $handler->resultMessages($mutation, $updated)]);
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

        $updated = $handler->updateOwner($mutation);

        return new JsonResponse(['updated' => $updated, 'messages' => $handler->resultMessages($mutation, $updated)]);
    }

    #[Route('/advanced-privilege/preview', name: 'advanced_privilege_preview', methods: ['POST'])]
    public function preview(Request $request, BulkMutationHandler $handler): JsonResponse
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

                return new JsonResponse(['groups' => $handler->affectedGroups($mutation), 'changes' => $handler->changes($mutation)]);
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

        return new JsonResponse(['groups' => $handler->affectedGroups($mutation), 'changes' => $handler->changes($mutation)]);
    }

    #[Route('/advanced-privilege/groups-preview', name: 'advanced_privilege_groups_preview', methods: ['GET'])]
    public function groupsPreview(Request $request, BulkMutationHandler $handler): JsonResponse
    {
        try {
            $groups = $handler->groupPreviewsForTarget(
                $request->query->getString('target'),
                $request->query->getString('pattern'),
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], 422);
        }

        return new JsonResponse(['groups' => $groups]);
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
