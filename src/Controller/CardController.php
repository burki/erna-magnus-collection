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
        foreach ($cardNames as $name) {
            $data = $this->cardService->getData($name);
            if (false !== $data && array_key_exists('name', $data) && is_array($data['name'])) {
                $nameParts = [];
                foreach (['family', 'given'] as $part) {
                    if (array_key_exists($part, $data['name']) && !empty($data['name'][$part])) {
                        $nameParts[] = $data['name'][$part];
                    }
                }

                if (0 === count($nameParts)) {
                    continue;
                }

                $entries[] = [
                    'name' => join(', ', $nameParts),
                    'card' => $name,
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

        // Implement the logic to display the card detail
        return $this->render('Card/detail.html.twig', [
            'cardIdx' => $idx,
            'cardNames' => $cardNames,
            'cardData' => $this->cardService->getData($cardNames[$idx]),
        ]);
    }
}
