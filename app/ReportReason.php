<?php

namespace App;

enum ReportReason: string
{
    case Fraud = 'fraud';
    case ProhibitedItem = 'prohibited_item';
    case FalseInformation = 'false_information';
    case Spam = 'spam';
    case InappropriatePhoto = 'inappropriate_photo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fraud => 'Penipuan',
            self::ProhibitedItem => 'Barang terlarang',
            self::FalseInformation => 'Informasi palsu',
            self::Spam => 'Spam',
            self::InappropriatePhoto => 'Foto tidak sesuai',
            self::Other => 'Pelanggaran lainnya',
        };
    }
}
