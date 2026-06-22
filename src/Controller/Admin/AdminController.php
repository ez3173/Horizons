<?php

namespace App\Controller\Admin;

use App\Repository\CommentRepository;
use App\Repository\JourneyRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{

    #[Route('', name: 'app_admin_dashboard')]
    public function dashboard(
        UserRepository $userRepository,
        JourneyRepository $journeyRepository,
        CommentRepository $commentRepository
    ): Response {

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $stats = [
            'users'    => count($userRepository->findAll()),
            'journeys' => count($journeyRepository->findAll()),
            'comments' => count($commentRepository->findAll()),
            'published' => count($journeyRepository->findBy(['published' => true])),
        ];

        $latestJourneys = $journeyRepository->findBy([], ['createdAt' => 'DESC'], 5);


        $latestUsers = $userRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats'          => $stats,
            'latestJourneys' => $latestJourneys,
            'latestUsers'    => $latestUsers,
        ]);
    }


    #[Route('/users', name: 'app_admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/journeys', name: 'app_admin_journeys')]
    public function journeys(JourneyRepository $journeyRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $journeys = $journeyRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('admin/journeys.html.twig', [
            'journeys' => $journeys,
        ]);
    }
}