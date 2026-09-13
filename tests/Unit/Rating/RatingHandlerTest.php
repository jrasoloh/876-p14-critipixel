<?php

declare(strict_types=1);

namespace App\Tests\Unit\Rating;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Rating\RatingHandler;
use PHPUnit\Framework\TestCase;

final class RatingHandlerTest extends TestCase
{
    private RatingHandler $ratingHandler;

    protected function setUp(): void
    {
        $this->ratingHandler = new RatingHandler();
    }

    public function testCalculateAverageShouldBeNullWhenThereIsNoReview(): void
    {
        $videoGame = new VideoGame();
        $videoGame->setAverageRating(3);

        $this->ratingHandler->calculateAverage($videoGame);

        self::assertNull($videoGame->getAverageRating());
    }

    /**
     * @dataProvider provideRatings
     */
    public function testCalculateAverageShouldRoundUpToTheNearestInteger(array $ratings, int $expectedAverage): void
    {
        $videoGame = new VideoGame();

        foreach ($ratings as $rating) {
            $videoGame->getReviews()->add(self::createReview($rating));
        }

        $this->ratingHandler->calculateAverage($videoGame);

        self::assertSame($expectedAverage, $videoGame->getAverageRating());
    }

    public static function provideRatings(): iterable
    {
        yield 'single review' => [[4], 4];
        yield 'exact average' => [[5, 3, 4], 4];
        yield 'average rounded up' => [[5, 4], 5];
        yield 'all minimum' => [[1, 1, 1], 1];
    }

    public function testCountRatingsPerValueShouldResetPreviousCounts(): void
    {
        $videoGame = new VideoGame();
        $videoGame->getNumberOfRatingsPerValue()->increaseFive();
        $videoGame->getNumberOfRatingsPerValue()->increaseFive();

        $this->ratingHandler->countRatingsPerValue($videoGame);

        self::assertSame(0, $videoGame->getNumberOfRatingsPerValue()->getNumberOfFive());
    }

    public function testCountRatingsPerValueShouldCountEachRatingValue(): void
    {
        $videoGame = new VideoGame();

        foreach ([1, 1, 2, 3, 3, 3, 4, 5, 5] as $rating) {
            $videoGame->getReviews()->add(self::createReview($rating));
        }

        $this->ratingHandler->countRatingsPerValue($videoGame);

        $numberOfRatingsPerValue = $videoGame->getNumberOfRatingsPerValue();

        self::assertSame(2, $numberOfRatingsPerValue->getNumberOfOne());
        self::assertSame(1, $numberOfRatingsPerValue->getNumberOfTwo());
        self::assertSame(3, $numberOfRatingsPerValue->getNumberOfThree());
        self::assertSame(1, $numberOfRatingsPerValue->getNumberOfFour());
        self::assertSame(2, $numberOfRatingsPerValue->getNumberOfFive());
    }

    private static function createReview(int $rating): Review
    {
        return (new Review())
            ->setUser(new User())
            ->setRating($rating);
    }
}

