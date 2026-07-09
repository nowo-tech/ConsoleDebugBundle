<?php

declare(strict_types=1);

namespace Nowo\ConsoleDebugBundle\Tests\Unit\Serializer;

use DateTimeImmutable;
use JsonSerializable;
use Nowo\ConsoleDebugBundle\Serializer\DebugValueNormalizer;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Stringable;

use function is_array;

final class DebugValueNormalizerTest extends TestCase
{
    private DebugValueNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new DebugValueNormalizer();
    }

    public function testNormalizesScalarsAndArrays(): void
    {
        self::assertSame('hello', $this->normalizer->normalize('hello'));
        self::assertSame(['a' => 1, 'b' => ['c' => true]], $this->normalizer->normalize(['a' => 1, 'b' => ['c' => true]]));
    }

    public function testNormalizesDateTimeAndThrowable(): void
    {
        $date = new DateTimeImmutable('2026-07-09T12:00:00+00:00');
        self::assertSame('2026-07-09T12:00:00+00:00', $this->normalizer->normalize($date));

        $exception  = new RuntimeException('boom', 42);
        $normalized = $this->normalizer->normalize($exception);
        self::assertSame(RuntimeException::class, $normalized['exception']);
        self::assertSame('boom', $normalized['message']);
    }

    public function testNormalizesGenericObjectAndStringable(): void
    {
        $object     = new stdClass();
        $normalized = $this->normalizer->normalize($object);

        self::assertSame('stdClass', $normalized['object']);
        self::assertArrayHasKey('hash', $normalized);

        $stringable = new class implements Stringable {
            public function __toString(): string
            {
                return 'stringable-value';
            }
        };
        self::assertSame('stringable-value', $this->normalizer->normalize($stringable));
    }

    public function testNormalizesEnumsJsonSerializableAndResource(): void
    {
        $payload = new class implements JsonSerializable {
            /** @return array<string, bool> */
            public function jsonSerialize(): array
            {
                return ['ok' => true];
            }
        };
        self::assertSame(['ok' => true], $this->normalizer->normalize($payload));

        $resource = fopen('php://memory', 'r');
        self::assertNotFalse($resource);
        self::assertIsString($this->normalizer->normalize($resource));
        fclose($resource);
    }

    public function testNormalizesBackedAndUnitEnums(): void
    {
        self::assertSame(
            ['enum' => str_replace('\\', '/', DemoStatus::class), 'case' => 'Active'],
            $this->normalizer->normalize(DemoStatus::Active),
        );
        self::assertSame(
            ['enum' => str_replace('\\', '/', DemoPriority::class), 'value' => 'high'],
            $this->normalizer->normalize(DemoPriority::High),
        );
    }

    public function testNormalizesMaxDepth(): void
    {
        $nested  = ['level' => 1];
        $current = &$nested;
        for ($i = 0; $i < 12; ++$i) {
            $current['child'] = ['level' => $i];
            $current          = &$current['child'];
        }

        $normalized = $this->normalizer->normalize($nested);
        self::assertSame('[max depth reached]', $this->findMaxDepthLeaf($normalized));
    }

    private function findMaxDepthLeaf(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        foreach ($value as $child) {
            $leaf = $this->findMaxDepthLeaf($child);
            if ($leaf === '[max depth reached]') {
                return $leaf;
            }
        }

        return $value;
    }
}

enum DemoStatus
{
    case Active;
}

enum DemoPriority: string
{
    case High = 'high';
}
