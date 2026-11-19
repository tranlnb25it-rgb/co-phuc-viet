<?php
declare(strict_types=1);

namespace App\Core;

/** Plain-PHP templates from /views. Templates escape output with e(). */
final class View
{
    private static function path(string $template): string
    {
        $file = dirname(__DIR__, 2) . '/views/' . $template . '.php';
        if (!is_file($file)) throw new \RuntimeException("View not found: $template");
        return $file;
    }

    /** Render a template to a string. */
    public static function partial(string $template, array $data = []): string
    {
        // Underscored locals so template variables named $data / $template are not shadowed by extract()
        $__file = self::path($template);
        unset($template);
        extract($data, EXTR_SKIP);
        if (array_key_exists('data', $data)) $data = $data['data'];
        ob_start();
        try {
            require $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }

    /** Render a page inside a layout and send it. $data['title'] sets the <title>. */
    public static function page(string $template, array $data = [], int $status = 200, string $layout = 'layouts/main'): never
    {
        $content = self::partial($template, $data);
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo self::partial($layout, $data + ['content' => $content]);
        exit;
    }
}
