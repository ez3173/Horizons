<?php

namespace App\Controller;

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
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
       $query = $journeyRepository->findBy(
        ['published'=>true],
        ['createdAt'=> 'DESC']
       );


       $journeys = $paginator->paginate(
        $query,
        $request->query->getInt('page',1),
        6
       );
       return $this->render('journey/index.html.twig',[
        'journeys' => $journeys,
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


