<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;

class ReviewDTO
{
    /**
     * @Serializer\Type("string")
     */
    private $author;

    /**
     * @Serializer\Type("string")
     */
    private $pluses;

    /**
     * @Serializer\Type("string")
     */
    private $minuses;

    /**
     * @Serializer\Type("string")
     */
    private $description;

    /**
     * @Serializer\Type("float")
     */
    private $rating;

    /**
     * @Serializer\Type("string")
     */
    private $date;

    /**
     * @Serializer\Type("string")
     */
    private $review;

    /**
     * @Serializer\Type("integer")
     */
    private $sentiment;

    public function getPluses()
    {
        return $this->pluses;
    }

    public function setPluses(?string $pluses): void
    {
        $this->pluses = $pluses;
    }

    public function getMinuses()
    {
        return $this->minuses;
    }

    public function setMinuses(?string $minuses): void
    {
        $this->minuses = $minuses;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getRating()
    {
        return $this->rating;
    }

    public function setRating(?float $rating): void
    {
        $this->rating = $rating;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate(?string $date): void
    {
        $this->date = $date;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(?string $author): void
    {
        $this->author = $author;
    }

    public function getReview()
    {
        return $this->review;
    }

    public function setReview(?string $review): void
    {
        $this->review = $review;
    }

    public function getSentiment()
    {
        return $this->sentiment;
    }

    public function setSentiment(?int $sentiment): void
    {
        $this->sentiment = $sentiment;
    }
}
