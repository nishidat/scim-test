<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
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
      'scim_id','external_id', 'group_name'
  ];

}
