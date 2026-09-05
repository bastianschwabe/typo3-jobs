<?php

declare(strict_types=1);

namespace BastianSchwabe\Jobs\Enum;

/**
 * Where the "apply" proxy sent the visitor.
 *
 * FORM is reserved for an application form shipped with the extension itself;
 * nothing writes it yet.
 */
enum ApplyType: string
{
    case Url = 'URL';
    case Email = 'EMAIL';
    case Form = 'FORM';
}
