<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitorDocument extends Model
{
    use HasFactory;

    protected $fillable = ['visitor_id', 'type', 'file_path', 'notes'];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
