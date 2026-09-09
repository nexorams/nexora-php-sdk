<?php

declare(strict_types=1);

namespace Nexora\Sdk\Support;

final class Arr
{
    /**
     * Filter out null values from an array, keeping explicit false, empty string, and zero values.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public static function filterNulls(array $array): array
    {
        return array_filter($array, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Convert snake_case keys in a dictionary to camelCase keys for API payload compatibility.
     *
     * @param array<string, mixed> $array
     * @return array<string, mixed>
     */
    public static function camelizeKeys(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $camelKey = lcfirst(str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', (string) $key))));
            if (is_array($value)) {
                $result[$camelKey] = self::camelizeKeys($value);
            } else {
                $result[$camelKey] = $value;
            }
        }
        return $result;
    }
}
