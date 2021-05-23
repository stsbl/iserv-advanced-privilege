<?php

declare(strict_types=1);

namespace Stsbl\AdvancedPrivilegeBundle\Controller;

use IServ\CoreBundle\Controller\AbstractPageController;
use IServ\CoreBundle\HttpFoundation\JsonResponse as JsonStatusResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\AdvancedPrivilegeBundle\Form\Type\GroupChoiceType;
use Stsbl\AdvancedPrivilegeBundle\Form\Type\OwnerChoiceType;
use Stsbl\AdvancedPrivilegeBundle\Model\GroupChoice;
use Stsbl\AdvancedPrivilegeBundle\Service\GroupHandler;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Admin controller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 * @Route("admin/privileges")
 */
final class AdminController extends AbstractPageController
{
    /**
     * Get multiple assign/revoke form
     */
    private function createGroupChoiceForm(string $action): FormInterface
    {
        return $this->get('form.factory')->createNamed(
            $action,
            GroupChoiceType::class,
            null,
            ['action' =>$this->generateUrl('admin_adv_priv_send'), 'action_type' => $action]
        );
    }

    /**
     * Get form for changing the owner of mutliple groups
     */
    private function getOwnerForm(): FormInterface
    {
        return $this->get('form.factory')->createNamed(
            'owner',
            OwnerChoiceType::class,
            null,
            ['action' =>$this->generateUrl('admin_adv_priv_send')]
        );
    }

    /**
     * Builds a JsonErrorResponse with all form errors.
     */
    private function buildFormErrorResponse(FormInterface $form): JsonStatusResponse
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = htmlspecialchars($error->getMessage());
        }

        return JsonStatusResponse::createError(nl2br(join("\n", $errors)));
    }

    /**
     * Handles submitted forms
     *
     * @Route("/advanced/send", name="admin_adv_priv_send", options={"expose": true}, methods={"POST"})
     */
    public function send(Request $request, GroupHandler $handler): JsonResponse
    {
        $assignForm = $this->createGroupChoiceForm(GroupChoice::ACTION_ASSIGN);
        $assignForm->handleRequest($request);
        $revokeForm = $this->createGroupChoiceForm(GroupChoice::ACTION_REVOKE);
        $revokeForm->handleRequest($request);
        $ownerForm = $this->getOwnerForm();
        $ownerForm->handleRequest($request);

        if ($assignForm->isSubmitted()) {
            if ($assignForm->isValid()) {
                if (!$handler->updateGroups($assignForm->getData())) {
                    return JsonStatusResponse::createError(_('Unexpected error during updating of groups.'));
                }

                return new JsonResponse(['msg' => $handler->getMessages()]);
            }

            return $this->buildFormErrorResponse($assignForm);
        }

        if ($revokeForm->isSubmitted()) {
            if ($revokeForm->isValid()) {
                if (!$handler->updateGroups($revokeForm->getData())) {
                    return JsonStatusResponse::createError(_('Unexpected error during updating of groups.'));
                }

                return new JsonResponse(['msg' => $handler->getMessages()]);
            }

            return $this->buildFormErrorResponse($revokeForm);
        }

        if ($ownerForm->isSubmitted()) {
            if ($ownerForm->isValid()) {
                if (!$handler->updateOwner($ownerForm->getData())) {
                    return JsonStatusResponse::createError(_('Unexpected error during updating of groups.'));
                }

                return new JsonResponse(['msg' => $handler->getMessages()]);
            }

            return $this->buildFormErrorResponse($ownerForm);
        }

        throw new BadRequestHttpException('This statement should never be reached!');
    }

    /**
     * @Route("/advanced", name="admin_adv_priv")
     * @Template()
     */
    public function index(): array
    {
        $assignForm = $this->createGroupChoiceForm(GroupChoice::ACTION_ASSIGN);
        $revokeForm = $this->createGroupChoiceForm(GroupChoice::ACTION_REVOKE);
        $ownerForm = $this->getOwnerForm();

        // track path
        $this->addBreadcrumb(_('Privileges'), $this->generateUrl('admin_privilege_index'));
        $this->addBreadcrumb(_('Advanced privilege assignment'), $this->generateUrl('admin_adv_priv'));

        return [
            'multipleAssignForm' => $assignForm->createView(),
            'multipleRevokeForm' => $revokeForm->createView(),
            'multipleOwnerForm' => $ownerForm->createView(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        $services = parent::getSubscribedServices();
        $services['form.factory'] = FormFactoryInterface::class;

        return $services;
    }
}
