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

    /**
     * The exact legacy URL path segment, preserved 1:1 for SEO. Only strips a leading
     * slash and surrounding whitespace and lower-cases — the old system's aliases are
     * already lower-case and Google has them indexed as-is (underscores, "_detail-N-M").
     */
    public static function legacyPath(string $alias): string
    {
        return strtolower(trim(ltrim(trim($alias), '/')));
    }

    /** Turn an old alias like "fotbalovy_dres_zeleny_detail-1199-14802" into a clean modern slug (future use). */
    public static function fromLegacyAlias(string $alias): string
    {
        $alias = preg_replace('/_detail-[0-9-]+$/', '', $alias) ?? $alias;
        $alias = str_replace('_', '-', $alias);

        return trim(preg_replace('/[^a-z0-9-]+/i', '-', strtolower($alias)) ?? $alias, '-');
    }
}
