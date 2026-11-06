<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/**
 * Katalog izin per aksi (R-03). Satu baris matriks PRD §4.3 = izin `.view` (👁) dan/atau izin aksinya.
 * Hanya izin di katalog ini yang bisa lolos Gate (PermissionGate); izin baru wajib ditambahkan di sini.
 */
enum Permission: string
{
    case UserManage = 'user.manage';
    case RoleManage = 'role.manage';
    case LocationManage = 'location.manage';
    case SettingsManage = 'settings.manage';

    case PosTokenManage = 'pos_token.manage';

    case CatalogView = 'catalog.view';
    case CatalogManage = 'catalog.manage';
    case CatalogImport = 'catalog.import';

    case ReorderPointView = 'reorder_point.view';
    case ReorderPointManage = 'reorder_point.manage';

    case GoodsReceiptView = 'goods_receipt.view';
    case GoodsReceiptPost = 'goods_receipt.post';

    case TransferView = 'transfer.view';
    case TransferPost = 'transfer.post';

    case ReturnView = 'return.view';
    case ReturnPost = 'return.post';

    // 📝 di matriks: membuat draft saja; posting butuh AdjustmentApprove oleh orang lain (R-02).
    case AdjustmentView = 'adjustment.view';
    case AdjustmentCreate = 'adjustment.create';
    case AdjustmentApprove = 'adjustment.approve';

    case ReversalView = 'reversal.view';
    case ReversalCreate = 'reversal.create';

    case StockCountView = 'stock_count.view';
    case StockCountManage = 'stock_count.manage';
    case StockCountCount = 'stock_count.count';

    case ReconciliationView = 'reconciliation.view';
    case ReconciliationApprove = 'reconciliation.approve';

    case PosImportView = 'pos_import.view';
    case PosImportManage = 'pos_import.manage';
    case PosPush = 'pos.push';
    case PosDiscrepancyView = 'pos_discrepancy.view';
    case PosDiscrepancyResolve = 'pos_discrepancy.resolve';

    case DashboardView = 'dashboard.view';
    case ReportView = 'report.view';
    case StockCheck = 'stock.check';
    case ReportExport = 'report.export';

    // view = entri ber-lokasi di lokasi user; view_global = juga entri tanpa lokasi (login, perubahan role). Ditegakkan di T5.08.
    case AuditLogView = 'audit_log.view';
    case AuditLogViewGlobal = 'audit_log.view_global';
}
