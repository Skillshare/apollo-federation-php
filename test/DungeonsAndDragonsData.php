<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

class DungeonsAndDragonsData
{
    private static $monsters;


    public static function getMonsterById(int $id): array
    {
        $matches = array_filter(self::getMonsters(), function ($monster) use ($id) {
            return $monster['id'] === $id;
        });

        return array_values($matches)[0];
    }

    public static function getMonstersByIds(array $ids): array
    {
        $matches = array_filter(self::getMonsters(), function ($monster) use ($ids) {
            return in_array($monster['id'], $ids);
        });

        return $matches;
    }

    public static function getMonsters()
    {
        if (!self::$monsters) {
            self::$monsters = [
                [
                    'id' => 1,
                    'name' => 'Aboleth',
                    'challengeRating' => 7
                ],
                [
                    'id' => 2,
                    'name' => 'Adult Black Dragon',
                    'challengeRating' => 14
                ],
                [
                    'id' => 3,
                    'name' => 'Beholder',
                    'challengeRating' => 1
                ]
            ];
        }

        return self::$monsters;
    }
}
