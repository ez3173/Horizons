<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Journey;
use App\Entity\Step;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Comment;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        //  Dossier d'upload
        $uploadDir = __DIR__ . '/../../public/uploads/journeys/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Catégories 
        $categoryNames = ['Europe', 'Asie', 'Amérique', 'Afrique', 'Océanie', 'Roadtrip'];

        $categoryImages = [
            'Europe'   => 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=800&q=80',
            'Asie'     => 'https://images.unsplash.com/photo-1480796927426-f609979314bd?w=800&q=80',
            'Afrique'  => 'https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?w=800&q=80',
            'Roadtrip' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800&q=80',
            'Amérique' => 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=800&q=80',
            'Océanie'  => 'https://images.unsplash.com/photo-1523482580672-f109ba8cb9be?w=800&q=80',
        ];

        $categories = [];
        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug(strtolower(str_replace(' ', '-', $name)));
            $manager->persist($category);
            $categories[] = $category;
        }

        // Utilisateurs
        $users = [];

        $admin = new User();
        $admin->setEmail('admin@horizons.com');
        $admin->setUsername('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setCreatedAt(new \DateTimeImmutable());
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'admin123')
        );
        $manager->persist($admin);
        $users[] = $admin;

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->email());
            $user->setUsername($faker->userName());
            $user->setRoles(['ROLE_USER']);
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, 'password123')
            );
            $manager->persist($user);
            $users[] = $user;
        }

        // Journeys 
        $journeys = [];

        for ($i = 0; $i < 20; $i++) {
            $journey = new Journey();
            $journey->setTitle($faker->sentence(4));
            $journey->setSlug($faker->unique()->slug(4));
            $journey->setDescription($faker->paragraphs(3, true));
            $journey->setBudget($faker->numberBetween(500, 5000));
            $journey->setDuration($faker->numberBetween(3, 30));
            $journey->setPublished($faker->boolean(80));
            $journey->setCreatedAt(new \DateTimeImmutable());

            $selectedCategory = $faker->randomElement($categories);
            $journey->setCategory($selectedCategory);
            $journey->setAuthor($faker->randomElement($users));

            // Télécharge l'image de la catégorie et la sauvegarde localement
            $imageUrl = $categoryImages[$selectedCategory->getName()] ?? null;
            if ($imageUrl) {
                $filename = 'fixture-' . strtolower($selectedCategory->getName()) . '-' . $i . '.jpg';
                $filepath = $uploadDir . $filename;
                if (!file_exists($filepath)) {
                    $imageData = @file_get_contents($imageUrl);
                    if ($imageData) {
                        file_put_contents($filepath, $imageData);
                    }
                }
                $journey->setCoverImage($filename);
            }

            $manager->persist($journey);
            $journeys[] = $journey;

            // Étapes 
            $stepCount = $faker->numberBetween(2, 5);
            for ($j = 0; $j < $stepCount; $j++) {
                $step = new Step();
                $step->setTitle('jour' . ($j + 1) . '-' . $faker->city());
                $step->setContent($faker->paragraph(2, true));
                $step->setDuration($faker->numberBetween(1, 3));
                $step->setJourney($journey);
                $manager->persist($step);
            }

            //  Commentaires 
            $commentCount = $faker->numberBetween(0, 5);
            for ($k = 0; $k < $commentCount; $k++) {
                $comment = new Comment();
                $comment->setContent($faker->paragraph());
                $comment->setCreatedAt(new \DateTimeImmutable());
                $comment->setJourney($journey);
                $comment->setAuthor($faker->randomElement($users));
                $manager->persist($comment);
            }
        }

        $manager->flush();
    }
}