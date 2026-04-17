<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class File extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'original_name', 'extension', 'size', 'access', 'is_archived', 'mime_type', 'content'
    ];

    // Only allow certain previewable extensions
    public function isPreviewable()
    {
        return in_array(strtolower($this->extension), ['jpg','jpeg','png','gif','webp','svg']);
    }
}

?>