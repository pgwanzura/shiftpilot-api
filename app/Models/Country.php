<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['code', 'name'];

    public $timestamps = false;

    public static function getList()
    {
        return static::pluck('name', 'code')->toArray();
    }

    public static function getName($code)
    {
        return static::where('code', $code)->value('name');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'country', 'code');
    }
}
