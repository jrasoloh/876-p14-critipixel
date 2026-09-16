<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

/**
 * Test unitaire dédié à la vérification du nombre de notes attribuées à un
 * jeu vidéo, par valeur de note (App\Rating\CountRatingsPerValue).
 */
final class CountRatingsPerValueTest extends TestCase
{
    private RatingHandler $countRatingsPerValue;

    protected function setUp(): void
    {
        $this->countRatingsPerValue = new RatingHandler();
    }

    public function testCountRatingsPerValueForSeveralVideoGamesWithVariousRatings(): void
    {
        // Jeu vidéo sans aucun avis : toutes les valeurs doivent rester à 0.
        $videoGameWithoutReviews = new VideoGame();

        // Jeu vidéo n'ayant reçu que des avis d'une seule valeur.
        $videoGameWithOnlyFiveStarReviews = $this->createVideoGameWithRatings([5, 5, 5]);

        // Jeu vidéo ayant reçu des avis répartis sur toutes les valeurs possibles.
        $videoGameWithMixedReviews = $this->createVideoGameWithRatings([1, 1, 2, 3, 3, 3, 4, 5, 5]);

        $this->countRatingsPerValue->countRatingsPerValue($videoGameWithoutReviews);
        $this->countRatingsPerValue->countRatingsPerValue($videoGameWithOnlyFiveStarReviews);
        $this->countRatingsPerValue->countRatingsPerValue($videoGameWithMixedReviews);

        $numbersWithoutReviews = $videoGameWithoutReviews->getNumberOfRatingsPerValue();
        self::assertSame(0, $numbersWithoutReviews->getNumberOfOne());
        self::assertSame(0, $numbersWithoutReviews->getNumberOfTwo());
        self::assertSame(0, $numbersWithoutReviews->getNumberOfThree());
        self::assertSame(0, $numbersWithoutReviews->getNumberOfFour());
        self::assertSame(0, $numbersWithoutReviews->getNumberOfFive());

        $numbersWithOnlyFiveStarReviews = $videoGameWithOnlyFiveStarReviews->getNumberOfRatingsPerValue();
        self::assertSame(0, $numbersWithOnlyFiveStarReviews->getNumberOfOne());
        self::assertSame(0, $numbersWithOnlyFiveStarReviews->getNumberOfTwo());
        self::assertSame(0, $numbersWithOnlyFiveStarReviews->getNumberOfThree());
        self::assertSame(0, $numbersWithOnlyFiveStarReviews->getNumberOfFour());
        self::assertSame(3, $numbersWithOnlyFiveStarReviews->getNumberOfFive());

        $numbersWithMixedReviews = $videoGameWithMixedReviews->getNumberOfRatingsPerValue();
        self::assertSame(2, $numbersWithMixedReviews->getNumberOfOne());
        self::assertSame(1, $numbersWithMixedReviews->getNumberOfTwo());
        self::assertSame(3, $numbersWithMixedReviews->getNumberOfThree());
        self::assertSame(1, $numbersWithMixedReviews->getNumberOfFour());
        self::assertSame(2, $numbersWithMixedReviews->getNumberOfFive());
    }

    public function testCountRatingsPerValueShouldResetPreviousCounts(): void
    {
        $videoGame = new VideoGame();
        $videoGame->getNumberOfRatingsPerValue()->increaseFive();
        $videoGame->getNumberOfRatingsPerValue()->increaseFive();

        $this->countRatingsPerValue->countRatingsPerValue($videoGame);

        self::assertSame(0, $videoGame->getNumberOfRatingsPerValue()->getNumberOfFive());
    }

    /**
     * @dataProvider provideRatingsAndExpectedCounts
     *
     * @param int[] $ratings
     */
    public function testCountRatingsPerValueShouldCountEachRatingValue(
        array $ratings,
        int $expectedOne,
        int $expectedTwo,
        int $expectedThree,
        int $expectedFour,
        int $expectedFive,
    ): void {
        $videoGame = $this->createVideoGameWithRatings($ratings);

        $this->countRatingsPerValue->countRatingsPerValue($videoGame);

        $numberOfRatingsPerValue = $videoGame->getNumberOfRatingsPerValue();

        self::assertSame($expectedOne, $numberOfRatingsPerValue->getNumberOfOne());
        self::assertSame($expectedTwo, $numberOfRatingsPerValue->getNumberOfTwo());
        self::assertSame($expectedThree, $numberOfRatingsPerValue->getNumberOfThree());
        self::assertSame($expectedFour, $numberOfRatingsPerValue->getNumberOfFour());
        self::assertSame($expectedFive, $numberOfRatingsPerValue->getNumberOfFive());
    }

    /**
     * @return iterable<string, array{0: int[], 1: int, 2: int, 3: int, 4: int, 5: int}>
     */
    public static function provideRatingsAndExpectedCounts(): iterable
    {
        yield 'single review' => [[3], 0, 0, 1, 0, 0];
        yield 'only one star reviews' => [[1, 1, 1], 3, 0, 0, 0, 0];
        yield 'mixed reviews' => [[1, 1, 2, 3, 3, 3, 4, 5, 5], 2, 1, 3, 1, 2];
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
