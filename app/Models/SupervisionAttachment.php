<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisionAttachment extends Model
{
    protected $fillable = ['supervision_id', 'kind', 'file_path', 'file_name', 'mime_type', 'size', 'uploaded_by'];

    public function supervision()
    {
        return $this->belongsTo(Supervision::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return \Storage::disk('public')->url($this->file_path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
