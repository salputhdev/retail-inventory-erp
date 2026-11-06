<?php

declare(strict_types=1);

namespace App\Domain\Identity\Enums;

/**
 * Role Fase 1 (PRD §4.2). Izin default = matriks PRD §4.3; setelah di-seed, Super Admin bisa mengubahnya tanpa deploy.
 * Super Admin sengaja tidak mendapat izin transaksi stok (catatan di bawah matriks PRD §4.3).
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case StoreManager = 'store-manager';
    case InventoryStaff = 'inventory-staff';
    case PosIntegrator = 'pos-integrator';
    case Auditor = 'auditor';

    /** @return list<Permission> */
    public function defaultPermissions(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                Permission::UserManage, Permission::RoleManage, Permission::LocationManage, Permission::SettingsManage,
                Permission::PosTokenManage,
                Permission::CatalogView, Permission::CatalogManage, Permission::CatalogImport,
                Permission::ReorderPointView, Permission::ReorderPointManage,
                Permission::PosImportView, Permission::PosImportManage,
                Permission::DashboardView, Permission::ReportView, Permission::StockCheck, Permission::ReportExport,
                Permission::AuditLogView, Permission::AuditLogViewGlobal,
            ],
            self::StoreManager => [
                Permission::CatalogView, Permission::CatalogManage, Permission::CatalogImport,
                Permission::ReorderPointView, Permission::ReorderPointManage,
                Permission::GoodsReceiptView, Permission::GoodsReceiptPost,
                Permission::TransferView, Permission::TransferPost,
                Permission::ReturnView, Permission::ReturnPost,
                Permission::AdjustmentView, Permission::AdjustmentCreate, Permission::AdjustmentApprove,
                Permission::ReversalView, Permission::ReversalCreate,
                Permission::StockCountView, Permission::StockCountManage, Permission::StockCountCount,
                Permission::ReconciliationView, Permission::ReconciliationApprove,
                Permission::PosImportView, Permission::PosImportManage,
                Permission::PosDiscrepancyView, Permission::PosDiscrepancyResolve,
                Permission::DashboardView, Permission::ReportView, Permission::StockCheck, Permission::ReportExport,
                Permission::AuditLogView,
            ],
            self::InventoryStaff => [
                Permission::CatalogView,
                Permission::ReorderPointView,
                Permission::GoodsReceiptView, Permission::GoodsReceiptPost,
                Permission::TransferView, Permission::TransferPost,
                Permission::ReturnView, Permission::ReturnPost,
                Permission::AdjustmentView, Permission::AdjustmentCreate,
                Permission::StockCountCount,
                Permission::StockCheck,
            ],
            // Akun teknis (R-05). Pemetaan ke token Sanctum per sumber POS diputuskan di T3.02.
            self::PosIntegrator => [
                Permission::PosPush,
            ],
            self::Auditor => [
                Permission::CatalogView,
                Permission::ReorderPointView,
                Permission::GoodsReceiptView,
                Permission::TransferView,
                Permission::ReturnView,
                Permission::AdjustmentView,
                Permission::ReversalView,
                Permission::StockCountView,
                Permission::ReconciliationView,
                Permission::PosImportView,
                Permission::PosDiscrepancyView,
                Permission::DashboardView, Permission::ReportView, Permission::StockCheck, Permission::ReportExport,
                Permission::AuditLogView, Permission::AuditLogViewGlobal,
            ],
        };
    }
}
