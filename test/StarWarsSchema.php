<?php

declare(strict_types=1);

namespace Apollo\Federation\Tests;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;

use Apollo\Federation\FederatedSchema;
use Apollo\Federation\Types\EntityObjectType;
use Apollo\Federation\Types\EntityRefObjectType;

class StarWarsSchema
{
    public static $episodesSchema;
    public static $overRiddedEpisodesSchema;

    public static $episodeType = null;
    public static $locationType = null;
    public static $characterType = null;

    public static function getEpisodesSchema(): FederatedSchema
    {
        if (!self::$episodesSchema) {
            self::$episodesSchema = new FederatedSchema([
                'query' => self::getQueryType(),
                'typeLoader' => self::getTypeLoader()
            ]);
        }
        return self::$episodesSchema;
    }

    public static function getEpisodesSchemaWithProvidedEntities(): FederatedSchema
    {
        if (!self::$episodesSchema) {
            self::$episodesSchema = new FederatedSchema([
                'query' => self::getQueryType(),
                'typeLoader' => self::getTypeLoader(),
                'entityTypes' => [
                    self::getCharacterType(),
                    self::getLocationType(),
                    self::getEpisodeType()
                ]
            ]);
        }
        return self::$episodesSchema;
    }

    public static function getEpisodesSchemaCustomResolver(): FederatedSchema
    {
        if (!self::$overRiddedEpisodesSchema) {
            self::$overRiddedEpisodesSchema = new FederatedSchema([
                'query' => self::getQueryType(),
                'resolve' =>  function ($root, $args, $context, $info) {
                    return array_map(function ($ref) use ($info) {
                        $typeName = $ref['__typename'];
                        $type = $info->schema->getType($typeName);
                        $ref["id"] = $ref["id"] + 1;
                        return $type->resolveReference($ref);
                    }, $args['representations']);
                },
                'typeLoader' => self::getTypeLoader()
            ]);
        }
        return self::$overRiddedEpisodesSchema;
    }

    public static function getTypeLoader(): callable {
        return function ($typeName) {
            if ($typeName === "Episode") {
                return self::getEpisodeType();
            }
            else if ($typeName === "Character") {
                return self::getCharacterType();
            }
            else if ($typeName === "Location") {
                return self::getLocationType();
            }
        };
    }

    private static function getQueryType(): ObjectType
    {
        $episodeType = self::getEpisodeType();

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
        return $queryType;
    }

    private static function getEpisodeType(): EntityObjectType
    {
        if (self::$episodeType) {
            return self::$episodeType;
        }

        self::$episodeType = new EntityObjectType([
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
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getCharacterType()))),
                    'resolve' => function ($root) {
                        return StarWarsData::getCharactersByIds($root['characters']);
                    },
                    'provides' => 'name'
                ]
            ],
            'keyFields' => ['id'],
            '__resolveReference' => function ($ref) {
                // print_r($ref);
                $entity = StarWarsData::getEpisodeById($ref['id']);
                $entity["__typename"] = "Episode";
                return $entity;
            }
        ]);

        return self::$episodeType;
    }

    private static function getCharacterType(): EntityRefObjectType
    {
        if (self::$characterType) {
            return self::$characterType;
        }

        self::$characterType = new EntityRefObjectType([
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
                    'type' => Type::nonNull(Type::listOf(self::getLocationType())),
                    'resolve' => function ($root) {
                        return StarWarsData::getLocationsByIds($root['locations']);
                    },
                    'requires' => 'name'
                ]
            ],
            'keyFields' => ['id']
        ]);

        return self::$characterType;
    }

    private static function getLocationType(): EntityRefObjectType
    {
        if (self::$locationType) {
            return self::$locationType;
        }

        self::$locationType = new EntityRefObjectType([
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

        return self::$locationType;
    }
}
