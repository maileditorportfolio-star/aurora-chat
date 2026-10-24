<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FixCorruptedJson
{
    public function handle(Request $request, Closure $next)
    {
        $contentType = $request->header('Content-Type');

        file_put_contents(
            '/tmp/fixjson_debug.log',
            date('c') . " ENTER middleware contentType=$contentType\n",
            FILE_APPEND
        );

        if (!str_contains($contentType, 'application/json')) {
            return $next($request);
        }

        $rawBody = file_get_contents('php://input');

        file_put_contents(
            '/tmp/fixjson_debug.log',
            date('c') . " RAW_BODY=" . substr($rawBody, 0, 200) . "\n",
            FILE_APPEND
        );

        if ($rawBody && $this->isCorruptedJson($rawBody)) {
            file_put_contents(
                '/tmp/fixjson_debug.log',
                date('c') . " IS_CORRUPTED=true\n",
                FILE_APPEND
            );
            $fixedBody = $this->fixCorruptedJson($rawBody);
            $decoded = json_decode($fixedBody, true);

            file_put_contents(
                '/tmp/fixjson_debug.log',
                date('c') . " FIXED=" . substr($fixedBody, 0, 200) . " DECODED=" . json_encode($decoded) . "\n",
                FILE_APPEND
            );

            if (is_array($decoded)) {
                // Set the JSON payload directly so json() returns fixed data
                $request->setJson(new \Symfony\Component\HttpFoundation\InputBag($decoded));
                // Also fix the input source for validate()/all()
                $request->replace($decoded);
                // Set raw content so getContent() returns fixed body
                $request->server->set('RAW_BODY', $fixedBody);
            }
        } else {
            file_put_contents(
                '/tmp/fixjson_debug.log',
                date('c') . " IS_CORRUPTED=false\n",
                FILE_APPEND
            );
        }

        return $next($request);
    }

    private function isCorruptedJson(string $body): bool
    {
        return (bool) preg_match('/\\\\:/', $body) || (bool) preg_match('/\\\\,/', $body);
    }

    public function fixCorruptedJson(string $body): string
    {
        $fixed = $body;

        // 1. Fix escaped punctuation: \: -> :, \, -> ,, \} -> }
        $fixed = str_replace(['\:', '\,', '\}'], [':', ',', '}'], $fixed);

        // 2. Fix escaped quotes: \letter -> "letter (backslash before letter was originally a quote)
        //    This handles: \Test -> "Test, \test@example.com -> "test@example.com
        $fixed = preg_replace('/\\\\([a-zA-Z])/', '"$1', $fixed);

        // 3. Now we have something like: {" name:"Test User,"email:"test@example.com,"password:"password123}
        //    Need to fix the space after { and ensure keys are properly quoted

        // 3a. Remove space after { or {" -> {"
        $fixed = preg_replace('/(\{\s*"\s*)\s+/', '$1', $fixed);

        // 3b. Fix keys: ensure {key: or {"key: or { "key: all become {"key":
        //    Pattern: { or , followed by optional whitespace, optional quote, optional whitespace, key name, colon
        $fixed = preg_replace('/([{,]\s*"\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1$2":', $fixed);
        $fixed = preg_replace('/([{,]\s*)([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $fixed);

        // 4. Fix string values: ensure values are quoted
        //    Pattern: :value, or :value} where value is not already quoted, not number, not bool/null
        //    Allow quotes in value since step 2 may have already added them
        $fixed = preg_replace_callback(
            '/:\s*([^,{}]*?)\s*([,}])/',
            function ($m) {
                $value = trim($m[1]);
                // Check if it looks like a number
                if (is_numeric($value)) {
                    return ':' . $value . $m[2];
                }
                // Check if it's boolean/null
                if (in_array(strtolower($value), ['true', 'false', 'null'])) {
                    return ':' . $value . $m[2];
                }
                // Check if already quoted (starts and ends with ")
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    return ':' . $value . $m[2];
                }
                // String value - quote it
                return ':"' . $value . '"' . $m[2];
            },
            $fixed
        );

        // 5. Clean up any double quotes
        $fixed = str_replace('""', '"', $fixed);

        return $fixed;
    }
}