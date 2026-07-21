<?php

// src/Dto/LifeEvent.php

namespace App\Dto;

class LifeEvent implements \JsonSerializable
{
    private ?string $place = null;
    private ?string $date = null;

    // uses a private constructor to avoid direct instantiation
    private function __construct() {}

    public static function createFromJson(mixed $value): ?self
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Invalid JSON data for LifeEvent');
        }

        $lifeEvent = new self();

        $initialized = false;
        if (array_key_exists('place', $value)) {
            $lifeEvent->place = $value['place'];
            $initialized = true;
        }
        if (array_key_exists('date', $value)) {
            $lifeEvent->date = $value['date'];
            $initialized = true;
        }

        return $initialized ? $lifeEvent : null;
    }

    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function jsonSerialize(): ?array
    {
        $ret = [];
        if (null !== $this->place) {
            $ret['place'] = $this->place;
        }
        if (null !== $this->date) {
            $ret['date'] = $this->date;
        }

        return !empty($ret) ? $ret : null;
    }
}
