<?php

namespace App\Src\music\song\Model;

use App\Models\MainModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Song extends MainModel
{
    use HasFactory;

    public const DOMAIN_ID  = 1001;
    public const SERVICE_ID = 1001;
    public const ENTITY_ID  = 1001;

    public const DOMAIN     = 'music';
    public const SERVICE    = 'song';
    public const ENTITY     = 'Song';
    public const COMPONENT  = 'M';

    protected $table = 'songs';

    protected $fillable = [
        'title',
        'artist',
        'duration',
        'release_date',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    protected $casts = [
        'release_date' => 'date',
    ];
}
