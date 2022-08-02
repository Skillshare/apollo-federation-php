<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

class StarWarsData
{
    /**
     * @var array<int,array<string,mixed>>|null
     */
    private static ?array $episodes = null;

    /**
     * @var array<int,array<string,mixed>>|null
     */
    private static ?array $characters = null;

    /**
     * @var array<int,array<string,mixed>>|null
     */
    private static ?array $locations = null;

    /**
     * @return array<string,mixed>|null
     */
    public static function getEpisodeById(int $id): ?array
    {
        $matches = array_filter(self::getEpisodes(), static fn (array $episode): bool => $episode['id'] === $id);

        return reset($matches) ?: null;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function getEpisodes(): array
    {
        if (!self::$episodes) {
            self::$episodes = [
                [
                    'id' => 1,
                    'title' => 'A New Hope',
                    'characters' => [1, 2, 3],
                ],
                [
                    'id' => 2,
                    'title' => 'The Empire Strikes Back',
                    'characters' => [1, 2, 3],
                ],
                [
                    'id' => 3,
                    'title' => 'Return of the Jedi',
                    'characters' => [1, 2, 3],
                ],
            ];
        }

        return self::$episodes;
    }

    /**
     * @param int[] $ids
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getCharactersByIds(array $ids): array
    {
        return array_filter(self::getCharacters(), static fn ($item): bool => \in_array($item['id'], $ids, true));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function getCharacters(): array
    {
        if (!self::$characters) {
            self::$characters = [
                [
                    'id' => 1,
                    'name' => 'Luke Skywalker',
                    'locations' => [1, 2, 3],
                ],
                [
                    'id' => 2,
                    'name' => 'Han Solo',
                    'locations' => [1, 2],
                ],
                [
                    'id' => 3,
                    'name' => 'Leia Skywalker',
                    'locations' => [3],
                ],
            ];
        }

        return self::$characters;
    }

    /**
     * @param int[] $ids
     *
     * @return array<int,array<string,mixed>>
     */
    public static function getLocationsByIds(array $ids): array
    {
        return array_filter(self::getLocations(), static fn ($item): bool => \in_array($item['id'], $ids, true));
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function getLocations(): array
    {
        if (!self::$locations) {
            self::$locations = [
                [
                    'id' => 1,
                    'name' => 'Tatooine',
                ],
                [
                    'id' => 2,
                    'name' => 'Endor',
                ],
                [
                    'id' => 3,
                    'name' => 'Hoth',
                ],
            ];
        }

        return self::$locations;
    }
}
