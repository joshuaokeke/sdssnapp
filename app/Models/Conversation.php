<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sent_by',
        'sent_to',
        'subject',
        'message',
        'reason',
    ];

    protected $casts = [
        'sent_to' => 'array',
    ];


    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // get all the users by ids
    public function sentTo()
    {
        $userIds = $this->sent_to;
        $sentToUsers = User::whereIn('id', $userIds)->get();
        Log::info('Conversation - Sent To Users: ', [$sentToUsers]);
        return $sentToUsers;
    }
}
