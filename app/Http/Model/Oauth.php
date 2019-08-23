<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Oauth extends Model
{
  /**
   * モデルと関連しているテーブル
   *
   * @var string
   */
  protected $table = 'oauth';
  
  /**
   * The attributes that are mass assignable.
   *
   * @var array
   */
  protected $fillable = [
      'tenant_id', 'token'
  ];

}
