<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\{Request, Response, View};
use App\Services\Inventory;

final class InventoryController
{
    /** GET /admin/kho?tab=stock|history */
    public function index(Request $req): void
    {
        $tab = $req->query('tab') === 'history' ? 'history' : 'stock';
        $f = $this->filters($req);
        View::page('admin/inventory/index', [
            'title'   => 'Kho hàng',
            'tab'     => $tab,
            'filters' => $f,
            'result'  => $tab === 'history' ? Inventory::movements($f) : Inventory::stockList($f),
        ], 200, 'admin/layout');
    }

    /** GET /admin/api/inventory?tab= — Ajax rows */
    public function rows(Request $req): void
    {
        $tab = $req->query('tab') === 'history' ? 'history' : 'stock';
        $f = $this->filters($req);
        $result = $tab === 'history' ? Inventory::movements($f) : Inventory::stockList($f);
        Response::json(['html' => View::partial("admin/inventory/$tab", ['result' => $result, 'filters' => $f + ['tab' => $tab]])]);
    }

    /** POST /admin/api/inventory/{id}  {mode: add|set, qty, reason, note} */
    public function adjust(Request $req): void
    {
        $qty = (string) $req->input('qty', '');
        $mode = $req->input('mode') === 'set' ? 'set' : 'add';
        if (!preg_match($mode === 'set' ? '/^\d{1,6}$/' : '/^-?\d{1,6}$/', $qty) || ($mode === 'add' && (int) $qty === 0)) {
            throw \App\Core\HttpException::badRequest('Nhập số lượng hợp lệ', ['qty' => $mode === 'set' ? 'Nhập số ≥ 0' : 'Nhập số khác 0 (âm để trừ)']);
        }
        $note = mb_substr(trim((string) $req->input('note', '')), 0, 255) ?: null;
        $r = Inventory::adjust((int) $req->params['id'], $mode === 'set' ? (int) $qty : null, $mode === 'add' ? (int) $qty : null,
                               (string) $req->input('reason', ''), $note);
        Response::json(['ok' => true, 'stock' => $r['stock'], 'low' => $r['stock'] <= low_stock(),
                        'message' => $r['change'] ? sprintf('Đã cập nhật: %s%d → còn %d', $r['change'] > 0 ? '+' : '', $r['change'], $r['stock']) : 'Không có thay đổi']);
    }

    private function filters(Request $req): array
    {
        $date = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        return [
            'tab'     => $req->query('tab') === 'history' ? 'history' : 'stock',
            'q'       => mb_substr(trim((string) $req->query('q', '')), 0, 60),
            'level'   => (string) $req->query('level', ''),
            'reason'  => (string) $req->query('reason', ''),
            'variant' => (int) $req->query('variant', 0) ?: '',
            'from'    => $date($req->query('from')),
            'to'      => $date($req->query('to')),
            'page'    => (int) $req->query('page', 1),
        ];
    }
}
