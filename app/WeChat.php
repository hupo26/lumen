<?

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

class WeChat extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;
    protected $table = 'wechat_access_token';
    protected $fillable = ['appid', 'secret', 'refresh_token', 'scope', 'access_token', 'expires_in', 'expires_time'];
    public $timestamps = false;
}
