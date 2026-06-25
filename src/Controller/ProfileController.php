<?php

namespace App\Controller;


use App\Repository\JourneyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function index(JourneyRepository $journeyRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        $published = $journeyRepository->findBy(
            ['author' => $user, 'published' => true],
            ['createdAt' => 'DESC']
        );

        $drafts = $journeyRepository->findBy(
            ['author' => $user, 'published' => false],
            ['createdAt' => 'DESC']
        );

        return $this->render('profile/index.html.twig', [
            'published' => $published,
            'drafts'    => $drafts,
        ]);
    }

    #[Route('/profile/delete', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var \App\Entity\User $user evité une erreur ide */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete-account' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token invalide.');
            return $this->redirectToRoute('app_profile');
        }

        // Déconnexion avant suppression pour éviter une erreur
       
        $security->logout(false);

        $entityManager->remove($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_home');
    }
}