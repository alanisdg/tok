<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tok extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'user_id',
        'tok_category_id',
    ];

    /**
     * Owner user of the Tok.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Optional category of the Tok.
     */
    public function category()
    {
        return $this->belongsTo(TokCategory::class, 'tok_category_id');
    }
}
