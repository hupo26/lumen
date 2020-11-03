<?

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class Product extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;
    protected $table = 'user_products';
    protected $fillable = [
        'uid','title','product_type','product_content','product_img_vedio','vedio_first_img','vedio_time','product_amount','product_money','product_allmoney','product_label','product_label_title','uptype'
    ];
    public $timestamps = false;
}
