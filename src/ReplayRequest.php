<?php

namespace Bvtterfly\Replay;

use Illuminate\Http\Request;

class ReplayRequest
{
    public function validatedSignature(Request $request): bool
    {
        return $request->header(config('replay.header_name')) == $this->signature($request);
    }

    public static function signature(Request $request): string
    {
        $hashAlgo = config('replay.signature_hash_algo');

        $bodyParam = $request->all();
        ksort($bodyParam);

        return hash($hashAlgo, json_encode(
            [
                $request->ip(),
                $request->path(),
                $bodyParam
            ]
        ));
    }
}
