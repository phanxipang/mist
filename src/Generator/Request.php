<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\Reference;
use cebe\openapi\spec\Schema;
use Fansipan\Mist\ParameterType;
use Fansipan\Mist\ValueObject\Config;
use Fansipan\Mist\ValueObject\GeneratedFile;
use Fansipan\Mist\ValueObject\ParameterCollection;
use Fansipan\Request as AbstractRequest;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Dumper;
use Nette\PhpGenerator\Literal;

final class Request implements GeneratorInterface
{
    use GeneratorTrait;

    private string $namespace = 'Request';

    private ParameterCollection $parameters;

    public function __construct(
        private readonly Operation $spec,
        private readonly string $method,
        private readonly string $path,
    ) {
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

        $this->addConstructorParameters($class);
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

    private function addConstructorParameters(ClassType $class): void
    {
        if ($this->parameters()->isEmpty()) {
            return;
        }

        $method = $class->addMethod('__construct');

        foreach ($this->parameters()->sortByDesc('required') as $parameter) {
            $property = $method->addPromotedParameter($parameter->name)
                ->setNullable(! $parameter->required)
                ->setPrivate();

            $schema = $parameter->schema instanceof Reference
                ? $parameter->schema->resolve()
                : $parameter->schema;

            if ($schema instanceof Schema) {
                $paramType = (string) new ParameterType($schema);
                $property->setType($paramType);
            }
        }
    }

    private function addEndpointMethod(ClassType $class): void
    {
        \preg_match_all('/{\K[^}]*(?=})/m', $this->path, $matches);

        if (! empty($matches[0])) {
            // $params = \array_filter($spec->parameters, static fn (Parameter $param): bool => $param->in === 'path');

            $search = \array_map(static fn (string $s): string => '{'.$s.'}', $matches[0]);
            $replace = \array_fill(0, \count($matches[0]), '%s');
            $params = \array_map(static fn (string $s) => new Literal('$this->?', [$s]), $matches[0]);

            $endpoint = Str::of($this->path)->replace($search, $replace);
            $body = $this->literal('return sprintf(?, ...?:);', (string) $endpoint, $params);
        } else {
            $body = $this->literal('return ?;', $this->path);
        }

        $method = $class->addMethod('endpoint');
        $method->setReturnType('string')
            ->addBody($body);
    }

    private function addVerbMethod(ClassType $class): void
    {
        $method = $class->addMethod('method');
        $method->setReturnType('string')
            ->addBody($this->literal('return ?;', \mb_strtoupper($this->method)));
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
            ->addBody(sprintf('return \\array_filter(%s);', $dumper->dump($query->toArray())));
    }
}
