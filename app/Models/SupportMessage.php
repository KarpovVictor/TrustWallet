<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'is_from_admin',
        'is_read',
        'attachment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_from_admin' => 'boolean',
        'is_read' => 'boolean',
    ];

    /**
     * Get the ticket that owns the message.
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the user that owns the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the message is from the admin.
     *
     * @return bool
     */
    public function isFromAdmin()
    {
        return $this->is_from_admin;
    }

    /**
     * Get a formatted date for the message creation.
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->created_at->format('d.m.Y H:i');
    }

    /**
     * Get the time of the message in H:i format.
     *
     * @return string
     */
    public function getTimeAttribute()
    {
        return $this->created_at->format('H:i');
    }
}