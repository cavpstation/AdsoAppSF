<?php

namespace Illuminate\Tests\Routing;

enum CategoryEnum
{
    case People;
    case Fruits;
}

enum CategoryBackedEnum: string
{
    case People = 'people';
    case Fruits = 'fruits';
}

enum CategoryBackedEnumWithKey: int
{
    case People = 1;
    case Fruits = 2;

    public function slug(): string
    {
        return match ($this) {
            self::People => 'people',
            self::Fruits => 'fruits',
        };
    }
}
