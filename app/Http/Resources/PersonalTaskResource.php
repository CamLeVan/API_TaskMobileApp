<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PersonalTaskResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'deadline' => $this->deadline,
            'priority' => $this->priority,
            'status' => $this->status,
            'order' => $this->order,
            'labels' => $this->labels,
            'reminder_minutes_before' => $this->reminder_minutes_before,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}