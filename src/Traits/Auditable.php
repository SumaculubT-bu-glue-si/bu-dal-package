<?php

namespace Bu\Server\Traits;

use Bu\Server\Models\AuditLog;

trait Auditable
{
    /**
     * Get the audit logs for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Record an audit log entry for this model.
     *
     * @param string $action
     * @param array $oldValues
     * @param array $newValues
     * @param int|null $userId
     * @return \Bu\Server\Models\AuditLog
     */
    public function recordAudit(string $action, array $oldValues, array $newValues, ?int $userId = null)
    {
        return AuditLog::create([
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'performed_by' => $userId ?? auth()->id(),
            'asset_id' => $this->id
        ]);
    }

    /**
     * Boot the Auditable trait.
     *
     * @return void
     */
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->recordAudit('created', [], $model->getAttributes());
        });

        static::updated(function ($model) {
            $model->recordAudit(
                'updated',
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        static::deleted(function ($model) {
            $model->recordAudit('deleted', $model->getAttributes(), []);
        });
    }
}
