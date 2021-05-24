<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @OA\Schema(
 *     title="UserDTO",
 *     description="UserDTO"
 * )
 *
 * Class UserDTO
 */
class UserDTO
{
    /**
     * @OA\Property(
     *     type="string",
     *     title="Username",
     *     description="Username"
     * )
     * @Serializer\Type("string")
     */
    private $username;

    /**
     * @OA\Property(
     *     format="email",
     *     title="Email",
     *     description="Email"
     * )
     * @Serializer\Type("string")
     * @Assert\Email(message="Email address {{ value }} is not valid")
     */
    private $email;

    /**
     * @OA\Property(
     *     type="string",
     *     title="Password",
     *     description="Password"
     * )
     * @Serializer\Type("string")
     * @Assert\Length(
     *     min="6",
     *     minMessage="Your password must be at least {{ limit }} characters",
     * )
     * @Assert\NotBlank()
     */
    private $password;

    /**
     * @OA\Property(
     *     type="array",
     *     @OA\Items(
     *         type="string"
     *     ),
     *     title="Roles",
     *     description="Roles"
     * )
     * @Serializer\Type("array")
     */
    private $roles = [];

    /**
     * @OA\Property(
     *     type="string",
     *     title="Surname",
     *     description="Surname"
     * )
     * @Serializer\Type("string")
     */
    private $surname;

    /**
     * @OA\Property(
     *     type="string",
     *     title="Name",
     *     description="Name"
     * )
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @OA\Property(
     *     type="string",
     *     title="Patronymic",
     *     description="Username"
     * )
     * @Serializer\Type("string")
     */
    private $patronymic;

    /**
     * @OA\Property(
     *     type="string",
     *     title="CreatedAt",
     *     description="CreatedAt"
     * )
     * @Serializer\Type("string")
     */
    private $createdAt;

    /**
     * @OA\Property(
     *     type="string",
     *     title="NameCompany",
     *     description="Photo"
     * )
     * @Serializer\Type("string")
     */
    private $nameCompany;

    public function getEmail()
    {
        return $this->email;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function setPassword($password): void
    {
        $this->password = $password;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
        $this->email = $username;
    }

    public function getSurname()
    {
        return $this->surname;
    }

    public function setSurname($surname): void
    {
        $this->surname = $surname;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getPatronymic()
    {
        return $this->patronymic;
    }

    public function setPatronymic($patronymic): void
    {
        $this->patronymic = $patronymic;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getNameCompany()
    {
        return $this->nameCompany;
    }

    public function setNameCompany($nameCompany): void
    {
        $this->nameCompany = $nameCompany;
    }
}
