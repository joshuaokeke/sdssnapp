<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'status',
        'facilitators',
        'speakers',
        'contact_name',
        'contact_phone_number',
        'facilitators',
        'created_by',
    ];


    // casts
    protected $casts = [
        'status' => 'boolean',
        'speakers' => 'array',
        'facilitators' => 'array',
    ];

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
