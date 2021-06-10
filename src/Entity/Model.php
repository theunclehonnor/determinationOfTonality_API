<?php

namespace App\Entity;

use App\Repository\ModelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ModelRepository::class)
 */
class Model
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $dataSet;

    /**
     * @ORM\Column(type="string", length=150)
     */
    private $classificator;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=ObjectInQuestion::class, mappedBy="model")
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDataSet(): ?string
    {
        return $this->dataSet;
    }

    public function setDataSet(string $dataSet): self
    {
        $this->dataSet = $dataSet;

        return $this;
    }

    public function getClassificator(): ?string
    {
        return $this->classificator;
    }

    public function setClassificator(string $classificator): self
    {
        $this->classificator = $classificator;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

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
            $objectInQuestion->setModel($this);
        }

        return $this;
    }

    public function removeObjectInQuestion(ObjectInQuestion $objectInQuestion): self
    {
        if ($this->objectInQuestions->removeElement($objectInQuestion)) {
            // set the owning side to null (unless already changed)
            if ($objectInQuestion->getModel() === $this) {
                $objectInQuestion->setModel(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName();
    }
}
