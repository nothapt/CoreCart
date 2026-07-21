<?php
declare(strict_types=1);

namespace CoreCart\System\Engine;

class RequestMiddleware implements Middleware
{
    private int $maxBodySize;

    public function __construct(int $maxBodySize = 1048576)
    {
        $this->maxBodySize = $maxBodySize;
    }

    public function handle(Request $request, callable $next): Response
    {
        $contentLength = $request->getContentLength();
        if ($contentLength > $this->maxBodySize) {
            return new JsonResponse(['error' => 'Request body too large'], 413);
        }

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $contentType = $request->getContentType();
            if ($contentType !== null) {
                $ct = strtolower(trim(explode(';', $contentType)[0]));
                $allowed = ['application/json', 'application/x-www-form-urlencoded', 'multipart/form-data'];
                if ($ct !== '' && !in_array($ct, $allowed, true)) {
                    return new JsonResponse(['error' => 'Unsupported content type', 'allowed' => $allowed], 415);
                }
            }
        }

        return $next($request);
    }
}
