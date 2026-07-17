<?php

// src/Controller/CardController.php

namespace App\Controller;

use App\Service\CardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/card')]
class CardController extends AbstractController
{
    public function __construct(protected CardService $cardService) {}

    #[Route('/', name: 'card-index')]
    public function list(): Response
    {
        $cardNames = $this->cardService->buildCardNames();

        $entries = [];
        foreach ($cardNames as $cardName) {
            $data = $this->cardService->getData($cardName);
            if (false !== $data) {
                $card = \App\Dto\Card::createFromJson($data);

                $name = $card->getFullname();
                if ('' === $name) {
                    continue;
                }

                $entries[] = [
                    'name' => $name,
                    'card' => $cardName,
                ];
            }
        }

        return $this->render('Card/index.html.twig', [
            'entries' => $entries,
        ]);
    }

    #[Route('/{card}', name: 'card-detail', requirements: ['card' => '[a-z0-9_-]+'])]
    public function detail(string $card): Response
    {
        $cardNames = $this->cardService->buildCardNames();

        $idx = array_search($card, $cardNames);
        if (false === $idx) {
            return $this->redirectToRoute('card-index');
        }

        $data = $this->cardService->getData($cardNames[$idx]);
        if (false === $data) {
            return $this->redirectToRoute('card-index');
        }

        $normalized = $this->cardService->getNormalizedData($cardNames[$idx]); // currently always false
        if (false === $normalized) {
            $normalized = $this->cardService->normalizeData($data);
        }

        // Implement the logic to display the card detail
        return $this->render('Card/detail.html.twig', [
            'cardIdx' => $idx,
            'cardNames' => $cardNames,
            'card' => \App\Dto\Card::createFromJson($normalized),
            'cardData' => [
                'orig' => $data, // Original data from JSON
                'normalized' => $normalized, // Placeholder for normalized data
            ],
        ]);
    }

    /*
    #[Route('/{card}/edit', name: 'card-edit', requirements: ['card' => '[a-z0-9_-]+'])]
    public function edit(string $card): Response
    {
        $cardNames = $this->cardService->buildCardNames();

        $idx = array_search($card, $cardNames);
        if (false === $idx) {
            return $this->redirectToRoute('card-index');
        }

        // Implement the logic to edit the card detail
        return $this->render('Card/detail.html.twig', [
            'cardIdx' => $idx,
            'cardNames' => $cardNames,
            'cardData' => $this->cardService->getData($cardNames[$idx]),
        ]);
    }
    */
}
