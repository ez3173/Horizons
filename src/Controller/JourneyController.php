<?php

namespace App\Controller;

use App\Entity\Journey;
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
        $categoryIdRaw = $request->query->get('category');
        $categoryId = $categoryIdRaw ? (int) $categoryIdRaw : null;

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
        'search'=> $search,
        'currentCategory'=> $categoryId
        ]);
    }
    // Créer un nouveau carnet — réservé aux utilisateurs connectés
    #[Route('/journey/new', name: 'app_journey_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response{
        $this->denyAccessUnlessGranted('ROLE_USER');

        $journey= new Journey();
        $form= $this->createForm(JourneyType::class ,$journey);
        $form->handleRequest($request);

        if ($form->isSubmitted()&& $form->isValid()) {
            $coverImageFile=$form->get('coverImageFile')->getData();

            if($coverImageFile){
                $originalFilename = pathinfo($coverImageFile->getClientOriginalName(),PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $coverImageFile->guessExtension();

                try{
                    $coverImageFile->move(
                        $this->getParameter('journeys_directory'),
                        $newFilename
                    );
                    $journey->setCoverImage($newFilename);
                } catch(FileException $e) {
                    $this->addFlash('error','Erreur lors de l\'upload de l\'image');
                }
            
            }

            $slugify = new Slugify();
            $journey->setSlug($slugify->slugify($journey->getTitle()));

            $journey->setAuthor($this->getUser());
            $journey->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($journey);
            $entityManager->flush();

            $this->addFlash('success', 'Votre carnet a été créé avec succés !');

            return $this->redirectToRoute('app_journey_show', ['slug' => $journey->getSlug()]);

        }
         return $this->render('journey/new.html.twig', [
        'form' => $form,
    ]);
    }
    // Modifier un carnet existant — réservé à l'auteur du carnet
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

        // Sécurité : seul l'auteur (ou un admin) peut modifier ce carnet
        if ($journey->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de modifier ce carnet');
        }

        $form = $this->createForm(JourneyType::class, $journey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $coverImageFile = $form->get('coverImageFile')->getData();

            if ($coverImageFile) {
                $originalFilename = pathinfo($coverImageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $coverImageFile->guessExtension();

                try {
                    $coverImageFile->move(
                        $this->getParameter('journeys_directory'),
                        $newFilename
                    );
                    $journey->setCoverImage($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image');
                }
            }

            // On régénère le slug au cas où le titre a changé
            $slugify = new Slugify();
            $journey->setSlug($slugify->slugify($journey->getTitle()));

            $entityManager->flush();

            $this->addFlash('success', 'Votre carnet a été modifié avec succès !');

            return $this->redirectToRoute('app_journey_show', ['slug' => $journey->getSlug()]);
        }

        return $this->render('journey/edit.html.twig', [
            'form' => $form,
            'journey' => $journey,
        ]);
    }
    // Supprimer un carnet — réservé à l'auteur ou un admin
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

        // Sécurité : seul l'auteur ou un admin peut supprimer
        if ($journey->getAuthor() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer ce carnet');
        }

        // Protection CSRF — vérifie que la demande vient bien de notre formulaire
        if ($this->isCsrfTokenValid('delete' . $journey->getId(), $request->request->get('_token'))) {
            $entityManager->remove($journey);
            $entityManager->flush();
            $this->addFlash('success', 'Le carnet a été supprimé.');
        }

        return $this->redirectToRoute('app_journey_my');
    }
    
    // Liste des carnets de l'utilisateur connecté (publiés ET brouillons)
    #[Route('/my-journeys', name: 'app_journey_my')]
    public function myJourneys(JourneyRepository $journeyRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // On récupère tous les carnets de l'utilisateur connecté, peu importe le statut
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

        if(!$journey) {
            throw $this->createNotFoundException('Ce carnetn\'existe pas');
        }
        return $this->render('journey/show.html.twig',[
            'journey' => $journey,
        ]);
    }
}


