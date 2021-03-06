<?php

namespace Eckinox\Controller\Application;

use Eckinox\Entity\Application\Log;
use Eckinox\Library\General\Arrays;
use Eckinox\Library\General\Serializer;
use Eckinox\Library\General\StringEdit;
use Eckinox\Library\Symfony\Annotation\Breadcrumb;
use Eckinox\Library\Symfony\Annotation\Lang;
use Eckinox\Library\Symfony\Annotation\Security;
use Eckinox\Library\Symfony\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 *  @Lang(domain="application", default_key="user")
 */
class UserController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    protected $securityRedirect = 'index_user';

    /**
     * @Route("/user/{page}", name="index_user", requirements={"page"="\d+"})
     * @Security(privilege="USER_LIST")
     * @Breadcrumb(parent="home")
     */
    public function index(Request $request, $page = 1)
    {
        /*
         * Check if we have action to do
         */
        if($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();
            $ids = $request->request->get('ids');
            $action = $request->request->get('action');

            if($ids && $action) {
                $users = $this->getDoctrine()
                    ->getRepository($this->getParameter('user_class'))
                    ->findById($ids);

                $names = [];

                if(method_exists($this->getParameter('user_class'), $action)) {
                    foreach($users as $user) {

                        if($this->getUser()->getId() == $user->getId()) {
                            $this->addFlash(
                                'warning',
                                $this->trans(
                                    'user.messages.warning.actionOwn',
                                    [],
                                    'application'
                                )
                            );

                            continue;
                        }
                        $names[] = $user->getFullName();

                        $user->$action();
                        $em->persist($user);
                    }
                }

                if($names) {
                    $em->flush();

                    $this->log(
                        $this->trans(
                            'user.logs.actions',
                            [],
                            'application'
                        ),
                        $this->logBuildAction(__FUNCTION__),
                        $_POST,
                        null,
                        'User',
                        $this->getUser()
                    );

                    $this->addFlash(
                        'success',
                        $this->translator->trans(
                            'user.messages.success.action' . ucfirst($action),
                            [
                                "%count%" => count($names),
                                "%names%" => implode(', ', $names)
                            ],
                            'application'
                        )
                    );
                }
            }

        }

        $userFormType = $this->getParameter('user_form_type_class');
        $listing = $userFormType::getListing($this);
        $search = $this->prepareSearch($request, $listing);

        $user_repository = $this->getDoctrine()->getRepository($this->getParameter('user_class'));
        $maxResults = $this->data('application.user.config.list.items_shown') ?: 10;

        $users = $user_repository->getList($page, $maxResults, $search);
        $count = $user_repository->getCount($search);
        $nbPages = intval(ceil($count / $maxResults));

        return $this->renderModView('@Eckinox/application/user/index.html.twig', array(
            'currentPage' => $page,
            'count' => $count,
            'listing' => $listing,
            'nbPages' => $nbPages,
            'users' => $users,
            'js_lang' => $this->lang_array('user.javascript'),
            'status' => ['active' => 'user.status.active', 'inactive' => 'user.status.inactive'],
            'title' => $this->lang('title.'.$request->get('_route')),
        ), $request);
    }

    /**
     * @Route("/user/create", name="create_user")
     * @Route("/user/edit/{user_id}", name="edit_user", requirements={"user_id"="\d+"})
     * @Security(privilege="USER_CREATE_EDIT")
     * @Breadcrumb(parent="index_user")
     */
    public function edit(Request $request, $user_id = null, AuthorizationCheckerInterface $authChecker, UserPasswordHasherInterface $passwordHasher)
    {
        $self = $this->getUser();
        $userClass = $this->getParameter('user_class');
        $user = new $userClass();
        $currentData = [];
        $isNew = true;
        $emailIsValid = true;

        /*
         * Load user
         */
        if($user_id) {
            $isNew = false;

            $user = $this->getDoctrine()
                ->getRepository($userClass)
                ->find($user_id);
        }
        else {
            $user->setVariables([]);
        }

        /*
         * Get privileges list and group
         */
        $privileges = [];
        $privilegesGroups = [];
        $privilegesGroupsAccessible = false;

        if ($this->getUser()->hasPrivilege('USER_EDIT_PRIVILEGES_GROUP')) {
            foreach ($this->data('privileges.groups') as $group => $unused) {
                if (!$privilegesGroupsAccessible && $group == $self->getPrivilegesGroup()) {
                    $privilegesGroupsAccessible = true;
                }

                if ($privilegesGroupsAccessible) {
                    $privilegesGroups[$this->trans(implode('.', ['privileges', 'groups', $group]), [], 'application')] = $group;
                }
            }
        }

        if($this->getUser()->hasPrivilege('USER_EDIT_PRIVILEGES')) {
            $privilegeData = $this->data('privileges.privileges');
            if ($privilegeData && (is_array($privilegeData) || $privilegeData instanceof \Traversable)) {
                foreach($privilegeData as $moduleName => $items) {
                    $cleanModuleName = $this->trans(implode('.', ['privileges', 'modules', $moduleName]), [], 'application');
                    foreach($items as $privilegeId) {
                        $privileges[$cleanModuleName][$this->trans(implode('.', ['privileges', 'labels', $moduleName, $privilegeId]), [], 'application')] = $privilegeId;
                    }
                }
            }

            $importData = $this->data('import');
            if ($importData && (is_array($importData) || $importData instanceof \Traversable)) {
                foreach ($importData as $importType => $settings) {
                    $cleanModuleName = $this->trans('privileges.modules.import'    , [], 'application');
                    $privilegeId = 'IMPORT_' . strtoupper(StringEdit::camelToSnakeCase($importType));
                    $privileges[$cleanModuleName][$this->trans(implode('.', ['privileges', 'labels', 'import', $privilegeId]), [], 'application')] = $privilegeId;
                }
            }
        }

        /*
         * We keep the current password. We need it if the user doesn't change it !
         */
        $currentPassword = $user->getPassword();

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $email = $data['user']['left']['email'];

            $result = $this->getDoctrine()
                ->getRepository($userClass)
                ->findOneBy([
                    "email" => $email
                ]);

            if($result && $result->getId() != $user_id) {
               $emailIsValid = false;

               $this->addFlash('error', $this->trans(
                   'user.messages.error.emailAlreadyExists',
                   ["%email%" => $email],
                   'application'
               ));
            }
        }

        $form = $this->createForm($this->getParameter('user_form_type_class'), $user, [
            "privilegesGroup" => $privilegesGroups,
            "privileges" => $privileges,
            "emailIsValid" => $emailIsValid,
        ]);

        /*
         * Get data before submit
         */
        $currentData = $this->getFormValues($form, []);

        $em = $this->getDoctrine()->getManager();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $emailIsValid) {
            /*
             * Get differences between current and new data
             */
            $newData = $this->getFormValues($form, []);
            $dataDifferences = Arrays::diff($newData, $currentData);

            $user->setUsername($user->getEmail());

            /*
             * Password
             */
            $password = $form->getData()->getPassword();

            if (!empty($password))  {
                $encoded = $passwordHasher->hashPassword($user, $password);

                $user->setPassword($encoded);
            } else {
                $user->setPassword($currentPassword);
            }

            $user->setUpdatedAt();

            /*
             * Save user
             */
            $em->persist($user);
            $em->flush();

            $this->log(
                $this->trans(
                    $isNew ? 'user.logs.created' : 'user.logs.updated',
                    ["%name%" => $user->getFullName()],
                    'application'
                ),
                $this->logBuildAction(__FUNCTION__),
                ['old_data' => $currentData, 'differences' => $dataDifferences],
                $user->getId(),
                'User',
                $this->getUser()
            );

            $this->addFlash(
                'success',
                $this->trans(
                    $isNew ? 'user.messages.success.hasBeenCreated' : 'user.messages.success.hasBeenUpdated',
                    ["%name%" => $user->getFullName()],
                    'application'
                )
            );

            return $this->redirectToRoute('edit_user', [ 'user_id' => $user->getId() ]);
        }

        // For some reason, the user is persisted and flushed even if there are validation errors in the form.
        // This prevents invalid data from being saved when an error occurs.
        $em->detach($user);

        return $this->renderModView('@Eckinox/application/user/edit.html.twig', array(
            'form' => $form->createView(),
            'privilegesGroups' => $this->data('privileges.groups'),
            'isNew' => $isNew,
            'user' => $user,
            'breadcrumbVariables' => [
                'edit_user' => [
                    '%name%' => $user->getFullName(),
                ]
            ],
            'result' => [
                'form_action' => !$user_id ? $this->generateUrl('edit_user', ['user_id' => $user->getId()]) : null,
            ],
            'title' => $this->getUser()->getId() === $user->getId() ? $this->lang('title.profile') : $this->lang('title.'.$request->get('_route'), ["%name%" => $user->getFullName()]),
        ), $request);
    }

    /**
     * @Route("/ajax/user/vars", name="ajax_user_vars")
     */
    public function handleVars() {
        $user = $this->getUser();
        $retval = [];

        // Set / get for a single variable
        if ( $name = $_GET['name'] ?? $_POST['name'] ?? false ) {
            $user = $this->getUser();

            if ( $value = $_POST['value'] ?? null ) {
                $user->setVar($name, $value);

                $this->_save_user($user);
            }

            $retval = $user->getVar($name);
        }
        // Save a bag of variables, allowing multiple vars to be sets at once
        elseif ( $varlist = $_POST['varlist'] ?? false ) {

            foreach(json_decode($varlist, true) as $key => $value) {
                $user->setVar($key, $value);
            }

            $this->_save_user($user);
        }
        else {
            $retval = $user->getVariables();
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent( json_encode( $retval ) );

        return $response;
    }


    protected function _save_user($user) {
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
    }
}
