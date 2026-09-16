<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Model\Entity\Review;
use App\Model\Entity\User;
use App\Model\Entity\VideoGame;
use App\Security\Voter\VideoGameVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class VideoGameVoterTest extends TestCase
{
    private VideoGameVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new VideoGameVoter();
    }

    public function testShouldGrantReviewWhenUserHasNotAlreadyReviewedTheVideoGame(): void
    {
        $user = new User();
        $videoGame = new VideoGame();

        $vote = $this->voter->vote($this->createTokenForUser($user), $videoGame, [VideoGameVoter::REVIEW]);

        self::assertSame(Voter::ACCESS_GRANTED, $vote);
    }

    public function testShouldDenyReviewWhenUserHasAlreadyReviewedTheVideoGame(): void
    {
        $user = new User();
        $videoGame = new VideoGame();
        $videoGame->getReviews()->add((new Review())->setUser($user)->setRating(5));

        $vote = $this->voter->vote($this->createTokenForUser($user), $videoGame, [VideoGameVoter::REVIEW]);

        self::assertSame(Voter::ACCESS_DENIED, $vote);
    }

    public function testShouldDenyReviewWhenTokenHasNoUser(): void
    {
        $videoGame = new VideoGame();

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $vote = $this->voter->vote($token, $videoGame, [VideoGameVoter::REVIEW]);

        self::assertSame(Voter::ACCESS_DENIED, $vote);
    }

    public function testShouldAbstainWhenAttributeIsNotSupported(): void
    {
        $vote = $this->voter->vote($this->createTokenForUser(new User()), new VideoGame(), ['edit']);

        self::assertSame(Voter::ACCESS_ABSTAIN, $vote);
    }

    public function testShouldAbstainWhenSubjectIsNotAVideoGame(): void
    {
        $vote = $this->voter->vote($this->createTokenForUser(new User()), new User(), [VideoGameVoter::REVIEW]);

        self::assertSame(Voter::ACCESS_ABSTAIN, $vote);
    }

    private function createTokenForUser(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return $token;
    }
}
