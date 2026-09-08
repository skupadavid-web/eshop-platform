<?php

declare(strict_types=1);

namespace App\Migration;

use Symfony\Component\String\Slugger\AsciiSlugger;

final class Slug
{
    private static ?AsciiSlugger $slugger = null;

    public static function make(string $text): string
    {
        self::$slugger ??= new AsciiSlugger('cs');

        return strtolower((string) self::$slugger->slug($text));
    }

    /** Turn an old alias_varianta like "fotbalovy_dres_zeleny_detail-1199-14802" into a clean slug. */
    public static function fromLegacyAlias(string $alias): string
    {
        $alias = preg_replace('/_detail-[0-9-]+$/', '', $alias) ?? $alias;
        $alias = str_replace('_', '-', $alias);

        return trim(preg_replace('/[^a-z0-9-]+/i', '-', strtolower($alias)) ?? $alias, '-');
    }
}
