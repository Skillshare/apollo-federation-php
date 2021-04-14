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
                        'type' => Type::nonNull(Type::int())
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string())
                    ],
                    'challengeRating' => [
                        'type' => Type::nonNull(Type::int()),
                    ]
                ],
                'keyFields' => ['id'],
                '__resolveReference' => function ($ref) {
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
                            return DungeonsAndDragonsData::getMonstersByIds(self::$buffer);
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
                        $ms = DungeonsAndDragonsData::getMonstersByIds(self::$buffer);
                        return $ms;
                    }
                ]
            );

            return self::$monstersSchema;
        }
    }
}