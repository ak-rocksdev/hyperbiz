<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;

class CompanySetting extends Model
{
    use BelongsToCompany;
    protected $table = 'company_settings';

    protected $fillable = [
        'company_id',
        'setting_key',
        'setting_value',
        'description',
    ];

    /**
     * Company this setting belongs to.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get a setting value for a company.
     */
    public static function getValue(int $companyId, string $key, $default = null)
    {
        $setting = static::where('company_id', $companyId)
            ->where('setting_key', $key)
            ->first();

        return $setting ? $setting->setting_value : $default;
    }

    /**
     * Set a setting value for a company.
     */
    public static function setValue(int $companyId, string $key, $value, $description = null)
    {
        return static::updateOrCreate(
            ['company_id' => $companyId, 'setting_key' => $key],
            ['setting_value' => $value, 'description' => $description]
        );
    }

    /**
     * Get all settings for a company as key-value array.
     */
    public static function getAllForCompany(int $companyId): array
    {
        return static::where('company_id', $companyId)
            ->pluck('setting_value', 'setting_key')
            ->toArray();
    }
}
