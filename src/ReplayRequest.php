<?php

namespace Bvtterfly\Replay;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        $signature = json_encode(
            [
                $request->ip(),
                $request->path(),
                $bodyParam
            ]
        );
        $hash = hash($hashAlgo, json_encode(
            [
                $request->ip(),
                $request->path(),
                $bodyParam
            ]
        ));

        Log::info("request signature: $signature");
        Log::info("hash signature: $hash");

        return hash($hashAlgo, json_encode(
            [
                $request->ip(),
                $request->path(),
                $bodyParam
            ]
        ));
    }
}
