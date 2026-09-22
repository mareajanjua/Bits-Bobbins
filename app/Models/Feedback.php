<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Feedback extends Model {
    protected $table = 'feedback';
    protected $fillable = ['customer_id','name','email','message','rating'];
    public function customer(){ return $this->belongsTo(User::class, 'customer_id'); }
}
