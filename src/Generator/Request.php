<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Schema;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\GeneratedFile;
use Fansipan\Mist\ParameterCollection;
use Fansipan\Mist\ParameterType;
use Fansipan\Mist\PhpFeature;
use Fansipan\Request as AbstractRequest;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;

final class Request implements GeneratorInterface
{
    use GeneratorTrait;

    private string $namespace = 'Request';

    private string $contentType;

    private ParameterCollection $parameters;

    public function __construct(
        private readonly Operation $spec,
        private readonly string $method,
        private readonly string $path,
    ) {
    }

    public function withContent(string $contentType): self
    {
        $clone = clone $this;
        $clone->contentType = $contentType;

        return $clone;
    }

    public function withNamespace(string $namespace): self
    {
        $clone = clone $this;
        $clone->namespace = $namespace;

        return $clone;
    }

    public function generate(Config $config): GeneratedFile
    {
        $name = Str::studly($this->spec->operationId);
        $dir = empty($this->spec->tags) ? null : Str::studly($this->spec->tags[0]);

        $file = $this->file();
        $ns = [$config->package->namespace, $this->namespace];

        if ($dir) {
            $ns[] = $dir;
        }

        $namespace = $file->addNamespace(\implode('\\', $ns));
        $namespace->addUse(AbstractRequest::class);

        $class = $namespace->addClass($name);

        $class->setFinal()
            ->setExtends(AbstractRequest::class)
            ->addComment($this->spec->description);

        $this->addConstructorParameters($class, $config->package->minimumPhpVersion);
        $this->addEndpointMethod($class);
        $this->addVerbMethod($class);
        $this->addQueryMethod($class);

        return new GeneratedFile(
            [$config->output->directory, $config->output->paths->src, $this->namespace, $dir, $name.'.php'],
            $this->print($file)
        );
    }

    private function parameters(): ParameterCollection
    {
        // Avoid closure serialization on instantiation
        return $this->parameters ??= ParameterCollection::tryFrom($this->spec->parameters);
    }

    private function addConstructorParameters(ClassType $class, string $minimumPhpVersion): void
    {
        if ($this->parameters()->isEmpty() && \is_null($this->spec->requestBody)) {
            return;
        }

        $feature = new PhpFeature($minimumPhpVersion);
        $method = $class->addMethod('__construct');

        if ($this->spec->requestBody) {
            $requestBody = $this->resolveSpecRef($this->spec->requestBody);
            \assert($requestBody instanceof RequestBody);

            $contentType = $this->contentType ?: \array_key_first($requestBody->content);
            // Todo: set param
            // $mediaType = $requestBody->content[$contentType] ?? null;

            if ($trait = ContentTypeResolver::resolve($contentType)) {
                $class->getNamespace()->addUse($trait);
                $class->addTrait($trait);
            }
        }

        foreach ($this->parameters()->sortByDesc('required') as $parameter) {
            $schema = $this->resolveSpecRef($parameter->schema);
            \assert($schema instanceof Schema);

            $paramType = (string) ParameterType::fromSchema($schema);

            $constants = [];

            if (! empty($schema->enum)) {
                foreach ($schema->enum as $value) {
                    $class->addConstant(
                        $const = Str::of(sprintf('%s %s', $parameter->name, $value))->snake()->upper()->toString(),
                        $value
                    );

                    $constants[$value] = new Literal('self::?', [$const]);
                }

                $method->addBody('\assert(\in_array($?, ?, true));', [$parameter->name, \array_values($constants)]);
                $method->addBody('');
            }

            if ($feature->supportConstructorPropertyPromotion()) {
                $param = $method->addPromotedParameter($parameter->name)
                    ->setPrivate();
            } else {
                $param = $method->addParameter($parameter->name);
                $property = $class->addProperty($parameter->name)
                    ->setPrivate()
                    ->setNullable(! $parameter->required && \is_null($schema->default));

                if ($paramType) {
                    if ($feature->supportTypedProperties()) {
                        $property->setType($paramType);
                    } else {
                        $property->addComment('@var '.$paramType);
                    }
                }

                $method->addBody('$this->? = $?;', [$parameter->name, $parameter->name]);
            }

            if ($paramType) {
                $param->setType($paramType);
            }

            $param->setNullable(! $parameter->required && \is_null($schema->default));

            $default = $constants[$schema->default] ?? $schema->default;

            if ($default) {
                $param->setDefaultValue($default);
            }

            if ($param->isNullable()) {
                $param->setDefaultValue(null);
            }

            // !Bug: invalid `getType`
            // $method->addComment(sprintf('@param  %s $%s  %s', $param->getType(), $param->getName(), $parameter->description));
        }
    }

    private function addEndpointMethod(ClassType $class): void
    {
        \preg_match_all('/{\K[^}]*(?=})/m', $this->path, $matches);

        $method = $class->addMethod('endpoint')
            ->setReturnType('string');

        if (! empty($matches[0])) {
            // $params = \array_filter($spec->parameters, static fn (Parameter $param): bool => $param->in === 'path');

            $search = \array_map(static fn (string $s): string => '{'.$s.'}', $matches[0]);
            $replace = \array_fill(0, \count($matches[0]), '%s');
            $params = \array_map(static fn (string $s) => new Literal('$this->?', [$s]), $matches[0]);

            $endpoint = Str::of($this->path)->replace($search, $replace);
            $method->addBody('return sprintf(?, ...?:);', [(string) $endpoint, $params]);
        } else {
            $method->addBody('return ?;', [$this->path]);
        }
    }

    private function addVerbMethod(ClassType $class): void
    {
        $method = $class->addMethod('method');
        $method->setReturnType('string')
            ->addBody('return ?;', [\mb_strtoupper($this->method)]);
    }

    private function addQueryMethod(ClassType $class): void
    {
        $parameters = $this->parameters()->inQuery();

        if ($parameters->isEmpty()) {
            return;
        }

        $query = $parameters->mapWithKeys(static fn (Parameter $param) => [$param->name => new Literal('$this->?', [$param->name])]);

        $dumper = new Dumper();
        $method = $class->addMethod('defaultQuery');
        $method->setReturnType('array')
            ->setProtected()
            ->addBody('return \array_filter(?);', [$query->toArray()]);
    }
}
