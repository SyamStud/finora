<?php

namespace App;

enum InputSource: string
{
    case Manual = 'manual';
    case WhatsApp = 'whatsapp';
    case Import = 'import';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
