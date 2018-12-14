<?php

namespace Eckinox\Entity\Application;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ei_emails")
 * @ORM\Entity(repositoryClass="Eckinox\Repository\Application\EmailRepository")
 */
class Email
{
    use \Eckinox\Library\Entity\baseEntity;
    use \Eckinox\Library\Entity\loggableEntity;

    /**
     * @ORM\Column(name="template_name", type="string", length=70, nullable=true)
     */
    private $templateName;

    /**
     * @ORM\Column(name="`to`", type="json")
     */
    private $to;

    /**
     * @ORM\Column(type="json", length=255, nullable=true)
     */
    private $cc;

    /**
     * @ORM\Column(type="json", length=255, nullable=true)
     */
    private $bcc;

    /**
     * @ORM\Column(name="`from`", type="string", length=255)
     */
    private $from;

    /**
     * @ORM\Column(name="from_name", type="string", length=70, nullable=true)
     */
    private $fromName;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(name="`text`", type="text", nullable=true)
     */
    private $text;

    /**
     * @ORM\Column(type="text")
     */
    private $html;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $attachment;

	/**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $layout;

	/**
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    private $objectId;

	/**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $module;

    /**
     * @ORM\ManyToOne(targetEntity="Eckinox\Entity\Application\User", inversedBy="emails")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private $user;

	/**
     * @ORM\Column(type="boolean", nullable=true)
     */
	private $draft;

    /**
     * @ORM\Column(name="status_key", type="string", length=125, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(name="sent_at", type="datetime", nullable=true)
     */
    private $sentAt;

	public function __construct() {
		$this->setCreatedAt();
        $this->status = 'unsent';
	}

    public function getTemplateName(){
        return $this->templateName;
    }
    public function setTemplateName($templateName){
        $this->templateName = $templateName;

        return $this;
    }

    public function getTo(){
		return $this->to;
	}

	public function setTo($to){
		$this->to = (array)$to;

        return $this;
	}

	public function getCc(){
		return $this->cc;
	}

	public function setCc($cc){
		$this->cc = (array)$cc;

        return $this;
	}

	public function getBcc(){
		return $this->bcc;
	}

	public function setBcc($bcc){
		$this->bcc = (array)$bcc;

        return $this;
	}

	public function getFrom(){
		return $this->from;
	}

	public function setFrom($from){
		$this->from = $from;

        return $this;
	}

	public function getFromName(){
		return $this->fromName;
	}

	public function setFromName($fromName){
		$this->fromName = $fromName;

        return $this;
	}

	public function getSubject(){
		return $this->subject;
	}

	public function setSubject($subject){
		$this->subject = $subject;

        return $this;
	}

	public function getText(){
		return $this->text;
	}

	public function setText($text){
		$this->text = $text;

        return $this;
	}

	public function getHtml(){
		return $this->html;
	}

	public function setHtml($html){
		$this->html = $html;

        return $this;
	}

	public function getAttachment(){
		return $this->attachment;
	}

	public function setAttachment($attachment){
		$this->attachment = $attachment;

        return $this;
	}

	public function getSentAt(){
		return $this->sentAt;
	}

	public function setSentAt($datetime = null) {
        $this->sentAt = $this->_datetime($datetime);

        return $this;
    }

	public function getBreadcrumbTitle() {
        return $this->getSubject();
    }

	public function getObjectId(){
		return $this->objectId;
	}

	public function setObjectId($objectId){
		$this->objectId = $objectId;

        return $this;
	}

	public function getModule(){
		return $this->module;
	}

	public function setModule($module){
		$this->module = $module;

        return $this;
	}

	public function setUser($user){
		$this->user = $user;

        return $this;
	}

	public function getUser(){
		return $this->user;
	}

	public function getLayout(){
		return $this->layout;
	}

	public function setLayout($layout){
		$this->layout = $layout;

        return $this;
	}

	public function isSent() {
		$nullDate = new \DateTime('0001-01-01 00:00:00');

		return $this->sentAt && $this->sentAt->format('Y-m-d H:i:s') !== $nullDate->format('Y-m-d H:i:s');
	}

	public function setDraft($draft){
	    $this->status = $draft ? 'draft' : 'unsent';

		$this->draft = $draft;

        return $this;
	}

	public function isDraft(){
		return $this->draft;
	}


	public function getStatus(){
        return $this->status ?: ($this->isSent() ? 'sent' : ($this->isDraft() ? 'draft' : 'unsent'));;
    }

    public function setStatus($status){
        $this->status = $status;

        return $this;
    }

	public function forward() {
		$email = clone $this;

		$email->to = [];
		$email->cc = [];
		$email->bcc = [];
		$email->draft = true;
		$email->status = 'draft';
		$email->sentAt = null;
		$email->setCreatedAt();
		$email->subject = 'Fwd: ' . $this->subject;

		return $email;
	}

    public function delete(){
        $this->status = 'deleted';

        return $this;
    }

    public function isTemplate(){
        return $email->templateName != null;
    }

    public function useTemplate() {
		$email = clone $this;

		$email->to = $this->to ?: [];
		$email->cc = $this->cc ?: [];
		$email->bcc = $this->bcc ?: [];
		$email->draft = false;
		$email->status = 'unsent';
		$email->sentAt = null;
		$email->setCreatedAt();
		$email->subject = $this->subject;
        $email->html = $this->html ?: '<p><br></p>';
        $email->templateName = null;

		return $email;
	}
}
