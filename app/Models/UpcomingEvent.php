<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;


class UpcomingEvent extends Model
{
    // soft delete
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'banner_id',
        'title', // name on the resources
        'description', // description
        'venue',
        'registration_link',
        'start_time',
        'start_date',
        'end_time',
        'end_date',
        'category',
        'status', // draft, published
        'facilitators',
        'speakers',
        'contact_name',
        'contact_phone_number',
        'facilitators',
        'created_by',
    ];


    // casts
    protected $casts = [
        'speakers' => 'array',
        'facilitators' => 'array',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];


    protected function startTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('H:i') : null,
            set: fn($value) => $value ? Carbon::parse($value)->format('H:i:s') : null,
        );
    }

    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('H:i') : null,
            set: fn($value) => $value ? Carbon::parse($value)->format('H:i:s') : null,
        );
    }

    protected function endDate(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
            set: fn($value) => $value ? Carbon::parse($value)->format('Y-m-d') : null,
        );
    }
    // banner
    public function banner()
    {
        return $this->belongsTo(Assets::class, 'banner_id');
    }

    // created by
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
