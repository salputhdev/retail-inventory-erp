# Architecture Decision Records

Format tiap ADR: konteks, opsi, keputusan, konsekuensi. ADR baru memakai nomor berikutnya; ADR yang diganti diberi status *Digantikan oleh ADR-xxx* (tidak dihapus).

Persetujuan: proyek ini portfolio tanpa architect klien, jadi persetujuan dilakukan Salman (berperan sebagai architect) lewat merge PR.

| ADR | Keputusan | Status |
| --- | --------- | ------ |
| [ADR-001](ADR-001.md) | Modular monolith Laravel, bukan microservice | Disetujui |
| [ADR-002](ADR-002.md) | Ledger immutable + `stock_levels` sebagai cache | Disetujui |
| [ADR-003](ADR-003.md) | Scope lokasi: pivot `location_user` + global scope + Policy | Disetujui |
| [ADR-004](ADR-004.md) | UI Livewire + Bootstrap; aturan widget JS Bootstrap | Disetujui |
| [ADR-005](ADR-005.md) | Redis + Horizon untuk queue, cache, session | Disetujui |
| [ADR-006](ADR-006.md) | Hosting, backup, dan PITR | Diputuskan sebagian: staging lokal; produksi belum |
| [ADR-007](ADR-007.md) | Alat browser testing: Pest browser (Playwright) | Disetujui |
| [ADR-008](ADR-008.md) | Costing weighted average di Fase 1; FIFO di Fase 2 | Disetujui |
| [ADR-009](ADR-009.md) | Versi stack dikunci saat M0 | Disetujui |
| ADR-010 | Outbox pattern untuk sinkron keluar | Ditunda ke Fase 4 |
| ADR-011 | Kontrak forecast service | Ditunda ke Fase 5 |
| ADR-012 | Strategi multi-tenant | Ditunda ke Fase 7 |
