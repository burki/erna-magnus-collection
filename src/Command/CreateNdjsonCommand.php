<?php

// src/Command/CreateNdjsonCommand.php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\CardService;

class CreateNdjsonCommand
{
    #[AsCommand(name: 'app:create-ndjson')]
    public function createNdJson(OutputInterface $output, CardService $cardService): int
    {
        $res = [];

        $normalizedCards = $cardService->getNormalized(false);

        foreach ($normalizedCards as $cardName => $normalizedData) {
            // add ID field to the beginning of the array
            $normalizedData = ['ID' => $cardName] + $normalizedData;
            $res[] = $normalizedData;
        }

        // Convert the array to NDJSON format
        $stream = fopen('php://memory', 'r+');
        \Indykoning\Jsonl\Jsonl::encodeToResource($stream, $res);
        rewind($stream);
        $ndjson = stream_get_contents($stream);
        fclose($stream);

        echo $ndjson;

        return Command::SUCCESS; //  equivalent to returning int(0)
    }
}
