<?php
/* Controller <SongController> - Ajax API Exposition
** Version 2024-10-01
** Copyright VAERDIA - All rights reserved
*/

namespace App\Src\music\song\Ajax;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Core\ApiCore;
use App\Src\music\song\Service\SongService;

class SongController extends ApiCore
{
    //::::::::::::::::::::::::::::::::::::::::::::

    public const DOMAIN_ID  = 1001;
    public const SERVICE_ID = 1001;
    public const ENTITY_ID  = 1001;

    public const DOMAIN     = 'music';
    public const SERVICE    = 'song';
    public const ENTITY     = 'Song';
    public const COMPONENT  = 'C';

    protected $targetService;

    public function __construct(SongService $targetService)
    {
        //......................................................

        $this->targetService = $targetService;
    }
}
