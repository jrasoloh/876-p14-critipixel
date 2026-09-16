<?php

namespace App\Doctrine\DataFixtures;

use App\Model\Entity\Review;
use App\Model\Entity\Tag;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\CalculateAverageRating;
use App\Rating\CountRatingsPerValue;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Generator;

use function array_fill_callback;
use function array_walk;
use function in_array;
use function min;
use function sprintf;

final class VideoGameFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * Ces jeux vidéo sont utilisés par des tests fonctionnels qui vérifient un
     * nombre précis d'avis (ex: ReviewTest). On ne leur ajoute donc pas d'avis
     * "aléatoires" via les fixtures pour ne pas casser ces assertions.
     */
    private const VIDEO_GAME_INDEXES_WITHOUT_REVIEWS = [2, 3, 4];

    public function __construct(
        private readonly Generator $faker,
        private readonly CalculateAverageRating $calculateAverageRating,
        private readonly CountRatingsPerValue $countRatingsPerValue
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $manager->getRepository(User::class)->findAll();
        $tags = $manager->getRepository(Tag::class)->findAll();

        $videoGames = array_fill_callback(0, 50, fn (int $index): VideoGame => (new VideoGame)
            ->setTitle(sprintf('Jeu vidéo %d', $index))
            ->setDescription($this->faker->paragraphs(10, true))
            ->setReleaseDate(new DateTimeImmutable())
            ->setTest($this->faker->paragraphs(6, true))
            ->setRating(($index % 5) + 1)
            ->setImageName(sprintf('video_game_%d.png', $index))
            ->setImageSize(2_098_872)
        );

        foreach ($videoGames as $videoGame) {
            $selectedTags = $this->faker->randomElements($tags, $this->faker->numberBetween(1, 4));

            foreach ($selectedTags as $tag) {
                $videoGame->getTags()->add($tag);
            }
        }

        array_walk($videoGames, [$manager, 'persist']);

        $manager->flush();

        foreach ($videoGames as $index => $videoGame) {
            if (in_array($index, self::VIDEO_GAME_INDEXES_WITHOUT_REVIEWS, true)) {
                continue;
            }

            $reviewers = $this->faker->randomElements($users, $this->faker->numberBetween(0, min(5, count($users))));

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
