<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class WebhookEvent extends Model {
    protected $fillable = ['event_id', 'payload', 'status'];
    protected $casts = ['payload' => 'array'];
}
