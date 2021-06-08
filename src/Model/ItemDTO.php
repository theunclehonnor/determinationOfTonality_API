<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class ItemDTO
{
    /**
     * @Serializer\Type("string")
     */
    private $title;

    /**
     * @Serializer\Type("integer")
     */
    private $countReviews;

    /**
     * @Serializer\Type("string")
     */
    private $image;

    /**
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @Serializer\Type("string")
     */
    private $tonality;

    /**
     * @Serializer\Type("float")
     */
    private $accuracy;

    /**
     * @Serializer\Type("string")
     */
    private $itervalRating;

    /**
     * @var ModelDTO
     * @Serializer\Type("App\Model\ModelDTO")
     */
    private $model;

    /**
     * @var ReviewDTO[]
     * @Serializer\Type("array<App\Model\ReviewDTO>")
     */
    private $reviews;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getCountReviews()
    {
        return $this->countReviews;
    }

    public function setCountReviews(?int $countReviews): void
    {
        $this->countReviews = $countReviews;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getAccuracy()
    {
        return $this->accuracy;
    }

    public function setAccuracy(?float $accuracy): void
    {
        $this->accuracy = $accuracy;
    }

    /**
     * @return ReviewDTO[]
     */
    public function getReviews(): array
    {
        return $this->reviews;
    }

    /**
     * @param ReviewDTO[] $reviews
     */
    public function setReviews(array $reviews): void
    {
        $this->reviews = $reviews;
    }

    public function getModel(): ModelDTO
    {
        return $this->model;
    }

    public function setModel(ModelDTO $model): void
    {
        $this->model = $model;
    }

    public function getTonality()
    {
        return $this->tonality;
    }

    public function setTonality(?string $tonality): void
    {
        $this->tonality = $tonality;
    }

    public function getItervalRating()
    {
        return $this->itervalRating;
    }

    public function setItervalRating($itervalRating): void
    {
        $this->itervalRating = $itervalRating;
    }
}
