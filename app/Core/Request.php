<?php
declare(strict_types=1);

namespace App\Core;

final class Request
{
    public array $params = [];   // route params, e.g. {slug}

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly ?array $jsonBody,
        private readonly bool $badJson = false,
    ) {}

    public static function capture(): self
    {
        // Strip the folder the site lives in (e.g. /co-phuc-viet) from the URI
        $base = rtrim((string) Config::get('base_path', ''), '/');
        $uri  = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
        if ($base !== '' && str_starts_with($uri, $base)) $uri = substr($uri, strlen($base));

        $body = null;
        $bad = false;
        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = $raw === '' ? [] : json_decode($raw, true);
            $body = is_array($decoded) ? $decoded : null;
            $bad = $body === null;   // malformed JSON or invalid UTF-8
        }
        return new self($_SERVER['REQUEST_METHOD'], '/' . trim($uri, '/'), $_GET, $body, $bad);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $this->assertValidBody();
        return $this->jsonBody[$key] ?? $_POST[$key] ?? $default;
    }

    public function body(): array
    {
        $this->assertValidBody();
        return $this->jsonBody ?? $_POST;
    }

    /** Never silently fall back to defaults when the client sent a broken JSON body. */
    private function assertValidBody(): void
    {
        if ($this->badJson) throw new HttpException(400, t('errors.badRequest'));
    }

    /** Query-string value; empty strings count as missing. */
    public function query(string $key, mixed $default = null): mixed
    {
        $v = $this->query[$key] ?? $default;
        return $v === '' ? $default : $v;
    }

    public function isAjax(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
            || str_starts_with($this->path, '/api/')
            || str_starts_with($this->path, '/admin/api/');
    }
}
