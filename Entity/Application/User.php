<?php

namespace Eckinox\Entity\Application;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Eckinox\Entity\Application\Log;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @ORM\Table(name="ei_users")
 * @ORM\Entity(repositoryClass="Eckinox\Repository\Application\UserRepository")
 */
class User implements AdvancedUserInterface, \Serializable
{
    use \Eckinox\Library\Entity\baseEntity;
    use \Eckinox\Library\Entity\loggableEntity;

    /**
     * @ORM\Column(type="string", length=125, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(name="full_name", type="string", length=125)
     */
    private $fullName;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=125, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $privileges;

    /**
     * @ORM\Column(type="json")
     */
    private $variables;

    /**
     * @ORM\OneToMany(targetEntity="Eckinox\Entity\Application\Log", mappedBy="user")
     */
    private $logs;

    /**
     * @ORM\OneToMany(targetEntity="Eckinox\Entity\Application\Email", mappedBy="user")
     */
    private $emails;

    /**
     * @ORM\Column(type="string", length=125, nullable=true)
     */
    private $function;

    /**
     * @ORM\Column(type="string", length=125, nullable=true)
     */
    private $department;

    /**
     * @ORM\Column(name="home_phone", type="string", length=15, nullable=true)
     */
    private $homePhone;

    /**
     * @ORM\Column(name="mobile_phone", type="string", length=15, nullable=true)
     */
    private $mobilePhone;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     */
    private $isActive;

    /**
     * @ORM\Column(type="string", length=125)
     */
    private $status;

    public function __construct()
    {
        $this->isActive = true;
        $this->status = 'active';
        $this->logs = new ArrayCollection();
        $this->privileges = [];
        $this->variables = [];
        $this->setCreatedAt();
        $this->init();
    }

    /**
     * @ORM\PostLoad
     */
    public function init() {
        $this->addIgnoredAttributes(array('logs', 'emails'));
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    public function getPrivileges()
    {
        return $this->privileges;
    }

    public function setPrivileges($privileges)
    {
        $this->privileges = $privileges;

        return $this;
    }

    public function getRoles()
    {
        return $this->privileges;
    }

    public function eraseCredentials()
    {
    }

    /** @see \Serializable::serialize() */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
        ));
    }

    /** @see \Serializable::unserialize() */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
        ) = unserialize($serialized);

        return $this;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getLogs() {
        return $this->logs;
    }

    public function getEmails() {
        return $this->emails;
    }

    public function getFunction(){
		return $this->function;
	}

	public function setFunction($function){
		$this->function = $function;

        return $this;
	}

	public function getDepartment(){
		return $this->department;
	}

	public function setDepartment($department){
		$this->department = $department;

        return $this;
	}

	public function getHomePhone(){
		return $this->homePhone;
	}

	public function setHomePhone($homePhone){
		$this->homePhone = $homePhone;

        return $this;
	}

	public function getMobilePhone(){
		return $this->mobilePhone;
	}

	public function setMobilePhone($mobilePhone){
		$this->mobilePhone = $mobilePhone;

        return $this;
	}

    public function getIsActive() {
        return $this->isActive;
    }

    public function setIsActive($bool) {
        $this->isActive = $bool;
        $this->status = $bool ? 'active' : 'inactive';

        return $this;
    }

    public function deactivate() {
        $this->isActive = false;
        $this->status = 'inactive';

        return $this;
    }

    public function activate() {
        $this->isActive = true;
        $this->status = 'active';

        return $this;
    }

    public function delete() {
        $this->isActive = false;
        $this->status = 'deleted';

        return $this;
    }

    public function hasPrivilege($privilege) {
        return in_array($privilege, $this->getPrivileges());
    }

    public function getVariables()
    {
        return $this->variables;
    }

    public function setVariables($set)
    {
        $this->variables = $set;

        return $this;
    }

    public function setVar($name, $value) {
        return $this->variables[$name] = $value;

        return $this;
    }

    public function getVar($name) {
        return $this->variables[$name] ?? null;
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return Boolean true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired() {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return Boolean true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked() {
        return true;
    }
    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return Boolean true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired() {
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return Boolean true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled() {
        return $this->status === 'active';
    }
}
