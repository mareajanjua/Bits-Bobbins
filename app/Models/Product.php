<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    use HasFactory;
    protected $fillable = ['category_id','product_number','product_code','name','description','image','price','stock','warranty_days'];
    protected $casts = ['price'=>'decimal:2'];
    public function category(){ return $this->belongsTo(Category::class); }
    public function orderItems(){ return $this->hasMany(OrderItem::class); }

    public static function makeProductCode(string $categoryCode, string $productNumber): string
    {
        return str_pad($categoryCode, 2, '0', STR_PAD_LEFT) . str_pad($productNumber, 5, '0', STR_PAD_LEFT);
    }
}
