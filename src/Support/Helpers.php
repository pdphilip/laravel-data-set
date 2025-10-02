<?php

namespace PDPhilip\DataSet\Support;

use BackedEnum;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use UnitEnum;

final class Helpers
{
    public static function liftDots(array $dottedArray): array
    {
        $lifted = [];
        foreach ($dottedArray as $k => $v) {
            if (preg_match('/\.\d+\./', $k)) {
                $clean = preg_replace('/\.\d+\./', '.', $k);
                if (empty($lifted[$clean])) {
                    $lifted[$clean] = [];
                }
                $lifted[$clean][] = $v;
                unset($dottedArray[$k]);
            }
        }

        return $dottedArray + $lifted;
    }

    /**
     * @template TValue
     * @template TDefault
     * *
     * @param  TValue  $value
     * @param  TDefault|callable(TValue): TDefault  $default
     * @return ($value is empty ? TDefault : mixed)
     */
    public static function enumValue($value, $default = null)
    {
        return match (true) {
            $value instanceof BackedEnum => $value->value,
            $value instanceof UnitEnum => $value->name,

            default => $value ?? value($default),
        };
    }

    public static function ensureIndexedArray(mixed $input): array
    {
        if (! $input) {
            return [];
        }

        if ($input instanceof Collection) {
            return $input->toArray();
        }

        if (empty($input[0])) {
            $input = [$input];
        }

        return Arr::wrap($input);
    }
}
