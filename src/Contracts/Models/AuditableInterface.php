<?php

namespace Bu\Server\Contracts\Models;

interface AuditableInterface
{
    /**
     * Get the audit logs for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function auditLogs();

    /**
     * Record an audit log entry for this model.
     *
     * @param string $action
     * @param array $oldValues
     * @param array $newValues
     * @param int|null $userId
     * @return \Bu\Server\Models\AuditLog
     */
    public function recordAudit(string $action, array $oldValues, array $newValues, ?int $userId = null);
}
