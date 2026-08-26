<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    public function record(
        User $actor,
        string $action,
        Model $subject,
        string $description,
        array $properties = []
    ): ActivityLog {
        $activity = new ActivityLog([
            'actor_id' => $actor->id,
            'action' => $action,
            'description' => $description,
            'properties' => $properties ?: null,
        ]);

        $activity->subject()->associate(
            $subject
        );

        $activity->save();

        return $activity;
    }
}