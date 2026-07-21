<?php

// src/Dto/Card.php

namespace App\Dto;

class Card implements \JsonSerializable
{
    private ?string $family = null;
    private ?string $given = null;
    private ?string $title = null;
    private ?LifeEvent $birth = null;
    private ?LifeEvent $death = null;
    private ?string $gender = null;
    private ?string $religion = null;
    private ?string $fieldOfActivity = null;
    private ?string $profession = null;
    private array $familyRelations = [];
    private array $details = [];
    private array $publications = [];
    private array $literature = [];

    // uses a private constructor to avoid direct instantiation
    private function __construct() {}

    public static function createFromJson(mixed $value): self
    {
        $card = new self();

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Invalid JSON data for Card');
        }

        if (array_key_exists('name', $value)) {
            $nameParts = $value['name'];
            if (is_array($nameParts)) {
                $card->family = $nameParts['family'] ?? null;
                $card->given = $nameParts['given'] ?? null;
                $card->title = $nameParts['title'] ?? $nameParts['academic_title'] ?? null;
            } elseif (is_string($nameParts) && '' !== trim($nameParts)) {
                $card->family = trim($nameParts);
            }
        }

        if (array_key_exists('gender', $value)) {
            if (is_string($value['gender']) && '' !== trim($value['gender'])) {
                $card->gender = trim($value['gender']);
            }
        }

        if (array_key_exists('religion', $value)) {
            if (is_string($value['religion']) && '' !== trim($value['religion'])) {
                $card->religion = trim($value['religion']);
            }
        }

        if (array_key_exists('fieldOfActivity', $value)) {
            if (is_string($value['fieldOfActivity']) && '' !== trim($value['fieldOfActivity'])) {
                $card->fieldOfActivity = trim($value['fieldOfActivity']);
            }
        }

        if (array_key_exists('profession', $value)) {
            if (is_string($value['profession']) && '' !== trim($value['profession'])) {
                $card->profession = trim($value['profession']);
            }
        }

        foreach (['birth', 'death'] as $event) {
            if (array_key_exists($event, $value)) {
                $card->$event = LifeEvent::createFromJson($value[$event]);
            }
        }

        if (array_key_exists('relations', $value)) {
            foreach ($value['relations'] as $relationData) {
                $relation = FamilyRelation::createFromJson($relationData);
                if (null !== $relation) {
                    $card->familyRelations[] = $relation;
                }
            }
        }

        if (array_key_exists('details', $value)) {
            foreach ($value['details'] as $line) {
                if (!is_string($line)) {
                    continue;
                    dd($line); // TODO: flatten
                }

                if ('' !== trim($line)) {
                    $card->details[] = trim($line);
                }
            }
        }

        foreach (['publications', 'literature'] as $key) {
            if (array_key_exists($key, $value) && !empty($value[$key])) {
                $card->{$key} = $value[$key];
            }
        }

        return $card;
    }

    public function getFullname($givenFirst = false): string
    {
        $nameParts = [];
        foreach (['family', 'given'] as $part) {
            if (null !== $this->$part && '' !== trim($this->$part)) {
                $nameParts[$part] = $this->$part;
            }
        }

        if (empty($nameParts)) {
            return '';
        }

        return $givenFirst
            ? implode(' ', array_reverse($nameParts))
            : implode(', ', $nameParts);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function getReligion(): ?string
    {
        return $this->religion;
    }

    public function getFieldOfActivity(): ?string
    {
        return $this->fieldOfActivity;
    }

    public function getBirth(): ?LifeEvent
    {
        return $this->birth;
    }

    public function getDeath(): ?LifeEvent
    {
        return $this->death;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function getFamilyRelations(): array
    {
        return $this->familyRelations;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function getPublications(): array
    {
        return $this->publications;
    }

    public function getLiterature(): array
    {
        return $this->literature;
    }

    public function jsonSerialize(): array
    {
        $ret = [];

        $nameParts = [];
        foreach (['family', 'given', 'title'] as $part) {
            if (null !== $this->$part && '' !== trim($this->$part)) {
                $nameParts[$part] = $this->$part;
            }
        }

        if (!empty($nameParts)) {
            $ret['name'] = $nameParts;
        }

        foreach (['birth', 'death'] as $event) {
            if (null !== $this->$event) {
                $serializedEvent = $this->$event->jsonSerialize();
                if (null !== $serializedEvent) {
                    $ret[$event] = $serializedEvent;
                }
            }
        }

        if (null !== $this->fieldOfActivity && '' !== trim($this->fieldOfActivity)) {
            $ret['fieldOfActivity'] = $this->fieldOfActivity;
        }

        if (null !== $this->gender && '' !== trim($this->gender)) {
            $ret['gender'] = $this->gender;
        }

        if (null !== $this->religion && '' !== trim($this->religion)) {
            $ret['religion'] = $this->religion;
        }

        if (null !== $this->profession && '' !== trim($this->profession)) {
            $ret['profession'] = $this->profession;
        }

        if (!empty($this->familyRelations)) {
            $ret['relations'] = array_map(fn($relation) => $relation->jsonSerialize(), $this->familyRelations);
        }

        if (!empty($this->details)) {
            $ret['details'] = $this->details;
        }

        if (!empty($this->publications)) {
            $ret['publications'] = $this->publications;
        }

        if (!empty($this->literature)) {
            $ret['literature'] = $this->literature;
        }

        return $ret;
    }
}
