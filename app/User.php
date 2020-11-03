<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    protected $table = 'user';

    use Authenticatable, Authorizable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'openid', 'nickname', 'avatar', 'gender', 'birthday', 'mobile', 'city', 'province', 'country', 'amount', 'member_token','status'
    ];
    public $timestamps = false;

}

