<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Payment extends Model {
    protected $fillable = ['order_id','method','amount','status','card_holder_name','card_last_four','cheque_number','dd_number','transaction_reference','paid_at'];
    protected $casts = ['amount'=>'decimal:2','paid_at'=>'datetime'];
    public function order(){ return $this->belongsTo(Order::class); }
}
