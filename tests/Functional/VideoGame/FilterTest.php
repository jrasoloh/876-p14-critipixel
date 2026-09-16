<?php

declare(strict_types=1);

namespace App\Tests\Functional\VideoGame;

use App\Model\Entity\Tag;
use App\Tests\Functional\FunctionalTestCase;

use function array_map;

final class FilterTest extends FunctionalTestCase
{
    public function testShouldListTenVideoGames(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->clickLink('2');
        self::assertResponseIsSuccessful();
    }

    public function testShouldFilterVideoGamesBySearch(): void
    {
        $this->get('/');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
        $this->client->submitForm('Filtrer', ['filter[search]' => 'Jeu vidéo 49'], 'GET');
        self::assertResponseIsSuccessful();
        self::assertSelectorCount(1, 'article.game-card');
    }

    /**
     * Vérifie le filtrage par tags. Le filtre applique une sémantique "ET" :
     * un jeu doit posséder TOUS les tags demandés pour être retourné.
     *
     * Les comptes attendus sont garantis par l'assignation déterministe des
     * tags réservés dans VideoGameFixtures.
     *
     * @dataProvider provideTagFilters
     *
     * @param string[] $tagNames
     */
    public function testShouldFilterVideoGamesByTags(array $tagNames, int $expectedCount): void
    {
        $this->get('/', ['filter' => ['tags' => $this->getTagIds($tagNames)]]);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount($expectedCount, 'article.game-card');
    }

    /**
     * @return iterable<string, array{0: string[], 1: int}>
     */
    public static function provideTagFilters(): iterable
    {
        // Aucun tag spécifié : tous les jeux sont retournés (10 affichés sur la 1re page des 50).
        yield 'no tag' => [[], 10];
        // Un seul tag.
        yield 'one tag (Action)' => [['Action'], 4];
        yield 'one tag (Aventure)' => [['Aventure'], 3];
        yield 'one tag (RPG)' => [['RPG'], 1];
        // Plusieurs tags (sémantique ET).
        yield 'two tags (Action + Aventure)' => [['Action', 'Aventure'], 2];
        yield 'three tags (Action + Aventure + RPG)' => [['Action', 'Aventure', 'RPG'], 1];
    }

    /**
     * Lorsqu'un tag inexistant est spécifié, le filtre est ignoré (choix invalide)
     * et la liste complète des jeux est retournée sans erreur.
     */
    public function testShouldIgnoreFilterWhenTagDoesNotExist(): void
    {
        $nonExistentTagId = 999999;

        $this->get('/', ['filter' => ['tags' => [$nonExistentTagId]]]);

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(10, 'article.game-card');
    }

    /**
     * @param string[] $tagNames
     * @return int[]
     */
    private function getTagIds(array $tagNames): array
    {
        $tagRepository = $this->getEntityManager()->getRepository(Tag::class);

        return array_map(
            static fn (string $name): int => $tagRepository->findOneBy(['name' => $name])->getId(),
            $tagNames
        );
    }
}
