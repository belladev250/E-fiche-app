<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable = ['invoice_id', 'amount', 'method', 'status', 'transaction_id', 'cashier_id'];
}
