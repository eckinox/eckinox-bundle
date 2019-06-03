<?php

namespace Eckinox\Entity\Application;

use Doctrine\ORM\Mapping as ORM;
use Eckinox\Entity\Application\User;

/**
 * @ORM\Entity
 */
class PasswordResetRequest
{
    use \Eckinox\Library\Entity\baseEntity;

	/**
	 * @ORM\Column(type="datetime")
	 */
	protected $date;

	/**
	 * @ORM\Column(type="text")
	 */
	protected $code;

	/**
	 * @ORM\Column(type="boolean")
	 */
	protected $used;

	/**
	 * @ORM\ManyToOne(targetEntity="User", inversedBy="passwordResetRequests")
	 */
	protected $user;

	public function __construct($user)
	{
		$this->date = new \DateTime();
		$this->used = false;
		$this->user = $user;

		$hash = substr(md5(random_bytes(10)), 0, 10);
		# Splice in the user ID within the hash, and prefix it with the index at which the ID appears (excluding the prefix)
		$userIdIndex = rand(0, 9);
		$this->code = $userIdIndex . strlen($user->getId()) . substr_replace($hash, $user->getId(), $userIdIndex, 0);
	}

	public function getId()
	{
		return $this->id;
	}

	public function setId($id)
	{
		$this->id = $id;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function setDate($date)
	{
		$this->date = $date;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function setCode($code)
	{
		$this->code = $code;
	}

	public function getUser()
	{
		return $this->user;
	}

	public function setUser($user)
	{
		$this->user = $user;
	}

	public function getUsed()
	{
		return $this->used;
	}

	public function setUsed($used)
	{
		$this->used = $used;
	}

	public static function getUserIdFromCode($code) {
		if (!$code || strlen($code < 5) || !is_numeric(substr($code, 0, 1))) {
			return null;
		}

		$userIdIndex = substr($code, 0, 1);
		$userIdLength = substr($code, 1, 1);
		$userId = substr($code, $userIdIndex + 2, $userIdLength);

		if (!is_numeric($userId)) {
			return null;
		}

		return $userId;
	}
}
