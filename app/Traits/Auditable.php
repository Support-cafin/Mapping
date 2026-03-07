<?php

namespace App\Traits;

use App\Services\AuditLogService;

trait Auditable
{
    /**
     * Boot the trait
     */
    public static function bootAuditable()
    {
        static::created(function ($model) {
            AuditLogService::logCreate(
                model: class_basename($model),
                data: $model->getAttributes(),
                modelId: $model->id,
                entrepriseId: $model->entreprise_id ?? null
            );
        });

        static::updated(function ($model) {
            // Ne pas logger les timestamps automatiques
            $changes = array_diff_key(
                $model->getChanges(),
                array_flip(['updated_at', 'created_at'])
            );

            if (!empty($changes)) {
                $original = array_intersect_key(
                    $model->getOriginal(),
                    $changes
                );

                AuditLogService::logUpdate(
                    model: class_basename($model),
                    modelId: $model->id,
                    oldValues: $original,
                    newValues: $changes,
                    entrepriseId: $model->entreprise_id ?? null
                );
            }
        });

        static::deleted(function ($model) {
            AuditLogService::logDelete(
                model: class_basename($model),
                modelId: $model->id,
                oldValues: $model->getOriginal(),
                entrepriseId: $model->entreprise_id ?? null
            );
        });
    }
}