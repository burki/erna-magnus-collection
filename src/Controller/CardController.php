<?php

// src/Controller/CardController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/card')]
class CardController extends AbstractController
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

    protected function getCardImgDir(): string
    {
        return $this->getPublicDir() . $this->cardDir;
    }

    protected function getCardDataDir(): string
    {
        return $this->getDataDir() . $this->cardDir;
    }

    protected function buildCardImages(): array
    {
        $fnames = [];
        foreach (glob($this->getCardImgDir() . '/*' . $this->cardExt) as $fname) {
            $basename = basename($fname);
            $fnames[] = pathinfo($basename, PATHINFO_FILENAME);
        }

        return $fnames;
    }

    #[Route('/', name: 'card-list')]
    public function list(): Response
    {
        $cardData = $this->buildCardImages();

        return $this->redirectToRoute('card-detail', [
            'card' => $cardData[0],
        ]);
    }

    #[Route('/{card}', name: 'card-detail', requirements: ['card' => '[a-z0-9_-]+'])]
    public function detail(string $card): Response
    {
        $cardImages = $this->buildCardImages();

        $idx = array_search($card, $cardImages);
        if (false === $idx) {
            return $this->redirectToRoute('card-list');
        }

        // Implement the logic to display the card detail
        return $this->render('Card/detail.html.twig', [
            'cardIdx' => $idx,
            'cardImages' => $cardImages,
            'cardData' => json_decode(file_get_contents($this->getCardDataDir() . '/' . $cardImages[$idx] . '.json'), true),
        ]);
    }
}
