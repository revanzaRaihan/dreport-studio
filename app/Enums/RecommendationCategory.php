<?php

namespace App\Enums;

enum RecommendationCategory: string
{
    case KREATIVITAS = 'kreativitas';
    case LOGIKA_TERSTRUKTUR = 'logika_terstruktur';
    case EKSPERIMEN = 'eksperimen';
    case CODING_DASAR = 'coding_dasar';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
