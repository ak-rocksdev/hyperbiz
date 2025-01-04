<?php

namespace App\Traits;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait LogsSystemChanges
{
    /**
     * Boot the trait and register model events.
     */
    protected static function bootLogsSystemChanges()
    {
        static::created(function ($model) {
            $model->logSystemAction('created');
        });

        static::updated(function ($model) {
            $model->logSystemAction('updated', $model->getOriginal(), $model->getDirty());
        });

        static::deleted(function ($model) {
            $model->logSystemAction('deleted');
        });
    }

    /**
     * Log the action performed on the model.
     *
     * @param string $action
     * @param array $originalValues
     * @param array $updatedValues
     */
    public function logSystemAction(string $action, array $originalValues = [], array $updatedValues = [])
    {
        // Prevent logging for the SystemLog model itself
        if (get_class($this) === SystemLog::class) {
            return;
        }

        $changes = [];
        foreach ($updatedValues as $field => $newValue) {
            $changes[$field] = [
                'original' => $originalValues[$field] ?? null,
                'updated' => $newValue,
            ];
        }

        Log::info('LogSystemAction called:', [
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changed_fields' => $changes,
        ]);

        SystemLog::create([
            'model_type' => get_class($this),
            'model_id' => $this->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'changed_fields' => json_encode($changes),
            'ip_address' => request()->ip() ?? 'CLI',
            'user_agent' => request()->header('User-Agent') ?? 'CLI',
        ]);
    }
}
