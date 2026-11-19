<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        string $message,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $msg = 'Không tìm thấy'): self { return new self(404, $msg); }
    public static function badRequest(string $msg, ?array $details = null): self { return new self(422, $msg, $details); }
    public static function unauthorized(string $msg = 'Chưa đăng nhập'): self { return new self(401, $msg); }
    public static function forbidden(string $msg = 'Không có quyền truy cập'): self { return new self(403, $msg); }
}
