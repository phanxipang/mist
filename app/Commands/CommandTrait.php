<?php

declare(strict_types=1);

namespace App\Commands;

use Assert\AssertionFailedException;
use Illuminate\Contracts\Support\Renderable;
use Symfony\Component\Console\Output\OutputInterface;

use function Termwind\render;

trait CommandTrait
{
    protected function render(string|\Stringable|Renderable $output, int $options = OutputInterface::OUTPUT_NORMAL): void
    {
        if ($output instanceof Renderable) {
            render($output->render(), $options);
        } else {
            render((string) $output, $options);
        }
    }

    /**
     * @return \Closure(mixed): ?string
     */
    protected static function assert(callable $assertion): \Closure
    {
        return static function (mixed $value) use ($assertion) {
            try {
                $assertion($value);

                return null;
            } catch (AssertionFailedException $e) {
                return $e->getMessage();
            }
        };
    }
}
