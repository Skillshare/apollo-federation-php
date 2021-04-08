<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

class DungeonsAndDragonsData
{
    private static $monsters;


    public static function getMonsterById($id)
    {
        $matches = array_filter(self::getMonsters(), function ($episode) use ($id) {
            return $episode['id'] === $id;
        });

        return $matches[0];
    }

    public static function getMonstersByIds($ids)
    {
        return array_filter(self::getMonsters(), function ($monster) use ($ids) {
            return in_array($monster['id'], $ids);
        });
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
