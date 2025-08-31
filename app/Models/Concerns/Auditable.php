<?php

namespace App\Models\Concerns;

use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::creating(function ($model): void {
            $user = Auth::user();
            $userId = $user?->id;

            $schema = self::schemaFor($model);
            $table = $model->getTable();

            if ($userId) {
                if (self::hasColumn($schema, $table, 'user_id') && empty($model->user_id)) {
                    $model->user_id = $userId;
                }
                if (self::hasColumn($schema, $table, 'team_id') && empty($model->team_id)) {
                    $teamId = $user->current_team_id ?? null;
                    if ($teamId) {
                        $model->team_id = $teamId;
                    }
                }
                if (self::hasColumn($schema, $table, 'created_by') && empty($model->created_by)) {
                    $model->created_by = $userId;
                }
                if (self::hasColumn($schema, $table, 'updated_by') && empty($model->updated_by)) {
                    $model->updated_by = $userId;
                }
            }

            $now = time();
            // Standard checksum field names
            if (self::hasColumn($schema, $table, 'created_checksum') && empty($model->created_checksum)) {
                $model->created_checksum = hash('sha256', $table.'|created|'.$now.'|'.spl_object_id($model));
            }
            if (self::hasColumn($schema, $table, 'updated_checksum') && empty($model->updated_checksum)) {
                $model->updated_checksum = hash('sha256', $table.'|updated|'.$now.'|'.spl_object_id($model));
            }
            // Alternative checksum field names
            if (self::hasColumn($schema, $table, 'checksum_created') && empty($model->checksum_created)) {
                $model->checksum_created = hash('sha256', $table.'|created|'.$now.'|'.spl_object_id($model));
            }
            if (self::hasColumn($schema, $table, 'checksum_updated') && empty($model->checksum_updated)) {
                $model->checksum_updated = hash('sha256', $table.'|updated|'.$now.'|'.spl_object_id($model));
            }
        });

        static::updating(function ($model): void {
            $userId = Auth::id();

            $schema = self::schemaFor($model);
            $table = $model->getTable();

            if ($userId && self::hasColumn($schema, $table, 'updated_by')) {
                $model->updated_by = $userId;
            }

            $now = time();
            if (self::hasColumn($schema, $table, 'updated_checksum')) {
                $model->updated_checksum = hash('sha256', $table.'|updated|'.$now.'|'.($model->getKey() ?? spl_object_id($model)));
            }
            if (self::hasColumn($schema, $table, 'checksum_updated')) {
                $model->checksum_updated = hash('sha256', $table.'|updated|'.$now.'|'.($model->getKey() ?? spl_object_id($model)));
            }
        });
    }

    private static function schemaFor($model): SchemaBuilder
    {
        $connection = $model->getConnectionName();

        return DB::connection($connection)->getSchemaBuilder();
    }

    private static function hasColumn(SchemaBuilder $schema, string $table, string $column): bool
    {
        try {
            return $schema->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
