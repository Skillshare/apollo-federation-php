<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;

class DungeonsAndDragonsSchema
{
    public static $monstersSchema;


    public static function getSchema(): FederatedSchema
    {
        if (!self::$monstersSchema) {
            $skillType = new EntityObjectType([
                'name' => 'Skill',
                'description' => 'A Skill that a monster can possess',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::int()),
                        'isExternal' => true
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'isExternal' => true
                    ]
                ],
                'keyFields' => ['id']
            ]);
            
            
            $monsterType = new EntityObjectType([
                'name' => 'Monster',
                'description' => 'A Monster from the Monster Manual',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::int()),
                        'isExternal' => true
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'isExternal' => true
                    ],
                    'challengeRating' => [
                        'type' => Type::nonNull(Type::int()),
                        'isExternal' => true
                    ],
                    'skills' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($skillType))),
                        'resolve' => function ($root) {
                            return  DungeonsAndDragonsData::getSkillsByIds($root['skills']);
                        }
                    ]
                ],
                'keyFields' => ['id'],
                '__resolveReference' => function ($ref, $context, $info) {
                    $monster = DungeonsAndDragonsData::getMonsterById($ref["id"]);
                    $typeName = ['__typename' => $ref['__typename']];

                    return $typeName + $monster;
                }
            ]);

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'monsters' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($monsterType))),
                        'resolve' => function() {
                            return DungeonsAndDragonsData::getMonsters();
                        }
                    ]
                ]
            ]);

            self::$monstersSchema = new FederatedSchema(
                [
                    'query' => $queryType,
                    'resolve' =>  function ($root, $args, $context, $info) use ($monsterType) {
                        return array_map(function ($ref) use ($monsterType) {
                            return $monsterType->resolveReference($ref);
                        }, $args['representations']);
                    }
                ]
            );

            return self::$monstersSchema;
        }
    }
}