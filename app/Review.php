<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    public function product()
    {
        return $this->belongsTo('App\Product');
    }
    public function user()
    {
        return $this->belongsTo('App\User');//たとえ100個のレビューがあっても、1つのレビューを主として考える。そうするとユーザーは1人しかいない＝belongsTo
    }
}
