<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;
    /**
    * モデルと関連しているテーブル
    *
    * @var string
    */
    protected $table = 'groups';

    /**
    * The attributes that are mass assignable.
    *
    * @var array
    */
    protected $fillable = [
      'tenant_id', 'scim_id', 'external_id', 'group_name'
    ];

}
