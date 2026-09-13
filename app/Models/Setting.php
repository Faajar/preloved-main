<?php

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['listing_fee_enabled', 'boost_fee_enabled', 'listing_fee', 'boost_fee', 'boost_duration_hours'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'listing_fee_enabled' => 'boolean',
            'boost_fee_enabled' => 'boolean',
            'listing_fee' => 'integer',
            'boost_fee' => 'integer',
            'boost_duration_hours' => 'integer',
        ];
    }
}
