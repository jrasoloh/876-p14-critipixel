<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire dédié à la vérification du calcul de la moyenne des notes
 * attribuées à un jeu vidéo (App\Rating\CalculateAverageRating).
 */
final class CalculateAverageRatingTest extends TestCase
{
    private RatingHandler $calculateAverageRating;

    protected function setUp(): void
    {
        $this->calculateAverageRating = new RatingHandler();
    }

    public function testCalculateAverageForSeveralVideoGamesWithVariousRatings(): void
    {
        // Jeu vidéo sans aucun avis : la moyenne doit rester nulle.
        $videoGameWithoutReviews = new VideoGame();

        // Jeu vidéo avec des avis identiques : la moyenne correspond à la note commune.
        $videoGameWithSameRatings = $this->createVideoGameWithRatings([4, 4, 4]);

        // Jeu vidéo avec une moyenne exacte (pas d'arrondi nécessaire).
        $videoGameWithExactAverage = $this->createVideoGameWithRatings([5, 3, 4]);

        // Jeu vidéo avec une moyenne qui doit être arrondie à l'entier supérieur.
        $videoGameWithRoundedUpAverage = $this->createVideoGameWithRatings([5, 4]);

        // Jeu vidéo n'ayant reçu que des notes minimales.
        $videoGameWithLowestRatings = $this->createVideoGameWithRatings([1, 1, 1]);

        $this->calculateAverageRating->calculateAverage($videoGameWithoutReviews);
        $this->calculateAverageRating->calculateAverage($videoGameWithSameRatings);
        $this->calculateAverageRating->calculateAverage($videoGameWithExactAverage);
        $this->calculateAverageRating->calculateAverage($videoGameWithRoundedUpAverage);
        $this->calculateAverageRating->calculateAverage($videoGameWithLowestRatings);

        self::assertNull($videoGameWithoutReviews->getAverageRating());
        self::assertSame(4, $videoGameWithSameRatings->getAverageRating());
        self::assertSame(4, $videoGameWithExactAverage->getAverageRating());
        self::assertSame(5, $videoGameWithRoundedUpAverage->getAverageRating());
        self::assertSame(1, $videoGameWithLowestRatings->getAverageRating());
    }

    public function testCalculateAverageShouldSetAverageRatingToNullWhenThereIsNoReview(): void
    {
        $videoGame = new VideoGame();
        $videoGame->setAverageRating(3);

        $this->calculateAverageRating->calculateAverage($videoGame);

        self::assertNull($videoGame->getAverageRating());
    }

    /**
     * @dataProvider provideRatingsAndExpectedAverage
     *
     * @param int[] $ratings
     */
    public function testCalculateAverageShouldRoundUpToTheNearestInteger(array $ratings, int $expectedAverage): void
    {
        $videoGame = $this->createVideoGameWithRatings($ratings);

        $this->calculateAverageRating->calculateAverage($videoGame);

        self::assertSame($expectedAverage, $videoGame->getAverageRating());
    }

    /**
     * @return iterable<string, array{0: int[], 1: int}>
     */
    public static function provideRatingsAndExpectedAverage(): iterable
    {
        yield 'single review' => [[4], 4];
        yield 'exact average' => [[5, 3, 4], 4];
        yield 'average rounded up' => [[5, 4], 5];
        yield 'all minimum ratings' => [[1, 1, 1], 1];
        yield 'all maximum ratings' => [[5, 5, 5], 5];
    }

    /**
     * @param int[] $ratings
     */
    private function createVideoGameWithRatings(array $ratings): VideoGame
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rating) {
            $videoGame->getReviews()->add(
                (new Review())
                    ->setUser(new User())
                    ->setRating($rating)
            );
        }

        return $videoGame;
    }
}
