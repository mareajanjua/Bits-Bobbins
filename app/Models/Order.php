<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    protected $fillable = ['customer_id','total_amount','status','delivery_type','payment_status','dispatched_at','delivered_at','shipping_name','shipping_phone','shipping_address','return_reason','return_requested_at','ordered_at'];
    protected $casts = ['total_amount'=>'decimal:2','dispatched_at'=>'datetime','delivered_at'=>'datetime','return_requested_at'=>'datetime','ordered_at'=>'datetime'];
    public function customer(){ return $this->belongsTo(User::class, 'customer_id'); }
    public function items(){ return $this->hasMany(OrderItem::class); }
    public function payment(){ return $this->hasOne(Payment::class); }
}
