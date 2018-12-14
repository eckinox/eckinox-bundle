<?php

namespace Eckinox\Entity\Application;

use Doctrine\ORM\Mapping as ORM;
use Eckinox\Entity\Application\User;

/**
 * @ORM\Table(name="ei_alerts")
 * @ORM\Entity(repositoryClass="Eckinox\Repository\Application\AlertRepository")
 */
class Alert
{
    use \Eckinox\Library\Entity\baseEntity;
    use \Eckinox\Library\Entity\loggableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=45, nullable=true)
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $message;

    /**
     * @ORM\Column(type="json_array", nullable=true)
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
     * @ORM\Column(type="string", columnDefinition="ENUM('pending', 'resolved', 'deleted')", options={"default":"pending"})
     */
    private $status;

    public function __construct()
    {
        $this->setCreatedAt();
        $this->setStatus("pending");
    }

    public function getId()
    {
        return $this->id;
    }

    public function getType(){
		return $this->type;
	}

	public function setType($type){
		$this->type = $type;

        return $this;
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

	public function getData($key = null) {
		return $key ? $this->data[$key] ?? null : $this->data;
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

    public function getStatus(){
		return $this->status;
	}

	public function setStatus($status){
		$this->status = $status;

        return $this;
	}

	public function delete() {
		$this->status = 'deleted';

        return $this;
	}

	public function putOnHold() {
		$this->status = 'pending';

        return $this;
	}

	public function resolve() {
		$this->status = 'resolved';

        return $this;
	}
}
