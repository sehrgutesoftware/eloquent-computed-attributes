<?php

namespace Tests\Unit;

use SehrGut\EloquentComputedAttributes\HasComputedAttributes;

class HasComputedAttributesMock
{
    use HasComputedAttributes;

    public static $savingArgument;

    public static function saving($argument)
    {
        static::$savingArgument = $argument;
    }
}
