<?php

declare(strict_types=1);

namespace Bvtterfly\Replay;

use Bvtterfly\Replay\Contracts\Policy;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Replay
{
    public function __construct(
        private Policy $policy,
        private Storage $storage,
        protected ReplayRequest $replayRequest
    ) {
    }

    public function handle(Request $request, Closure $next, ?string $cachePrefix = null): Response
    {
        if (! config('replay.enabled')
            || ! $this->policy->isIdempotentRequest($request)) {
            return $next($request);
        }

        $key = $this->getCacheKey($request, $cachePrefix);

        if ($recordedResponse = ReplayResponse::find($key)) {
            return $recordedResponse->toResponse($this->replayRequest->signature($request));
        }

        $lock = $this->checkResponseInProgress($key);

        try {
            $response = $next($request);
            if ($this->policy->isRecordableResponse($response)) {
                ReplayResponse::save($key, $this->replayRequest->signature($request), $response);
            }
            return $response;
        } finally {
            $lock->release();
        }
    }

    private function getCacheKey(Request $request, ?string $prefix = null): string
    {
        $idempotencyKey = $this->getIdempotencyKey($request);

        return $prefix ? "$prefix:$idempotencyKey" : $idempotencyKey;
    }

    private function getIdempotencyKey(Request $request): string
    {
        return $request->header(config('replay.header_name'));
    }

    protected function checkResponseInProgress(string $key): Lock
    {
        $lock = $this->storage->lock($key);
        if (! $lock->get()) {
            abort(Response::HTTP_CONFLICT, __('replay::responses.error_messages.already_in_progress'));
        }

        return $lock;
    }
}
