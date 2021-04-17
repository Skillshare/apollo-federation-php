<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Exception;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;

class DungeonsAndDragonsSchema
{
    public static $monstersSchema;
    public static $buffer = [];

    public static function getSchema(): FederatedSchema
    {
        if (!self::$monstersSchema) {
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
                    ]
                ],
                'keyFields' => ['id'],
                '__resolveReference' => function ($ref, $context, $info) {
                    array_push(self::$buffer, $ref["id"]);
                    return $ref;
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
                        array_map(function ($ref) use ($monsterType) {
                            return $monsterType->resolveReference($ref);
                        }, $args['representations']);

                        $monsters = DungeonsAndDragonsData::getMonstersByIds(self::$buffer);
                        return $monsters;
                    }
                ]
            );

            return self::$monstersSchema;
        }
    }
}