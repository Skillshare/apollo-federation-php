<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Exception;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;

class DungeonsAndDragonsSchema
{
    public static $episodesSchema;
    public static $buffer = [];

    public static function getSchema(): FederatedSchema
    {
        if (!self::$episodesSchema) {
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
                    ]
                ]
            ]);

            self::$episodesSchema = new FederatedSchema(
                [
                    'query' => $queryType,
                    'resolve' =>  function ($root, $args, $context, $info) use ($monsterType) {
                        array_map(function ($ref) use ($monsterType) {
                            return $monsterType->resolveReference($ref);
                        }, $args['representations']);
                        $ms = DungeonsAndDragonsData::getMonstersByIds(self::$buffer);
                        print_r($ms);
                        return $ms;
                    }
                ]
            );

            return self::$episodesSchema;
        }
    }
}