<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvoiceItem extends Model {
    protected $fillable = ['invoice_id', 'description', 'qty', 'price', 'total', 'type'];
}
