<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;

final class ReviewTest extends FunctionalTestCase
{
    public function testThatLoggedInUserCanPostAReview(): void
    {
        $this->login('user+2@email.com');

        $this->get('/jeu-video-2');

        self::assertSelectorExists('button:contains("Poster")');

        $this->client->submitForm('Poster', [
            'review[rating]' => '4',
            'review[comment]' => 'Un tres bon jeu !',
        ]);

        self::assertResponseRedirects('/jeu-video-2');

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-2']);
        $review = $this->getEntityManager()->getRepository(Review::class)->findOneBy(['videoGame' => $videoGame]);

        self::assertNotNull($review);
        self::assertSame(4, $review->getRating());
        self::assertSame('Un tres bon jeu !', $review->getComment());
        self::assertSame('user+2@email.com', $review->getUser()->getEmail());
    }

    public function testThatUserCannotPostReviewTwiceOnTheSameVideoGame(): void
    {
        $this->login('user+3@email.com');

        $this->get('/jeu-video-3');
        $this->client->submitForm('Poster', ['review[rating]' => '3']);

        $this->get('/jeu-video-3');

        self::assertSelectorNotExists('button:contains("Poster")');
        self::assertSelectorTextContains('h2', 'Notes (1)');
    }

    public function testThatAnonymousUserCannotSeeTheReviewForm(): void
    {
        $this->get('/jeu-video-4');

        self::assertSelectorNotExists('button:contains("Poster")');
    }
}

