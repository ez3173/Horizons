<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\JourneyRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

 class JourneyController extends AbstractController
{
    // principal route  with all the carnet
    #[Route('/journeys', name: 'app_journey_index')]
    public function index(
        JourneyRepository $journeyRepository,
        CategoryRepository $categoryRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        $search= $request->query->get('search');
        $categoryId= $request->query->get('category');
    
        $query = $journeyRepository->findByFilters($search, $categoryId);

        $journeys = $paginator->paginate(
            $query,
            $request->query->getInt('page',1),
            6
        );
        // we get all the categories
        $categories = $categoryRepository->findAll();

        return $this->render('journey/index.html.twig',[
        'journeys' => $journeys,
        'categories'=> $categories,
        'searcg'=> $search,
        'currentCategory'=> $categoryId
        ]);
    }
    #[Route('/journey/{slug}', name: 'app_journey_show')]
    public function show(string $slug, JourneyRepository $journeyRepository): Response
    {

        $journey = $journeyRepository->findOneBy(['slug' => $slug]);

        if(!$journey) {
            throw $this->createNotFoundException('Ce carnetn\'existe pas');
        }
        return $this->render('journey/show.html.twig',[
            'journey' => $journey,
        ]);
    }
}


