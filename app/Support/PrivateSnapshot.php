<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PrivateSnapshot
{
    /** Recompute current data on every request; reuse only the unchanged client rendering. */
    public static function response(Request $request, array $data): JsonResponse
    {
        $response = response()->json($data)->header('Cache-Control', 'private, no-store');
        $response->setEtag(hash('sha256', $response->getContent()));
        $response->isNotModified($request);

        return $response;
    }
}
