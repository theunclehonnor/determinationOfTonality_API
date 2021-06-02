<?php

namespace App\Entity;

use App\Repository\ReportRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ReportRepository::class)
 */
class Report
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $file;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="reports")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userApi;

    /**
     * @ORM\OneToOne(targetEntity=ObjectInQuestion::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $objectInQuestion;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFile(): ?string
    {
        return $this->file;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;

        return $this;
    }

    public function getUserApi(): ?User
    {
        return $this->userApi;
    }

    public function setUserApi(?User $userApi): self
    {
        $this->userApi = $userApi;

        return $this;
    }

    public function getObjectInQuestion(): ?ObjectInQuestion
    {
        return $this->objectInQuestion;
    }

    public function setObjectInQuestion(ObjectInQuestion $objectInQuestion): self
    {
        $this->objectInQuestion = $objectInQuestion;

        return $this;
    }
}
