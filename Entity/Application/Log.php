<?php

namespace Eckinox\Entity\Application;

use Doctrine\ORM\Mapping as ORM;
use Eckinox\Entity\Application\User;

/**
 * @ORM\Table(name="ei_logs")
 * @ORM\Entity(repositoryClass="Eckinox\Repository\Application\LogRepository")
 */
class Log
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $action;

    /**
     * @ORM\Column(type="json_array")
     */
    private $data;

    /**
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    private $objectId;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $module;

    /**
     * @ORM\ManyToOne(targetEntity="Eckinox\Entity\Application\User", inversedBy="logs")
     */
    private $user;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     */
    private $createdAt;

    public function __construct()
    {
        $this->setCreatedAt();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    public function getObjectId()
    {
        return $this->objectId;
    }

    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function setCreatedAt($datetime = null) {
        $this->createdAt = $datetime ?: new \DateTime('now') ;

        return $this;
    }

    public function getCreatedAt() {
        return $this->createdAt;
    }
}
