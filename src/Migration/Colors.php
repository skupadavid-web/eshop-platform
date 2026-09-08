<?php

declare(strict_types=1);

namespace App\Migration;

final class Colors
{
    private const MAP = [
        'černá' => '#1d1c1a', 'čierna' => '#1d1c1a', 'bílá' => '#f2efe9', 'biela' => '#f2efe9',
        'červená' => '#c0392b', 'modrá' => '#2456c9', 'královská modrá' => '#1e3a8a',
        'tmavě modrá' => '#1e2a52', 'tmavomodrá' => '#1e2a52', 'námořnická' => '#1e2a52',
        'oranžová' => '#e07b28', 'purpurová' => '#8e3a80', 'růžová' => '#d96a9a',
        'šedá' => '#9b948a', 'sivá' => '#9b948a', 'grafitová' => '#45433e', 'antracit' => '#3a3a3a',
        'zelená' => '#3a8f4f', 'lahvově zelená' => '#1f5133', 'střední zelená' => '#3a8f4f',
        'khaki' => '#8a7d58', 'army' => '#5a5733', 'žlutá' => '#e6c141', 'žltá' => '#e6c141',
        'tyrkysová' => '#1aa8a0', 'bordó' => '#6b1f2b', 'vínová' => '#6b1f2b', 'hnědá' => '#6b4a2f',
        'fialová' => '#7a3f9d',
    ];

    public static function hex(string $name): ?string
    {
        $key = mb_strtolower(trim($name));

        return self::MAP[$key] ?? null;
    }
}
