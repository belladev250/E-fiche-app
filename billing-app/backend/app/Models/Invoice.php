<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invoice extends Model {
    protected $fillable = ['visit_id', 'transaction_ref', 'total_amount', 'status'];
    public function visit() { return $this->belongsTo(Visit::class); }
    public function items() { return $this->hasMany(InvoiceItem::class); }
    public function payments() { return $this->hasMany(Payment::class); }
}
