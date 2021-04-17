<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

class DungeonsAndDragonsData
{
    private static $monsters;
    private static $skills;

    public static function getMonsterById(int $id): array
    {
        $matches = array_filter(self::getMonsters(), function ($monster) use ($id) {
            return $monster['id'] === $id;
        });

        return array_values($matches)[0];
    }

    public static function getMonsters()
    {
        if (!self::$monsters) {
            self::$monsters = [
                [
                    'id' => 1,
                    'name' => 'Aboleth',
                    'challengeRating' => 7,
                    'skills' => [11, 12]
                ],
                [
                    'id' => 2,
                    'name' => 'Adult Black Dragon',
                    'challengeRating' => 14,
                    'skills' => [12, 13]
                ],
                [
                    'id' => 3,
                    'name' => 'Beholder',
                    'challengeRating' => 1,
                    'skills' => [13, 14]
                ]
            ];
        }

        return self::$monsters;
    }


    public static function getSkillsByIds(array $ids): array
    {
        return array_filter(self::getSkills(), function ($skill) use ($ids) {
            return in_array($skill['id'], $ids);
        });
    }

    public static function getSkills()
    {
        if (!self::$skills) {
            self::$skills = [
                [
                    'id' => 11,
                    'name' => 'Perception'
                ],
                [
                    'id' => 12,
                    'name' => 'Stealth'
                ],
                [
                    'id' => 13,
                    'name' => 'History'
                ],
                [
                    'id' => 14,
                    'name' => 'Darkvision'
                ],
            ];
        }

        return self::$skills;
    }
}
