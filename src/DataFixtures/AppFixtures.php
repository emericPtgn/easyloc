<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{

    private $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHaser = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        $user = new User();
        $user->setEmail("user@easylocapi.fr");
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->userPasswordHasher->hasPassword($user, "StrongestAssword"));
        $manager->persist($user);

        $userAdmin = new User();
        $userAdmin->setEmail("useradmin@easylocapi.fr");
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->userPasswordHasher->hasPassword($userAdmin, "ThisMDPistheStrongesta**word"));
        $manager->persist($userAdmin);

    }
}
