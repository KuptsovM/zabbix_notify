<?php

namespace App\Controller;

use App\Service\AlertRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InboxController extends AbstractController
{
    public function __construct(
        private readonly AlertRepository $alerts,
    ) {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    #[Route('/inbox', name: 'inbox', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('inbox.html.twig', [
            'alerts' => $this->alerts->newestFirst(),
        ]);
    }
}
