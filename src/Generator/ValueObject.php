<?php

declare(strict_types=1);

namespace Fansipan\Mist\Generator;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Type;
use Fansipan\Mist\Config\Config;
use Fansipan\Mist\GeneratedFile;
use Fansipan\Mist\ParameterType;
use Fansipan\Mist\PhpFeature;
use Illuminate\Support\Str;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;

final class ValueObject implements GeneratorInterface
{
    use GeneratorTrait;

    private readonly Schema $schema;

    public function __construct(
        private readonly string $name,
        Schema $schema
    ) {
        $this->schema = $this->resolveSpecRef($schema);
    }

    public function generate(Config $config): GeneratedFile
    {
        $file = $this->file();

        $namespace = $file->addNamespace($config->package->namespace);

        $class = $namespace->addClass($this->name)
            ->addImplement(\JsonSerializable::class);

        $method = $class->addMethod('__construct');

        $feature = new PhpFeature($config->package->minimumPhpVersion);
        $properties = $params = [];

        foreach ($this->schema->properties as $name => $schema) {
            $schema = $this->resolveSpecRef($schema);
            \assert($schema instanceof Schema);

            $propertyName = Str::camel($name);
            $params[Str::snake($name)] = new Literal('$this->?', [$propertyName]);

            $paramType = (string) ParameterType::fromSchema($schema);

            if ($paramType === Type::OBJECT) {
                $paramType = sprintf('\\%s\\%s', $namespace->getName(), \ucwords($name));
            }

            $constants = [];

            if (! empty($schema->enum)) {
                foreach ($schema->enum as $value) {
                    $class->addConstant(
                        $const = Str::of(sprintf('%s %s', $propertyName, $value))->snake()->upper()->toString(),
                        $value
                    );

                    $constants[$value] = new Literal('self::?', [$const]);
                }

                $method->addBody('\assert(\in_array($?, ?, true));', [$propertyName, \array_values($constants)]);
                $method->addBody('');
            }

            $param = $method->addParameter($propertyName);
            $property = $class->addProperty($propertyName)
                ->setPrivate();

            $method->addBody('$this->? = $?;', [$propertyName, $propertyName]);

            if ($paramType) {
                if ($feature->supportTypedProperties()) {
                    $property->setType($paramType);
                } else {
                    $property->addComment('@var '.$paramType);
                }

                $param->setType($paramType);
            }

            $default = $constants[$schema->default] ?? $schema->default;

            if ($default) {
                $param->setDefaultValue($default);
            }

            if ($param->isNullable()) {
                $param->setDefaultValue(null);
            }

            $this->addGetterAndSetter($class, $propertyName, $paramType);
        }

        $class->addMethod('toArray')
            ->setReturnType('array')
            ->setBody('return ?;', [$params])
            ->addComment('Get the object representation as an array.')
            ->addComment('')
            ->addComment('@return array<string, mixed>');

        $class->addMethod('jsonSerialize')
            ->addAttribute(\ReturnTypeWillChange::class)
            ->setBody('return $this->toArray();');

        return new GeneratedFile(
            [$config->output->directory, $config->output->paths->src, $this->name.'.php'],
            $this->print($file)
        );
    }

    private function addGetterAndSetter(ClassType $class, string $name, ?string $type = null): void
    {
        $method = Str::of($name)->studly();

        $class->addMethod((clone $method)->prepend('get')->toString())
            ->setReturnType($type)
            ->addBody('return $this->?;', [$name]);

        // $setter = $class->addMethod((clone $method)->prepend('set')->toString());
        // $param = $setter->addParameter($property->getName())
        //     ->setType($property->getType());

        // $setter->setReturnType('void')
        //     ->addBody('$this->? = $?;', [$property->getName(), $param->getName()]);
    }
}
