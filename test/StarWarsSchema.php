<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use Exception;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;
use GraphQL\Utils\Utils;

class StarWarsSchema
{
    public static $episodesSchema;

    public static function getEpisodesSchema(): FederatedSchema
    {
        if (!self::$episodesSchema) {
            $locationType = new EntityRefObjectType([
                'name' => 'Location',
                'description' => 'A location in the Star Wars Trilogy',
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

            $characterType = new EntityRefObjectType([
                'name' => 'Character',
                'description' => 'A character in the Star Wars Trilogy',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::int()),
                        'isExternal' => true
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'isExternal' => true
                    ],
                    'locations' => [
                        'type' => Type::nonNull(Type::listOf($locationType)),
                        'resolve' => function ($root) {
                            return StarWarsData::getLocationsByIds($root['locations']);
                        },
                        'requires' => 'name'
                    ]
                ],
                'keyFields' => ['id']
            ]);

            $episodeType = new EntityObjectType([
                'name' => 'Episode',
                'description' => 'A film in the Star Wars Trilogy',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::int())
                    ],
                    'title' => [
                        'type' => Type::nonNull(Type::string())
                    ],
                    'characters' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($characterType))),
                        'resolve' => function ($root) {
                            return StarWarsData::getCharactersByIds($root['characters']);
                        },
                        'provides' => 'name'
                    ]
                ],
                'keyFields' => ['id'],
                '__resolveReference' => function ($ref) {
                    return StarWarsData::getEpisodeById($ref['id']);
                }
            ]);

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'episodes' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                        'resolve' => function () {
                            return StarWarsData::getEpisodes();
                        }
                    ],
                    'deprecatedEpisodes' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($episodeType))),
                        'deprecationReason' => 'Because you should use the other one.'
                    ]
                ]
            ]);

            self::$episodesSchema = new FederatedSchema([
                'query' => $queryType,
                'resolve' => function ($root, $args, $context, $info) {
                    $foo = array_map(function ($ref) use ($context, $info) {
 
                        $type = $info->schema->getType($ref['__typename']);
                        Utils::invariant(
                            $type && $type instanceof EntityObjectType,
                            sprintf(
                                'The _entities resolver tried to load an entity for type "%s", but no object type of that name was found in the schema',
                                $type->name
                            )
                            );
                        if (!$type->hasReferenceResolver()) {
                            return $ref;
                        }
                        return $type->resolveReference($ref, $context, $info);
                    }, $args['representations']);
                    return $foo;
                }
            ]);
        }

        return self::$episodesSchema;
    }
}
