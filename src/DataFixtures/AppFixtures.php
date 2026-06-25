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
    // On injecte le service de hashage 
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}
    public function load(ObjectManager $manager): void
    {

        $faker = Factory::create('fr_FR');

        // Catégorie
        $categoryNames = ['Europe', 'Asie', 'Amérique', 'Afrique','Océanie','Roadtrip'];
        $category = [];

        foreach ($categoryNames as $name) {
            $category = new Category();
            $category->setName($name);
            $category-> setSlug(strtolower(str_replace('','-',$name)));
            $manager->persist($category);
            $categories[] = $category;

        }

        $users= [];

        // creation d'un admin
        $admin =  new User();
        $admin-> setEmail('admin@horizons.com');
        $admin-> setUsername('Admin');
        $admin-> setRoles(['ROLE_ADMIN']);
        $admin-> setCreatedAt(new \DateTimeImmutable());
        $admin-> setPassword(
            $this->passwordHasher->hashPassword($admin,'admin123')
        );
        $manager->persist($admin);
        $users[] = $admin;      

        // 10 utilisateurs
        for ($i=0; $i < 10; $i++) { 
            $user = new User();
            $user-> setEmail($faker->unique()->email());
            $user-> setUsername($faker->userName());
            $user-> setRoles(['ROLE_USER']);
            $user-> setCreatedAt(new \Datetimeimmutable());
            $user-> setPassword(
                $this->passwordHasher->hashPassword($user,'password123')
            );
            $manager->persist($user);
            $users[] =  $user;
        }
        $journeys= [];
        
        for ($i = 0; $i<20; $i++) {
            $journey = new Journey();
            $journey->setTitle($faker->sentence(4));
            $journey->setSlug($faker->unique()->slug(4));
            $journey->setDescription($faker->paragraphs(3, true));
            $journey->setBudget($faker->numberBetween(500,5000));
            $journey->setDuration($faker->numberBetween(3,30));
            $journey->setPublished($faker->boolean(80));
            $journey->setCreatedAt(new \DateTimeImmutable());
            $journey->setCategory($faker->randomElement($categories));
            $journey->setAuthor($faker->randomElement($users));
            $manager->persist($journey);
            $journeys[]= $journey;

            $stepCount = $faker->numberBetween(2,5);
            for ($j=0; $j < $stepCount; $j++){
                $step= new Step();
                $step->setTitle('jour' . ($j+1).'-'.$faker->city());
                $step->setContent($faker->paragraph(2,true));
                $step->setDuration($faker->numberBetween(1, 3));
                $step->setJourney($journey);
                $manager->persist($step);
            }
            $commentCount= $faker->numberBetween(0,5);
            for ($k = 0; $k< $commentCount;$k++){
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
