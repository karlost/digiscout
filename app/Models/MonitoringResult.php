<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class MonitoringResult extends Model
{
    use CrudTrait, HasFactory;

    protected $fillable = [
        'website_id',
        'monitoring_tool_id',
        'status',
        'value',
        'check_time',
        'additional_data',
    ];

    protected $casts = [
        'check_time' => 'datetime',
        'value' => 'float',
        'additional_data' => 'array',
    ];

    /**
     * Get the website that owns this monitoring result.
     */
    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class);
    }

    /**
     * Get the monitoring tool that owns this monitoring result.
     */
    public function monitoringTool(): BelongsTo
    {
        return $this->belongsTo(MonitoringTool::class);
    }
}
