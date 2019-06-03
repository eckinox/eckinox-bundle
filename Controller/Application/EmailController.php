<?php

namespace Eckinox\Controller\Application;

use Eckinox\Library\Symfony\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eckinox\Entity\Application\Email;
use Eckinox\Form\Application\EmailType;
use Eckinox\Library\General\Arrays;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eckinox\Library\Symfony\Annotation\Cron;
use Eckinox\Library\Symfony\Annotation\Security;
use Eckinox\Library\Symfony\Annotation\Breadcrumb;
use Eckinox\Library\Symfony\Annotation\Lang;

/**
 *  @Lang(domain="application", default_key="email")
 */
class EmailController extends Controller
{
    use \Eckinox\Library\Application\log;
    use \Eckinox\Library\General\appData;

    /**
     * @Route("/email/{page}", name="index_email", requirements={"page"="\d+", "templates"="\d+"})
     * @Breadcrumb(parent="home")
     */
    public function index(Request $request, $page = 1, $templates = false)
    {
        /*
         * Check if we have action to do
         */
        if($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();
            $ids = $request->request->get('ids');
            $action = $request->request->get('action');

            if($ids && $action) {
                $emails = $this->getDoctrine()
                    ->getRepository(Email::class)
                    ->findById($ids);
                $subjects = [];

                if(method_exists(Email::class, $action)) {
                    foreach($emails as $email) {
                        if($email->isSent() && $action === "delete" && !$templates){
                            $this->addFlash(
                                'warning',
                                $this->transChoice(
                                    'email.messages.warning.actionDelete',
                                    1,
                                    ["%subject%" => $email->getSubject()],
                                    'application'
                                )
                            );
                            continue;
                        }
                        $subjects[] = $email->getSubject();
                        $email->$action();
                        $em->persist($email);
                    }
                }
                $em->flush();

                $this->log(
                    $this->trans(
                        'email.logs.actions',
                        [],
                        'application'
                    ),
                    $this->logBuildAction(__FUNCTION__),
                    $_POST,
                    null,
                    'Email',
                    $this->getUser()
                );

                if(count($subjects)){
                    $this->addFlash(
                        'success',
                        $this->transChoice(
                            'email.messages.success.action'.ucfirst($action),
                            count($subjects),
                            ["%subjects%" => implode(', ', $subjects)],
                            'application'
                        )
                    );
                }
            }

        }

        $listing = EmailType::getListing($this);
        $search = $this->prepareSearch($request, $listing);

        $email_repository = $this->getDoctrine()->getRepository(Email::class);
        $maxResults = $this->data('application.email.config.list.items_shown') ?: 10;

        $emails = $templates ? $email_repository->getTemplates($page, $maxResults, $search) : $email_repository->getList($page, $maxResults, $search);
        $count = $templates ? $email_repository->getCountTemplates($search) : $email_repository->getCount($search);
        $nbPages = intval(ceil($count / $maxResults));

        return $this->renderModView('@Eckinox/application/email/index.html.twig', array(
            'emails' => $emails,
            'currentPage' => $page,
            'count' => $count,
            'listing' => $templates ? EmailType::getTemplatesListing($this) : EmailType::getListing($this),
            'nbPages' => $nbPages,
            'isTemplate' => $templates,
            'js_lang' => $this->lang_array('email.javascript'),
            'status' => ['sent' => 'email.status.sent', 'draft' => 'email.status.draft', 'unsent' => 'email.status.unsent', 'unsent_error' => 'email.status.unsent_error'],
            'title' => $this->lang('title.'.$request->get('_route')),
        ), $request);
    }

    /**
     * @Route("/email/templates/{page}", name="index_email_templates", requirements={"page"="\d+"})
     * @Breadcrumb(parent="home")
     * @Security("EMAIL_TEMPLATES")
     */
    public function indexTemplates(Request $request, $page = 1)
    {
        return $this->index($request, $page, true);
    }

    /**
     * @Route("/email/create", name="create_email")
     * @Route("/email/edit/{email_id}", name="edit_email", requirements={"email_id"="\d+", "templates"="\d+"})
     * @Breadcrumb(parent="index_email")
     */
    public function edit(Request $request, $email_id = null, AuthorizationCheckerInterface $authChecker, $templates = false)
    {
        $email = new Email();
        $user = $this->getUser();
        $currentData = [];
        $isNew = true;

        /*
         * Allowing parameters to be passed as query string
         */
        $to = $request->query->get('to')   ? explode(',', $request->query->get('to'))  : [''];
        $cc = $request->query->get('cc')   ? explode(',', $request->query->get('cc'))  : [''];
        $bcc = $request->query->get('bcc') ? explode(',', $request->query->get('bcc')) : [''];
        $user = $request->query->get('user') ? $request->query->get('user') : $this->getUser()->getEmail();
        $attachments = $request->query->get('attachment') ? explode(',', $request->query->get('attachment')) : [];
        $tmpFolder = $request->request->get('tmpPath') ?: uniqid($this->getUser()->getId())."/";
        $tmpPath = implode(DIRECTORY_SEPARATOR, [ sys_get_temp_dir(), $tmpFolder . "files" ]);

        $templateName = $request->query->get('template') ?: null;

        /*
         * Load email
         */
        if($email_id) {
            $isNew = false;

            $email = $this->getDoctrine()
                ->getRepository(Email::class)
                ->find($email_id);
        }
        else if($templateName){
            $email = $this->getDoctrine()
                ->getRepository(Email::class)
                ->getTemplateByName($templateName);
            $email = $email->useTemplate();
        } else {
            $email->setFrom( $user );
            $email->setSubject( $request->query->get('subject') );
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
        }

        /*
         * Prepare contacts list for form
         */
        $contacts = $this->getContacts();

        foreach($contacts as $key => $arr) {
            if(is_array($arr)) {
                $contacts[$key] = array_flip($arr);
            }
        }

        /*
         * Place quote PDF in the tmp folder if it exists
         */
        if(!empty($attachments)){
            foreach($attachments as $attachment){
                $tmp = explode('/', $attachment);
                if (!file_exists($tmpPath)) { mkdir($tmpPath, 0775 , true);}
                copy($this->getParameter('app.attachments.path')."..".DIRECTORY_SEPARATOR.ltrim($attachment, '.'.DIRECTORY_SEPARATOR),  $tmpPath.DIRECTORY_SEPARATOR.end($tmp));
            }
        }

        if (!$templates) {
            foreach($_GET as $key => $item) {
                $email->setSubject(str_replace(":$key:", $item, $email->getSubject()));
                $email->setHtml(str_replace(":$key:", $item, $email->getHtml()));
            }
        }

        $form = $this->createForm(EmailType::class, $email, [
            'users' => $this->getDoctrine()
                ->getRepository($this->getParameter('user_class'))
                ->getSelectableEmail(),
            'disabled' => $templates ? false : $email->isSent(),
            'from' => $email->getFrom() ?: $this->getUser()->getEmail(),
            'to' => (array)( $email->getTo() ?: $to ),
            'cc' => (array)( $email->getCc() ?: $cc ),
            'bcc' => (array)( $email->getBcc() ?: $bcc ),
            'subject' => $email->getSubject() ?: '',
            'html' => $email->getHtml() ?: '',
            'contacts' => $contacts,
            'required' => $templates ? '' : 'required',
        ]);

        /*
         * Get data before submit
         */
        $currentData += $this->getFormValues($form);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /*
             * Get differences between current and new data
             */
            $newData = $this->getFormValues($form);
            $dataDifferences = Arrays::diff($newData, $currentData);

            $email->setUpdatedAt();

            /*
             * Check if it's a template and change the date to 2000-01-01 00:00:00 if it is
             */
            if($templates && $isNew){
                $templateDate = new \DateTime('2000-01-01 00:00:00');

                $email->setSentAt($templateDate);
                $email->setStatus(null);
            }

            /*
             * Save email
             */
            $em->persist($email);
            $em->flush();

            /*
             *  Moving files from tmp into attachments path $tmpFolder
             */
            if (!$email_id && file_exists($tmpPath)) {
                $filePath = implode(DIRECTORY_SEPARATOR, [ $this->getParameter('app.attachments.path')."application", "email", $email->getId(), "files"]);

                if (!file_exists($filePath)) {
                    mkdir($filePath, 0775 , true);
                }

                foreach(array_diff(scandir($tmpPath), ['.', '..']) as $file) {
                    rename($tmpPath.DIRECTORY_SEPARATOR.$file, $filePath.DIRECTORY_SEPARATOR.$file);
                }
            }

            $this->log(
                $this->trans(
                    $isNew ? 'email.logs.created' : 'email.logs.updated',
                    ["%name%" => $email->getSubject()],
                    'application'
                ),
                $this->logBuildAction(__FUNCTION__),
                ['old_data' => $currentData, 'differences' => $dataDifferences],
                $email->getId(),
                'Email',
                $this->getUser()
            );

            $this->addFlash(
                'success',
                $this->trans(
                    $isNew ? 'email.messages.success.hasBeenCreated' : 'email.messages.success.hasBeenUpdated',
                    ["%name%" => $email->getSubject()],
                    'application'
                )
            );

            return $this->redirectToRoute( $templates ? 'edit_email_template' :'edit_email', ['email_id' => $email->getId()]);
        }

        if($isNew){
            $transTitle = $templates ? 'email.title.createTemplate' : 'email.title.create';
        }else{
            $transTitle = $templates ? 'email.title.editTemplate' : 'email.title.edit';
        }

        return $this->renderModView('@Eckinox/application/email/edit.html.twig', array(
            'form' => $form->createView(),
            'isNew' => $isNew,
            'isTemplate' => $templates,
            'email' => $email,
            'title' => $this->trans(
                $transTitle,
                ["%name%" => $email->getSubject()],
                'application'
            ),
            'breadcrumbVariables' => [
                'edit_email' => [
                    '%name%' => $email->getSubject()
                ],
                'edit_email_template' => [
                    '%name%' => $email->getSubject()
                ]
            ],
            'result' => [
                'form_action' => $isNew ? $this->generateUrl('edit_email', ['email_id' => $email->getId()]) : null,
            ],
            'forward'=> (strpos($email->getSubject(), 'Fwd:') !== false) ? true : false,
            'title' => $this->lang('title.'.$request->get('_route'), ["%name%" => $email->getSubject()]),
            'tmpPath' => $email_id ? false : $tmpFolder
        ), $request);
    }

    /**
     * @Route("/email/templates/create", name="create_email_template")
     * @Route("/email/templates/edit/{email_id}", name="edit_email_template", requirements={"email_id"="\d+"})
     * @Breadcrumb(parent="index_email")
     * @Security("EMAIL_TEMPLATES")
     */
    public function editTemplate(Request $request, $email_id = null, AuthorizationCheckerInterface $authChecker)
    {
        return $this->edit($request, $email_id, $authChecker, true);
    }

    /**
     * @Route("/email/forward/{email_id}", name="forward_email", requirements={"email_id"="\d+"})
     * @Breadcrumb(parent="index_email")
     */
    public function forwardEmail(Request $request, $email_id)
    {
        $email = new Email();
        $user = $this->getUser();
        $currentData = [];

        /*
         * Load email
         */
        $email = $this->getDoctrine()
            ->getRepository(Email::class)
            ->find($email_id);

        $subject = $email->getSubject();
        $email = $email->forward();
        $email->setDraft(false);

        if($request->isMethod('POST')) {
            $data = $request->request->all();
        }

        /*
         * Prepare contacts list for form
         */
        $contacts = $this->getContacts();

        foreach($contacts as $key => $arr) {
            if(is_array($arr)) {
                $contacts[$key] = array_flip($arr);
            }
        }

        $form = $this->createForm(EmailType::class, $email, [
            'users' => $this->getDoctrine()
                ->getRepository($this->getParameter('user_class'))
                ->getSelectableEmail(),
            'disabled' => $email->isSent(),
            'to' => $email->getTo() ?: [''],
            'cc' => $email->getCc() ?: [''],
            'bcc' => $email->getBcc() ?: [''],
            'contacts' => $contacts,
        ]);

        /*
         * Get data before submit
         */
        $currentData += $this->getFormValues($form);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /*
             * Get differences between current and new data
             */
            $newData = $this->getFormValues($form);
            $dataDifferences = Arrays::diff($newData, $currentData);

            $email->setUpdatedAt();

            /*
             * Save email
             */
            $em->persist($email);
            $em->flush();

            $parentPath = implode(DIRECTORY_SEPARATOR, [ $this->getParameter('app.attachments.path')."application", "email", $email_id, "files"]);
            $folderPath = implode(DIRECTORY_SEPARATOR, [ $this->getParameter('app.attachments.path')."application", "email", $email->getId(), "/files"]);

            if(!file_exists($folderPath)) { mkdir($folderPath, 0775 , true); }

            foreach(array_diff(scandir($parentPath), ['.', '..']) as $file) {
                copy($parentPath . DIRECTORY_SEPARATOR . $file, $folderPath . DIRECTORY_SEPARATOR . $file);
            }

            $this->log(
                $this->trans(
                    'email.logs.forwarded',
                    ["%name%" => $email->getSubject()],
                    'application'
                ),
                $this->logBuildAction(__FUNCTION__),
                ['old_data' => $currentData, 'differences' => $dataDifferences],
                $email->getId(),
                'Email',
                $this->getUser()
            );

            $this->addFlash(
                'success',
                $this->trans(
                    'email.messages.success.hasBeenForwarded',
                    ["%name%" => $email->getSubject()],
                    'application'
                )
            );

            return $this->redirectToRoute('edit_email', ['email_id' => $email->getId()]);
        }

        return $this->renderModView('@Eckinox/application/email/edit.html.twig', array(
            'form' => $form->createView(),
            'isNew' => $email->getId(),
            'email' => $email,
            'tmpPath' => null,
            'title' => $this->lang('title.'.$request->get('_route'), ["%name%" => $subject]),
            'breadcrumbVariables' => [
                'forward_email' => [
                    '%name%' => $email->getSubject()
                ]
            ],
            'forward' => true,
        ), $request);
    }

    /**
     * @Route("/cron/emails", name="cron_send_emails")
     * @Cron("every_min(5)")
     */
    public function sendBatch(\Swift_Mailer $mailer) {
        $em = $this->getDoctrine()->getManager();

        $emails = $this->getDoctrine()
            ->getRepository(Email::class)
            ->getUnsent(5);

         /*
          * Prevent to reload mails being sent
          */
        $nullDate = new \DateTime('0001-01-01 00:00:00');

        foreach($emails as $email) {
            $email->setSentAt($nullDate);
            $em->persist($email);
        }

        $em->flush();

        /*
         * Send mails
         */
        foreach($emails as $email) {
            $email->setUpdatedAt();

            try {
                if ($this->send($email, $mailer)) {
                    $email->setStatus('sent');
                    $email->setSentAt();
                }
            } catch (\Exception $e){
                $email->setStatus('unsent_error');
            }

            $em->persist($email);
        }

        $em->flush();

        return new Response('');
    }

    /**
     * @Route("/email/force_send/{email_id}", name="force_send_email", requirements={"email_id"="\d+"})
     */
    public function forceSend(\Swift_Mailer $mailer, $email_id) {
        $em = $this->getDoctrine()->getManager();

        $email = $this->getDoctrine()
            ->getRepository(Email::class)
            ->find($email_id);

        /*
         * Send mail
         */
        $email->setUpdatedAt();

        if($this->send($email, $mailer)) {
            $email->setSentAt();
            $email->setStatus("sent");
        }

        $em->persist($email);
        $em->flush();

        $this->addFlash(
                'success',
                $this->trans(
                    'email.messages.success.hasBeenForceSent',
                    ["%name%" => $email->getSubject()],
                    'application'
                )
            );

        return $this->redirectToRoute('edit_email', ['email_id' => $email_id]);
    }


    public function send(Email $email, \Swift_Mailer $mailer)
    {
        $em = $this->getDoctrine()->getManager();

        $layout = $email->getLayout() ?: '@Eckinox/application/email/trello.html.twig';
        $html = $this->renderView($layout, [
            "page_title" => $email->getSubject(),
            "content" => $email->getHtml(),
            "footer" => "",
        ]);

        if ( ( $from = $email->getFrom() ) && ( $to = $email->getTo() ) )  {
            $message = (new \Swift_Message($email->getSubject()))
                ->setFrom($from, $email->getFromName())
                ->setTo($to)
                ->setBody($html, 'text/html')
            ;

            if($cc = $email->getCc()) {
                $message->setCc($cc);
            }

            if($bcc = $email->getBcc()) {
                $message->setBcc($bcc);
            }

            $attachmentPath = implode(DIRECTORY_SEPARATOR, [ $this->getParameter('app.attachments.path')."application", "email", $email->getId(), "files"]);

            if(file_exists($attachmentPath)){
                foreach(array_diff(scandir($attachmentPath), ['.', '..']) as $attachment) {
                    # $attachment must be sanitized before used !
                    $message->attach(\Swift_Attachment::fromPath($attachmentPath.DIRECTORY_SEPARATOR.$attachment));
                }
            }

            return $mailer->send($message);
        }
        else {
            return $this->renderText('An invalid email was found having '.json_encode([ 'to' => $to, 'from' => $from, 'cc' => $cc, 'bcc' => $bcc, 'html' => $html ]));
        }
    }

    public function getContacts() {
        $contacts = [];

        /*
         * Users
         */
        $users = $this->getDoctrine()
            ->getRepository($this->getParameter('user_class'))
            ->getSelectableEmail();

        return array_merge(["Utilisateurs" => $users], $contacts);
    }

}
