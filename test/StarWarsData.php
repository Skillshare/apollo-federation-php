<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

class StarWarsData
{
    private static $episodes;

    private static $characters;

    private static $locations;

    public static function getEpisodeById($id)
    {
        $matches = array_filter(self::getEpisodes(), function ($episode) use ($id) {
            return $episode['id'] === $id;
        });

        return $matches[0];
    }

    public static function getEpisodes()
    {
        if (!self::$episodes) {
            self::$episodes = [
                [
                    'id' => 1,
                    'title' => 'A New Hope',
                    'characters' => [1, 2, 3]
                ],
                [
                    'id' => 2,
                    'title' => 'The Empire Strikes Back',
                    'characters' => [1, 2, 3]
                ],
                [
                    'id' => 3,
                    'title' => 'Return of the Jedi',
                    'characters' => [1, 2, 3]
                ]
            ];
        }

        return self::$episodes;
    }

    public static function getCharactersByIds($ids)
    {
        return array_filter(self::getCharacters(), function ($character) use ($ids) {
            return in_array($character['id'], $ids);
        });
    }

    public static function getCharacters()
    {
        if (!self::$characters) {
            self::$characters = [
                [
                    'id' => 1,
                    'name' => 'Luke Skywalker',
                    'locations' => [1, 2, 3]
                ],
                [
                    'id' => 2,
                    'name' => 'Han Solo',
                    'locations' => [1, 2]
                ],
                [
                    'id' => 3,
                    'name' => 'Leia Skywalker',
                    'locations' => [3]
                ]
            ];
        }

        return self::$characters;
    }

    public static function getLocationsByIds($ids)
    {
        return array_filter(self::getLocations(), function ($location) use ($ids) {
            return in_array($location['id'], $ids);
        });
    }

    public static function getLocations()
    {
        if (!self::$locations) {
            self::$locations = [
                [
                    'id' => 1,
                    'name' => 'Tatooine'
                ],
                [
                    'id' => 2,
                    'name' => 'Endor'
                ],
                [
                    'id' => 3,
                    'name' => 'Hoth'
                ]
            ];
        }

        return self::$locations;
    }
}
