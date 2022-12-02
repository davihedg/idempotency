<?php

namespace Bvtterfly\Replay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReplayRequest
{
    public static function signature(Request $request): string
    {
        $hashAlgo = config('replay.signature_hash_algo');

        $bodyParam = $request->all();
        ksort($bodyParam);

        return hash($hashAlgo, json_encode(
            [
                $request->path(),
                $bodyParam
            ]
        ));
    }
}
