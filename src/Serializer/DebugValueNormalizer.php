<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Serializer;

use BackedEnum;
use DateTimeInterface;
use JsonSerializable;
use LogicException;
use Throwable;
use UnitEnum;

use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_resource;
use function is_string;

/**
 * Converts PHP values into JSON-safe structures for browser console output.
 */
final class DebugValueNormalizer
{
    private const MAX_DEPTH = 8;

    public function normalize(mixed $value, int $depth = 0): mixed
    {
        if ($depth >= self::MAX_DEPTH) {
            return '[max depth reached]';
        }

        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalize($item, $depth + 1);
            }

            return $normalized;
        }

        if ($value instanceof JsonSerializable) {
            $serialized = $value->jsonSerialize();

            return $this->normalize($serialized, $depth + 1);
        }

        if ($value instanceof BackedEnum) {
            return [
                'enum'  => $this->normalizeClassName($value::class),
                'value' => $value->value,
            ];
        }

        if ($value instanceof UnitEnum) {
            return [
                'enum' => $this->normalizeClassName($value::class),
                'case' => $value->name,
            ];
        }

        if (is_object($value)) {
            if ($value instanceof Throwable) {
                return [
                    'exception' => $this->normalizeClassName($value::class),
                    'message'   => $value->getMessage(),
                    'code'      => $value->getCode(),
                    'file'      => $value->getFile(),
                    'line'      => $value->getLine(),
                ];
            }

            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            if ($value instanceof DateTimeInterface) {
                return $value->format(DateTimeInterface::ATOM);
            }

            return [
                'object' => $this->normalizeClassName($value::class),
                'hash'   => spl_object_hash($value),
            ];
        }

        if (is_resource($value)) {
            return '[resource ' . (get_resource_type($value) ?: 'unknown') . ']';
        }

        // @codeCoverageIgnoreStart
        throw new LogicException('Unhandled value type.');
        // @codeCoverageIgnoreEnd
    }

    private function normalizeClassName(string $class): string
    {
        return str_replace('\\', '/', $class);
    }

    /**
     * @param list<mixed> $values
     *
     * @return list<mixed>
     */
    public function normalizeMany(array $values): array
    {
        $normalized = [];
        foreach ($values as $value) {
            $normalized[] = $this->normalize($value);
        }

        return $normalized;
    }
}
