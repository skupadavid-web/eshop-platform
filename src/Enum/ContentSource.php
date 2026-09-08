<?php

declare(strict_types=1);

namespace App\Enum;

/** Provenance of a piece of copy — drives the SEO content workstream. */
enum ContentSource: string
{
    case Original = 'original';   // hand-written
    case Template = 'template';   // generic template with variables
    case Ai = 'ai';               // AI-generated
    case Improved = 'improved';   // reviewed / rewritten
}
