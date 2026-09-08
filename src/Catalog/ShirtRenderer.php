<?php

declare(strict_types=1);

namespace App\Catalog;

/**
 * Draws the placeholder t-shirt SVG used across the storefront until real product
 * photography is migrated (E3). Ported from the approved mockup.
 */
final class ShirtRenderer
{
    public function svg(string $hex, string $motif = 'heart', ?string $label = null): string
    {
        $isWhite = '#f2efe9' === $hex;
        $stroke = $isWhite ? '#d8d2c6' : 'rgba(0,0,0,.14)';
        $ink = \in_array($hex, ['#1d1c1a', '#1e2a52', '#45433e'], true) || 'code' === $motif ? '#f2efe9' : '#1d1c1a';
        if ($isWhite) {
            $ink = '#c0392b';
        }
        $alt = ('#1d1c1a' === $ink) ? '#f2efe9' : '#1d1c1a';

        $m = match ($motif) {
            'heart' => '<path d="M100 134 l-15 -15 a10 10 0 0 1 15 -13 a10 10 0 0 1 15 13 z" fill="'.$ink.'"/><text x="100" y="156" text-anchor="middle" font-family="Bricolage Grotesque,sans-serif" font-size="12" font-weight="700" fill="'.$ink.'">ODROVICE</text>',
            'cat' => '<g fill="'.$ink.'"><circle cx="100" cy="118" r="16"/><path d="M86 108 l-4 -12 l12 6 z"/><path d="M114 108 l4 -12 l-12 6 z"/></g><circle cx="94" cy="116" r="2" fill="'.$alt.'"/><circle cx="106" cy="116" r="2" fill="'.$alt.'"/>',
            'paw' => '<g fill="'.$ink.'"><circle cx="100" cy="124" r="11"/><circle cx="88" cy="112" r="4"/><circle cx="100" cy="107" r="4"/><circle cx="112" cy="112" r="4"/></g>',
            'code' => '<text x="100" y="116" text-anchor="middle" font-family="IBM Plex Mono,monospace" font-size="9" fill="'.$ink.'">it works</text><text x="100" y="130" text-anchor="middle" font-family="IBM Plex Mono,monospace" font-size="9" fill="'.$ink.'">on my machine</text>',
            'flag' => '<rect x="78" y="102" width="44" height="30" fill="#1e2a52"/><path d="M78 102 L122 132 M122 102 L78 132" stroke="#f2efe9" stroke-width="5"/><path d="M100 102 V132 M78 117 H122" stroke="#f2efe9" stroke-width="8"/><path d="M100 102 V132 M78 117 H122" stroke="#c0392b" stroke-width="4"/>',
            'blank' => '',
            'year' => '<text x="100" y="126" text-anchor="middle" font-family="Bricolage Grotesque,sans-serif" font-size="26" font-weight="700" fill="'.$ink.'">1985</text>',
            'number' => '<text x="100" y="130" text-anchor="middle" font-family="Bricolage Grotesque,sans-serif" font-size="34" font-weight="700" fill="'.$ink.'">10</text>',
            default => '<text x="100" y="122" text-anchor="middle" font-family="Bricolage Grotesque,sans-serif" font-size="13" font-weight="700" fill="'.$ink.'">NIC NEMUSÍM</text>',
        };

        $a11y = null !== $label
            ? 'role="img" aria-label="'.htmlspecialchars($label, \ENT_QUOTES).'"'
            : 'role="presentation" aria-hidden="true" focusable="false"';

        return '<svg viewBox="0 0 200 210" xmlns="http://www.w3.org/2000/svg" '.$a11y.'>'
            .'<path d="M62 34 L40 44 L30 74 L48 84 L54 68 L54 180 Q100 190 146 180 L146 68 L152 84 L170 74 L160 44 L138 34 Q119 52 100 52 Q81 52 62 34 Z" fill="'.$hex.'" stroke="'.$stroke.'" stroke-width="1.5"/>'
            .'<path d="M62 34 Q81 52 100 52 Q119 52 138 34 Q119 44 100 44 Q81 44 62 34 Z" fill="rgba(0,0,0,.06)"/>'.$m.'</svg>';
    }
}
