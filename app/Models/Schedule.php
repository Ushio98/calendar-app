<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Schedule extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'color',
        'memo',
        'workload_value',
        'workload_unit',
        'workload_mode',
        'start_date',
        'end_date',
        'all_day',
        'location',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    public const WORKLOAD_NONE = 'none';
    public const WORKLOAD_FIXED = 'fixed';
    public const WORKLOAD_DAILY = 'daily';

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['daily_workloads'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = ['workloads'];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'all_day' => 'boolean',
        'workload_value' => 'integer',
        'is_checked' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workloads(): HasMany
    {
        return $this->hasMany(ScheduleWorkload::class)->orderBy('date');
    }

    /**
     * 日ごとの作業量を { 'YYYY-MM-DD': 量 } の形で返す（「日ごとに設定」以外は空）
     */
    protected function dailyWorkloads(): Attribute
    {
        return Attribute::get(function () {
            if ($this->workload_mode !== self::WORKLOAD_DAILY) {
                return (object) [];
            }

            return (object) $this->workloads
                ->mapWithKeys(fn (ScheduleWorkload $workload) => [$workload->date->toDateString() => $workload->value])
                ->all();
        });
    }

    /**
     * 指定した日の作業量。作業量がない日は null
     */
    public function workloadOn(string $date): ?int
    {
        return match ($this->workload_mode) {
            self::WORKLOAD_FIXED => $this->workload_value,
            self::WORKLOAD_DAILY => $this->workloads->first(fn (ScheduleWorkload $workload) => $workload->date->toDateString() === $date)?->value,
            default => null,
        };
    }

    public function checks(): HasMany
    {
        return $this->hasMany(ScheduleCheck::class);
    }
}
