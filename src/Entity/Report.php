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
     * @ORM\Column(type="string", length=255)
     */
    private $file;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="reports")
     * @ORM\JoinColumn(nullable=false)
     */
    private $userApi;

    /**
     * @ORM\OneToMany(targetEntity=ObjectInQuestion::class, mappedBy="report")
     */
    private $objectInQuestions;

    public function __construct()
    {
        $this->objectInQuestions = new ArrayCollection();
    }


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

    /**
     * @return Collection|ObjectInQuestion[]
     */
    public function getObjectInQuestions(): Collection
    {
        return $this->objectInQuestions;
    }

    public function addObjectInQuestion(ObjectInQuestion $objectInQuestion): self
    {
        if (!$this->objectInQuestions->contains($objectInQuestion)) {
            $this->objectInQuestions[] = $objectInQuestion;
            $objectInQuestion->setReport($this);
        }

        return $this;
    }

    public function removeObjectInQuestion(ObjectInQuestion $objectInQuestion): self
    {
        if ($this->objectInQuestions->removeElement($objectInQuestion)) {
            // set the owning side to null (unless already changed)
            if ($objectInQuestion->getReport() === $this) {
                $objectInQuestion->setReport(null);
            }
        }

        return $this;
    }
}
