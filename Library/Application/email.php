<?php

namespace Eckinox\Library\Application;

use Eckinox\Entity\Application\Email as EmailEntity;

trait email {

    public function email($params = []) {
        $em = $this->getEntityManagerInstance($this);
        $email = new EmailEntity();
        $issetHtmlAndSubject = false;

        if ($params['template_name'] ?? false) {
            $email = $this->getDoctrine()
                ->getRepository(EmailEntity::class)
                ->getTemplateByName($params['template_name']);
            $email = $email->useTemplate();

            foreach($params['vars'] as $key => $item) {
                $email->setSubject(str_replace(":$key:", $item, $email->getSubject()));
                $email->setHtml(str_replace(":$key:", $item, $email->getHtml()));
            }

            $params['vars'] ? $issetHtmlAndSubject = true : $issetHtmlAndSubject = false;
        }

        isset($params['to']) ? $email->setTo($params['to']) : ( $email->getTo() ?: $email->setTo([]) );
        isset($params['cc']) ? $email->setCc($params['cc']) : ( $email->getCc() ?: $email->setCc([]) );
        isset($params['bcc']) ? $email->setBcc($params['bcc']) : ( $email->getBcc() ?: $email->setBcc([]) );
        isset($params['from']) ? $email->setFrom($params['from']) : ( $email->getFrom() ?: $email->setFrom($_SERVER['EMAIL_FROM'] ?? ('noreply@' . $_SERVER['SERVER_NAME']) ));
        isset($params['from_name']) ? $email->setFromName($params['from_name']) : ( $email->getFromName() ?: $email->setFromName($_SERVER['EMAIL_FROM_NAME'] ?? null) );
        isset($params['text']) ? $email->setText($params['text']) : ( $email->getText() ?: $email->setText(null) );
        isset($params['subject']) ? $email->setSubject($params['subject']) : ( $email->getSubject() ?: $email->setSubject(null) );
        isset($params['html']) ? $email->setHtml($params['html']) : ( $email->getHtml() ?: $email->setHtml(null) );
        isset($params['attachment']) ? $email->setAttachment($params['attachment']) : ( $email->getAttachment() ?: $email->setAttachment(null) );
        isset($params['object_id']) ? $email->setObjectId($params['object_id']) : ( $email->getObjectId() ?: $email->setObjectId(null) );
        isset($params['module']) ? $email->setModule($params['module']) : ( $email->getModule() ?: $email->setModule(null) );
        isset($params['module']) ? $email->setLayout($params['layout']) : ( $email->getLayout() ?: $email->setLayout('@Eckinox/application/email/trello.html.twig') );
        isset($params['user']) ? $email->setUser($params['user']) : ( $email->getUser() ?: $email->setUser(null) );

        $email->setDraft(isset($params['is_draft']) && $params['is_draft']);
        $email->setTemplateName(null);
        $email->setCreatedAt();

        $em->persist($email);
        $em->flush();
    }

}
