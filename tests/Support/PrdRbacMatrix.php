<?php

declare(strict_types=1);

namespace Tests\Support;

use LogicException;

/**
 * Matriks PRD §4.3 ditulis ulang dengan simbol PRD, sengaja terpisah dari Role::defaultPermissions() supaya test
 * membandingkan dua sumber, bukan mengulang kode yang diuji.
 * ✅ = lihat + aksi · 📝 = lihat + aksi (draft) · 👁 / 👁 (...) = izin lihat saja · — = tidak ada.
 */
final class PrdRbacMatrix
{
    public const ROLES = ['super-admin', 'store-manager', 'inventory-staff', 'pos-integrator', 'auditor'];

    /** Aksi PRD, izin lihat, izin aksi, simbol untuk [Super Admin, Store Manager, Inventory Staff, POS Integrator, Auditor]. */
    private const ROWS = [
        ['Kelola user, role, lokasi, konfigurasi', null, ['user.manage', 'role.manage', 'location.manage', 'settings.manage'], ['✅', '—', '—', '—', '—']],
        ['Kelola token API POS', null, ['pos_token.manage'], ['✅', '—', '—', '—', '—']],
        ['Kelola produk, varian, SKU, barcode, kategori', 'catalog.view', ['catalog.manage'], ['✅', '✅', '👁', '—', '👁']],
        ['Import massal produk (CSV)', null, ['catalog.import'], ['✅', '✅', '—', '—', '—']],
        ['Atur reorder point / minimum per lokasi', 'reorder_point.view', ['reorder_point.manage'], ['✅', '✅', '👁', '—', '👁']],
        ['Posting penerimaan barang', 'goods_receipt.view', ['goods_receipt.post'], ['—', '✅', '✅', '—', '👁']],
        ['Kirim & terima transfer', 'transfer.view', ['transfer.post'], ['—', '✅', '✅', '—', '👁']],
        ['Posting retur pelanggan / supplier', 'return.view', ['return.post'], ['—', '✅', '✅', '—', '👁']],
        ['Adjustment manual', 'adjustment.view', ['adjustment.create'], ['—', '📝', '📝', '—', '👁']],
        ['Approve adjustment (bukan pembuatnya)', null, ['adjustment.approve'], ['—', '✅', '—', '—', '—']],
        ['Reversal movement (dengan alasan)', 'reversal.view', ['reversal.create'], ['—', '✅', '—', '—', '👁']],
        ['Buat & batalkan sesi stock count', 'stock_count.view', ['stock_count.manage'], ['—', '✅', '—', '—', '👁']],
        ['Input hasil hitung', null, ['stock_count.count'], ['—', '✅', '✅', '—', '—']],
        ['Review exception & approve rekonsiliasi (bukan penghitungnya)', 'reconciliation.view', ['reconciliation.approve'], ['—', '✅', '—', '—', '👁']],
        ['Upload file POS & kelola template mapping kolom', 'pos_import.view', ['pos_import.manage'], ['✅', '✅', '—', '—', '👁']],
        ['Push transaksi POS via API', null, ['pos.push'], ['—', '—', '—', '✅', '—']],
        ['Resolve discrepancy POS & mapping SKU', 'pos_discrepancy.view', ['pos_discrepancy.resolve'], ['—', '✅', '—', '—', '👁']],
        ['Dashboard & laporan', 'stock.check', ['dashboard.view', 'report.view'], ['✅', '✅', '👁 (cek stok)', '—', '✅']],
        ['Export laporan (CSV/XLSX/PDF)', null, ['report.export'], ['✅', '✅', '—', '—', '✅']],
        ['Lihat audit log', 'audit_log.view', ['audit_log.view_global'], ['✅', '👁 (lokasinya)', '—', '—', '✅']],
    ];

    /** @return array<string, array<string, bool>> izin => role => diizinkan */
    public static function expected(): array
    {
        $expected = [];

        foreach (self::ROWS as [$action, $view, $acts, $symbols]) {
            $permissions = $view === null ? $acts : [$view, ...$acts];

            foreach ($permissions as $permission) {
                if (isset($expected[$permission])) {
                    throw new LogicException("Izin {$permission} muncul di lebih dari satu baris ({$action}).");
                }

                foreach (self::ROLES as $i => $role) {
                    $expected[$permission][$role] = match (true) {
                        $symbols[$i] === '—' => false,
                        $symbols[$i] === '✅', $symbols[$i] === '📝' => true,
                        str_starts_with($symbols[$i], '👁') => $permission === $view,
                        default => throw new LogicException("Simbol tidak dikenal: {$symbols[$i]}"),
                    };
                }
            }
        }

        return $expected;
    }

    /** @return array<string, array{string, string, bool}> dataset Pest: "role × izin" => [role, izin, diizinkan] */
    public static function cells(): array
    {
        $cells = [];

        foreach (self::expected() as $permission => $roles) {
            foreach ($roles as $role => $allowed) {
                $cells[($allowed ? 'boleh' : 'dilarang')." {$role} × {$permission}"] = [$role, $permission, $allowed];
            }
        }

        return $cells;
    }
}
