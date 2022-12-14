<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use function array_filter;
use function in_array;
use function reset;

/**
 * @psalm-type EpisodeType = array{id: int, title: string, characters: int[]}
 * @psalm-type CharacterType = array{id: int, name: string, locations: int[]}
 * @psalm-type LocationType = array{id: int, name: string}
 */
class StarWarsData
{
    /**
     * @var EpisodeType[]
     */
    private static array $episodes;

    /**
     * @var CharacterType[]
     */
    private static array $characters;

    /**
     * @var LocationType[]
     */
    private static array $locations;

    /**
     * @return EpisodeType
     */
    public static function getEpisodeById(int $id): array
    {
        $matches = array_filter(self::getEpisodes(), fn ($episode) => $episode['id'] === $id);

        return reset($matches);
    }

    /**
     * @return EpisodeType[]
     */
    public static function getEpisodes(): array
    {
        if (!isset(self::$episodes)) {
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
     * @return CharacterType[]
     */
    public static function getCharactersByIds(array $ids): array
    {
        return array_filter(self::getCharacters(), fn ($character) => in_array($character['id'], $ids));
    }

    /**
     * @return CharacterType[]
     */
    public static function getCharacters(): array
    {
        if (!isset(self::$characters)) {
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
     * @return LocationType[]
     */
    public static function getLocationsByIds(array $ids): array
    {
        return array_filter(self::getLocations(), fn ($location) => in_array($location['id'], $ids));
    }

    /**
     * @return LocationType[]
     */
    public static function getLocations(): array
    {
        if (!isset(self::$locations)) {
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
