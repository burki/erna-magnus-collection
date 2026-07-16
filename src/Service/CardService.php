<?php

// src/Service/CardService.php

namespace App\Service;

class CardService
{
    protected string $cardDir = '/a-z';
    protected string $cardExt = '.jpg';

    public function __construct(protected string $projectDir) {}

    protected function getPublicDir(): string
    {
        return $this->projectDir . '/public';
    }

    protected function getDataDir(): string
    {
        return $this->projectDir . '/data';
    }

    public function getCardImgDir(): string
    {
        return $this->getPublicDir() . $this->cardDir;
    }

    public function getCardDataDir(): string
    {
        return $this->getDataDir() . $this->cardDir;
    }

    public function buildCardNames(): array
    {
        $fnames = [];
        foreach (glob($this->getCardImgDir() . '/*' . $this->cardExt) as $fname) {
            $basename = basename($fname);
            $fnames[] = pathinfo($basename, PATHINFO_FILENAME);
        }

        return $fnames;
    }

    public function getData(string $card): array|false
    {
        $dataFile = $this->getCardDataDir() . '/' . $card . '.json';
        if (!file_exists($dataFile)) {
            return false;
        }

        $data = json_decode(file_get_contents($dataFile), true);
        if (null === $data) {
            return false;
        }

        return $data;
    }
}
