<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Review;
use App\Model\Entity\VideoGame;
use App\Tests\Functional\FunctionalTestCase;

/**
 * Test fonctionnel dédié à la vérification du bon comportement lors de
 * l'ajout d'une note (Review) à un jeu vidéo.
 */
final class ReviewTest extends FunctionalTestCase
{
    // Cas nominal : ajout d'une note avec des données valides.

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

    public function testThatLoggedInUserCanPostAReviewWithoutAComment(): void
    {
        $this->login('user+3@email.com');

        $this->get('/jeu-video-3');

        $this->client->submitForm('Poster', ['review[rating]' => '5']);

        self::assertResponseRedirects('/jeu-video-3');

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-3']);
        $review = $this->getEntityManager()->getRepository(Review::class)->findOneBy(['videoGame' => $videoGame]);

        self::assertNotNull($review);
        self::assertSame(5, $review->getRating());
        self::assertNull($review->getComment());
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

    // Erreurs de validation : des données invalides doivent renvoyer une réponse 422.

    public function testThatPostingAReviewWithoutARatingFailsWithAValidationError(): void
    {
        $this->login('user+4@email.com');
        $crawler = $this->get('/jeu-video-4');
        $token = $crawler->filter('input[name="review[_token]"]')->attr('value');

        $this->client->request('POST', '/jeu-video-4', [
            'review' => ['comment' => 'Pas de note fournie', '_token' => $token],
        ]);

        self::assertResponseIsUnprocessable();

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-4']);
        self::assertCount(0, $videoGame->getReviews());
    }

    public function testThatPostingAReviewWithAnInvalidRatingChoiceFailsWithAValidationError(): void
    {
        $this->login('user+5@email.com');
        $crawler = $this->get('/jeu-video-5');
        $token = $crawler->filter('input[name="review[_token]"]')->attr('value');

        $this->client->request('POST', '/jeu-video-5', [
            'review' => ['rating' => '42', 'comment' => 'Note hors limites', '_token' => $token],
        ]);

        self::assertResponseIsUnprocessable();
    }

    public function testThatPostingAReviewWithATooLongCommentFailsWithAValidationError(): void
    {
        $this->login('user+6@email.com');
        $this->get('/jeu-video-6');

        $this->client->submitForm('Poster', [
            'review[rating]' => '3',
            'review[comment]' => str_repeat('a', 2001),
        ]);

        self::assertResponseIsUnprocessable();

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-6']);
        self::assertCount(0, $videoGame->getReviews());
    }

    // Autorisations : seuls les utilisateurs connectés et n'ayant pas déjà noté le jeu peuvent le faire.

    public function testThatAnonymousUserCannotSeeTheReviewForm(): void
    {
        $this->get('/jeu-video-7');

        self::assertSelectorNotExists('button:contains("Poster")');
    }

    public function testThatAnonymousUserCannotSubmitAReview(): void
    {
        // Sans utilisateur connecté, le formulaire n'est jamais rendu par la page,
        // il est donc impossible d'obtenir un jeton CSRF valide : toute tentative
        // de soumission directe est rejetée (réponse 422, formulaire invalide).
        $this->client->request('POST', '/jeu-video-7', [
            'review' => ['rating' => '3', 'comment' => 'Je ne suis pas connecte'],
        ]);

        self::assertResponseIsUnprocessable();

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-7']);
        self::assertCount(0, $videoGame->getReviews());
    }

    public function testThatUserCannotForceAReviewOnAVideoGameAlreadyReviewed(): void
    {
        $this->login('user+8@email.com');

        // On récupère un jeton CSRF valide avant d'avoir posté un premier avis.
        $crawler = $this->get('/jeu-video-8');
        $token = $crawler->filter('input[name="review[_token]"]')->attr('value');

        $this->client->request('POST', '/jeu-video-8', [
            'review' => ['rating' => '3', 'comment' => 'Premier avis', '_token' => $token],
        ]);
        self::assertResponseRedirects('/jeu-video-8');

        // Un second envoi (en réutilisant le même jeton, toujours valide pour la session)
        // doit être bloqué par la règle métier du VideoGameVoter.
        $this->client->request('POST', '/jeu-video-8', [
            'review' => ['rating' => '5', 'comment' => 'Second avis force', '_token' => $token],
        ]);

        self::assertResponseStatusCodeSame(403);

        $videoGame = $this->getEntityManager()->getRepository(VideoGame::class)->findOneBy(['slug' => 'jeu-video-8']);
        self::assertCount(1, $videoGame->getReviews());
    }
}
