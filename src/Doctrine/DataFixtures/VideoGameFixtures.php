<?php

declare(strict_types=1);

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Ces jeux vidéo sont utilisés par des tests fonctionnels qui vérifient un
     * nombre précis d'avis (ex: ReviewTest). On ne leur ajoute donc pas d'avis
     * "aléatoires" via les fixtures pour ne pas casser ces assertions.
     */
    private const VIDEO_GAME_INDEXES_WITHOUT_REVIEWS = [2, 3, 4, 5, 6, 7, 8];

    /**
     * Ces tags sont réservés aux tests fonctionnels de filtrage (FilterTest) :
     * ils ne sont jamais assignés aléatoirement, mais uniquement de façon
     * déterministe (voir RESERVED_TAG_ASSIGNMENTS) afin de garantir des
     * comptes exacts et reproductibles.
     */
    private const RESERVED_TAG_NAMES = ['Action', 'Aventure', 'RPG'];

    /**
     * Assignation déterministe des tags réservés à certains jeux vidéo (par index).
     * Comptes attendus :
     *  - Action                    : jeux 40, 41, 42, 44  => 4 jeux
     *  - Aventure                  : jeux 42, 43, 44      => 3 jeux
     *  - RPG                       : jeu  44              => 1 jeu
     *  - Action + Aventure         : jeux 42, 44          => 2 jeux
     *  - Action + Aventure + RPG   : jeu  44              => 1 jeu.
     */
    private const RESERVED_TAG_ASSIGNMENTS = [
        40 => ['Action'],
        41 => ['Action'],
        42 => ['Action', 'Aventure'],
        43 => ['Aventure'],
        44 => ['Action', 'Aventure', 'RPG'],
    ];

    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $tags = $manager->getRepository(Tag::class)->findAll();

        // Index des tags par nom pour l'assignation déterministe.
        $tagsByName = [];
        foreach ($tags as $tag) {
            $tagsByName[$tag->getName()] = $tag;
        }

        // Les tags réservés sont exclus du tirage aléatoire afin que leurs
        // comptes restent parfaitement maîtrisés pour les tests.
        $randomTagPool = \array_filter(
            $tags,
            static fn (Tag $tag): bool => !\in_array($tag->getName(), self::RESERVED_TAG_NAMES, true)
        );
        $randomTagPool = array_values($randomTagPool);

        $videoGames = \array_fill_callback(
            0,
            50,
            fn (int $index): VideoGame => (new VideoGame())
            ->setTitle(\sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new \DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(\sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );

        foreach ($videoGames as $index => $videoGame) {
            $selectedTags = $this->faker->randomElements($randomTagPool, $this->faker->numberBetween(1, 4));

            foreach ($selectedTags as $tag) {
                $videoGame->getTags()->add($tag);
            }

            // Assignation déterministe des tags réservés (pour les tests de filtrage).
            foreach (self::RESERVED_TAG_ASSIGNMENTS[$index] ?? [] as $reservedTagName) {
                $videoGame->getTags()->add($tagsByName[$reservedTagName]);
            }
        }

        foreach ($videoGames as $videoGame) {
            $manager->persist($videoGame);
        }

        $manager->flush();

        foreach ($videoGames as $index => $videoGame) {
            if (\in_array($index, self::VIDEO_GAME_INDEXES_WITHOUT_REVIEWS, true)) {
                continue;
            }

            $reviewers = $this->faker->randomElements($users, $this->faker->numberBetween(0, \min(5, count($users))));

            foreach ($reviewers as $reviewer) {
                $review = (new Review())
                    ->setVideoGame($videoGame)
                    ->setUser($reviewer)
                    ->setRating($this->faker->numberBetween(1, 5))
                    ->setComment($this->faker->boolean(70) ? $this->faker->paragraph() : null);

                $manager->persist($review);
                $videoGame->getReviews()->add($review);
            }

            $this->calculateAverageRating->calculateAverage($videoGame);
            $this->countRatingsPerValue->countRatingsPerValue($videoGame);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, TagFixtures::class];
    }
}
