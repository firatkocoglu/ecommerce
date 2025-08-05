<?php

namespace App;

enum ShipmentCarrier: string
{
    case Aras = 'aras';
    case MNG = 'mng';
    case Yurtici = 'yurtici';
    case PTT = 'ptt';
    case Surat = 'surat';

    public function label(): string{
        return match ($this) {
            self::Aras => 'Aras Kargo',
            self::MNG => 'MNG Kargo',
            self::Yurtici => 'Yurtiçi Kargo',
            self::PTT => 'PTT Kargo',
            self::Surat => 'Sürat Kargo',
        };
    }
}
