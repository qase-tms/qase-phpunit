<?php

declare(strict_types=1);

namespace Qase\PHPUnitReporter;

/**
 * Turns a data provider argument into the string Qase stores as a case parameter value.
 */
class ParameterValue
{
    /**
     * A value the reporter replaces with the data set name: it has no stable string form of its own.
     */
    public const OPAQUE = '{}';

    /**
     * Longer values (a model as JSON, a big array) are cut: Qase keeps every value a parameter ever had.
     */
    public const MAX_LENGTH = 500;

    public static function toString(mixed $value): string
    {
        $string = self::convert($value);

        // The `u` flag keeps a multibyte character whole; on invalid UTF-8 preg_replace() returns null.
        return preg_replace('/^(.{' . self::MAX_LENGTH . '}).+$/su', '$1...', $string) ?? $string;
    }

    /**
     * The Qase API rejects an empty parameter value, so `false` and blank scalars become words.
     */
    private static function convert(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            $str = (string)$value;
            return trim($str) === '' ? 'empty' : $str;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if ($value instanceof \Throwable) {
            // __toString() carries the stack trace: file paths and line numbers that change with every run.
            $message = $value->getMessage();
            return $message === '' ? get_class($value) : get_class($value) . ': ' . $message;
        }

        if ($value instanceof \Mockery\MockInterface || $value instanceof \PHPUnit\Framework\MockObject\Stub) {
            // A mock's string form is its generated class name plus an object hash that is new in every process.
            return 'mock of ' . self::mockedType($value);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string)$value;
            }
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return 'empty';
    }

    /**
     * A provider that reads the clock, Faker or a random source gives a new value on every call. Such a
     * parameter would be new on every run, so it takes the data set name instead.
     *
     * @param array<string, string> $firstRun
     * @param array<string, string> $secondRun
     * @return array<string, string>
     */
    public static function stableOnly(array $firstRun, array $secondRun): array
    {
        foreach ($firstRun as $name => $value) {
            if (($secondRun[$name] ?? null) !== $value) {
                $firstRun[$name] = self::OPAQUE;
            }
        }

        return $firstRun;
    }

    private static function mockedType(object $mock): string
    {
        $parent = get_parent_class($mock);
        if ($parent !== false && !str_starts_with($parent, 'Mockery\\')) {
            return $parent;
        }

        // An interface mock: drop what the mocking library adds (Mockery's __toString() brings Stringable),
        // then keep the most specific interface.
        $own = array_filter(
            class_implements($mock),
            static fn (string $i): bool => $i !== \Stringable::class
                && !str_starts_with($i, 'Mockery\\')
                && !str_starts_with($i, 'PHPUnit\\'),
        );
        foreach ($own as $candidate) {
            foreach ($own as $other) {
                if ($other !== $candidate && is_subclass_of($other, $candidate)) {
                    continue 2;
                }
            }

            return $candidate;
        }

        return get_class($mock);
    }
}
