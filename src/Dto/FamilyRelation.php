<?php

// src/Dto/FamilyRelation.php

namespace App\Dto;

class FamilyRelation implements \JsonSerializable
{
    private ?string $role = null;
    private ?string $name = null;

    // uses a private constructor to avoid direct instantiation
    private function __construct() {}

    public static function createFromJson(mixed $value): ?self
    {
        if (!is_array($value)) {
            throw new \InvalidArgumentException('Invalid JSON data for FamilyRelation');
        }

        $familyRelation = new self();

        $initialized = false;
        if (array_key_exists('role', $value)) {
            $familyRelation->role = $value['role'];
            $initialized = true;
        }

        if (array_key_exists('name', $value)) {
            $familyRelation->name = $value['name'];
            $initialized = true;
        }

        return $initialized ? $familyRelation : null;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function jsonSerialize(): ?array
    {
        $ret = [];

        if (null !== $this->role) {
            $ret['role'] = $this->role;
        }

        if (null !== $this->name) {
            $ret['name'] = $this->name;
        }

        return !empty($ret) ? $ret : null;
    }
}
