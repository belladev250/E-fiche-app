<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Visit extends Model {
    protected $fillable = ['patient_name', 'visit_id', 'status'];
    public function invoices() { return $this->hasMany(Invoice::class); }
}
