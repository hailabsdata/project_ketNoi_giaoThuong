<?php

namespace App\Support;

use Illuminate\Support\Str;

class Correlation
{
    protected static ?string $requestId = null;
    protected static ?string $correlationId = null;

    public static function ensure(): void
    {
        if (!self::$requestId) {
            self::$requestId = (string) Str::uuid();
        }
        if (!self::$correlationId) {
            self::$correlationId = self::$requestId;
        }
    }

    public static function set(?string $requestId = null, ?string $correlationId = null): void
    {
        if ($requestId)     self::$requestId     = $requestId;
        if ($correlationId) self::$correlationId = $correlationId;
        self::ensure();
    }

    public static function requestId(): string
    {
        self::ensure(); return self::$requestId;
    }

    public static function correlationId(): string
    {
        self::ensure(); return self::$correlationId;
    }
}
