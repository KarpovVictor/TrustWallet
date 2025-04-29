<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportTicket extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'ticket_number',
        'subject',
        'status',
        'priority',
        'last_reply_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'last_reply_at' => 'datetime',
    ];

    /**
     * Get the user that owns the ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the messages for the ticket.
     */
    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    /**
     * Check if the ticket is open.
     *
     * @return bool
     */
    public function isOpen()
    {
        return $this->status === 'open';
    }

    /**
     * Get a formatted date for the ticket creation.
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Get a formatted date for the last reply.
     *
     * @return string
     */
    public function getFormattedLastReplyAtAttribute()
    {
        return $this->last_reply_at ? $this->last_reply_at->format('d.m.Y H:i') : '-';
    }
}