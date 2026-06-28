<?php

namespace App\Controller;

use App\Entity\Journey;
use App\Entity\Comment;
use App\Form\JourneyType;
use App\Repository\CategoryRepository;
use App\Repository\JourneyRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class JourneyController extends AbstractController
{
    #[Route('/journeys', name: 'app_journey_index')]
    public function index(
        JourneyRepository $journeyRepository,
        CategoryRepository $categoryRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $search = $request->query->get('search');
        $categoryIdRaw = $request->query->get('category');
        $categoryId = $categoryIdRaw ? (int) $categoryIdRaw : null;

        $query = $journeyRepository->findByFilters($search, $categoryId);

        $journeys = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            6
        );

        $categories = $categoryRepository->findAll();

        return $this->render('journey/index.html.twig', [
            'journeys'        => $journeys,
            'categories'      => $categories,
            'search'          => $search,
            'currentCategory' => $categoryId,
        ]);
    }

    #[Route('/journey/new', name: 'app_journey_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $journey = new Journey();
        $form = $this->createForm(JourneyType::class, $journey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Upload de l'image de couverture
            $coverImageFile = $form->get('coverImageFile')->getData();
            if ($coverImageFile) {
                $safeFilename = $slugger->slug(pathinfo($coverImageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $coverImageFile->guessExtension();
                try {
                    $coverImageFile->move($this->getParameter('journeys_directory'), $newFilename);
                    $journey->setCoverImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }

            $journey->setSlug((new Slugify())->slugify($journey->getTitle()));
            $journey->setAuthor($this->getUser());
            $journey->setCreatedAt(new \DateTimeImmutable());

            // Publié ou brouillon selon le bouton cliqué
            $published = $request->request->get('publish_action') === 'publish';
            $journey->setPublished($published);

            $entityManager->persist($journey);
            $entityManager->flush();

            if (!$published) {
                $this->addFlash('success', 'Carnet enregistré en brouillon.');
            }

            return $this->redirectToRoute('app_journey_show', ['slug' => $journey->getSlug()]);
        }

        return $this->render('journey/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/journey/{slug}/edit', name: 'app_journey_edit')]
    public function edit(
        string $slug,
        Request $request,
        JourneyRepository $journeyRepository,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        $journey = $journeyRepository->findOneBy(['slug' => $slug]);

        if (!$journey) {
            throw $this->createNotFoundException('Ce carnet n\'existe pas');
        }

        // Seul l'auteur ou un admin peut modifier
        if ($journey->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de modifier ce carnet');
        }

        $form = $this->createForm(JourneyType::class, $journey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coverImageFile = $form->get('coverImageFile')->getData();
            if ($coverImageFile) {
                $safeFilename = $slugger->slug(pathinfo($coverImageFile->getClientOriginalName(), PATHINFO_FILENAME));
                $newFilename  = $safeFilename . '-' . uniqid() . '.' . $coverImageFile->guessExtension();
                try {
                    $coverImageFile->move($this->getParameter('journeys_directory'), $newFilename);
                    $journey->setCoverImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }

            // Régénération du slug si le titre a changé
            $journey->setSlug((new Slugify())->slugify($journey->getTitle()));
            $entityManager->flush();

            $this->addFlash('success', 'Votre carnet a été modifié avec succès !');

            return $this->redirectToRoute('app_journey_show', ['slug' => $journey->getSlug()]);
        }

        return $this->render('journey/edit.html.twig', [
            'form'    => $form,
            'journey' => $journey,
        ]);
    }

    #[Route('/journey/{slug}/delete', name: 'app_journey_delete', methods: ['POST'])]
    public function delete(
        string $slug,
        Request $request,
        JourneyRepository $journeyRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $journey = $journeyRepository->findOneBy(['slug' => $slug]);

        if (!$journey) {
            throw $this->createNotFoundException('Ce carnet n\'existe pas');
        }

        if ($journey->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer ce carnet');
        }

        if ($this->isCsrfTokenValid('delete' . $journey->getId(), $request->request->get('_token'))) {
            $entityManager->remove($journey);
            $entityManager->flush();
            $this->addFlash('success', 'Le carnet a été supprimé.');
        }

        return $this->redirectToRoute('app_journey_my');
    }

    #[Route('/my-journeys', name: 'app_journey_my')]
    public function myJourneys(JourneyRepository $journeyRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $journeys = $journeyRepository->findBy(
            ['author' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('journey/my_journeys.html.twig', [
            'journeys' => $journeys,
        ]);
    }

    #[Route('/journey/{slug}', name: 'app_journey_show')]
    public function show(string $slug, JourneyRepository $journeyRepository): Response
    {
        $journey = $journeyRepository->findOneBy(['slug' => $slug]);

        if (!$journey) {
            throw $this->createNotFoundException('Ce carnet n\'existe pas');
        }

        return $this->render('journey/show.html.twig', [
            'journey' => $journey,
        ]);
    }

    #[Route('/journey/{slug}/comment', name: 'app_journey_comment', methods: ['POST'])]
    public function addComment(
        string $slug,
        Request $request,
        JourneyRepository $journeyRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $journey = $journeyRepository->findOneBy(['slug' => $slug]);

        if (!$journey) {
            throw $this->createNotFoundException('Ce carnet n\'existe pas');
        }

        $content = $request->request->get('content');

        if ($content && trim($content) !== '') {
            $comment = new Comment();
            $comment->setContent($content);
            $comment->setAuthor($this->getUser());
            $comment->setJourney($journey);
            $comment->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($comment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_journey_show', ['slug' => $journey->getSlug()]);
    }
    #[Route('/journey/{slug}/publish', name: 'app_journey_publish', methods: ['POST'])]
    public function publish(
        string $slug,
        Request $request,
        JourneyRepository $journeyRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $journey = $journeyRepository->findOneBy(['slug' => $slug]);

        if (!$journey) {
            throw $this->createNotFoundException();
        }

        if ($journey->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('publish' . $journey->getId(), $request->request->get('_token'))) {
            $journey->setPublished(true);
            $entityManager->flush();
            $this->addFlash('success', 'Votre carnet est maintenant publié !');
        }

        return $this->redirectToRoute('app_journey_show', ['slug' => $journey->getSlug()]);
    }
}