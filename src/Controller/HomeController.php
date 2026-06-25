<?php

namespace App\Controller;

use App\Repository\JourneyRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        JourneyRepository $journeyRepository,
        CategoryRepository $categoryRepository
    ): Response {
        // 3 derniers carnets publiés pour la section "à la une"
        $featuredJourneys = $journeyRepository->findBy(
            ['published' => true],
            ['createdAt' => 'DESC'],
            3
        );

        $categories = $categoryRepository->findAll();

        return $this->render('home/index.html.twig', [
            'featuredJourneys' => $featuredJourneys,
            'categories'       => $categories,
        ]);
    }
}