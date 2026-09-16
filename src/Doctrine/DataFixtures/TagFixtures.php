<?php

declare(strict_types=1);

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Tag;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class TagFixtures extends Fixture
{
    private const NAMES = [
        'Action',
        'Aventure',
        'RPG',
        'Stratégie',
        'Simulation',
        'Sport',
        'Course',
        'Puzzle',
        'Plateforme',
        'Tir',
        'Horreur',
        'Combat',
        'Multijoueur',
        'Indépendant',
        'Rythme',
    ];

    public function load(ObjectManager $manager): void
    {
        $tags = \array_map(
            static fn (string $name): Tag => (new Tag())->setName($name),
            self::NAMES
        );

        \array_walk($tags, [$manager, 'persist']);

        $manager->flush();
    }
}
