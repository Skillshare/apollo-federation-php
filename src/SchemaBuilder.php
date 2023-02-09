<?php

declare(strict_types=1);

namespace Apollo\Federation;

use GraphQL\Type\Definition\Directive;

class SchemaBuilder
{
    /**
     * @param array<string,mixed> $schemaConfig
     * @param array<string,mixed> $builderConfig
     */
    public function build(array $schemaConfig, array $builderConfig = []): FederatedSchema
    {
        $builderConfig += ['directives' => ['link']];
        $schemaConfig = array_merge($schemaConfig, $this->getEntityDirectivesConfig($schemaConfig, $builderConfig));

        return new FederatedSchema($schemaConfig);
    }

    /**
     * @param array<string,mixed> $schemaConfig
     * @param array{ directives: array<string> } $builderConfig
     *
     * @return array<string,mixed>
     */
    protected function getEntityDirectivesConfig(array $schemaConfig, array $builderConfig): array
    {
        $directives = array_intersect_key(Directives::getDirectives(), array_flip($builderConfig['directives']));
        if (array_intersect_key($directives, Directive::getInternalDirectives())) {
            throw new \LogicException('Some Apollo directives override internals.');
        }
        $schemaConfig['directives'] = array_merge($schemaConfig['directives'] ?? [], $directives);

        return $schemaConfig;
    }
}
