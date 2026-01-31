<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\FinancialSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class FinancialSettingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:finance.settings.view', only: ['index']),
            new Middleware('permission:finance.settings.manage', only: ['update', 'getByGroup']),
        ];
    }

    /**
     * Display the financial settings page.
     */
    public function index()
    {
        $settings = FinancialSetting::all()->keyBy('setting_key')->map(function ($setting) {
            return [
                'value' => $this->castSettingValue($setting->setting_value, $setting->setting_type),
                'type' => $setting->setting_type,
                'group' => $setting->setting_group,
                'description' => $setting->description,
                'is_system' => $setting->is_system,
            ];
        });

        // Get accounts for dropdown selection
        $accounts = ChartOfAccount::active()
            ->postable()
            ->orderBy('account_code')
            ->get()
            ->map(fn($account) => [
                'id' => $account->id,
                'code' => $account->account_code,
                'name' => $account->account_name,
                'full_name' => $account->full_name,
                'type' => $account->account_type,
            ]);

        return Inertia::render('Finance/Settings/Index', [
            'settings' => $settings,
            'accounts' => $accounts,
            'settingGroups' => [
                'general' => 'General Settings',
                'journal' => 'Auto-Journal Settings',
                'tax' => 'Tax Settings',
                'accounts' => 'Default Account Mappings',
            ],
        ]);
    }

    /**
     * Update financial settings.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable',
        ]);

        foreach ($validated['settings'] as $key => $value) {
            $setting = FinancialSetting::where('setting_key', $key)->first();

            if ($setting && !$setting->is_system) {
                $serializedValue = $this->serializeSettingValue($value, $setting->setting_type);
                $setting->update(['setting_value' => $serializedValue]);
            }
        }

        // Clear cache
        FinancialSetting::clearCache();

        return back()->with('success', 'Financial settings updated successfully.');
    }

    /**
     * Get settings by group (API endpoint).
     */
    public function getByGroup(string $group)
    {
        $settings = FinancialSetting::where('setting_group', $group)->get();

        return response()->json([
            'settings' => $settings->mapWithKeys(function ($setting) {
                return [
                    $setting->setting_key => $this->castSettingValue(
                        $setting->setting_value,
                        $setting->setting_type
                    ),
                ];
            }),
        ]);
    }

    /**
     * Cast setting value based on type.
     */
    private function castSettingValue(?string $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return match ($type) {
                'boolean' => false,
                'integer' => null,
                'decimal' => null,
                'json' => [],
                default => '',
            };
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'json' => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Serialize setting value for storage.
     */
    private function serializeSettingValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }
}
