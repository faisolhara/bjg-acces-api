<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccessControl extends Model
{
    protected $table = 'apps.bjg_access_control';
    public $timestamps     = false;

    protected $primaryKey  = 'bjg_access_control_id';
}
