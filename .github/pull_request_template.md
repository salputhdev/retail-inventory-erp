## Ringkasan
<!-- Apa yang berubah dan kenapa. Sebut ID task, mis. T1.04. -->

## Kriteria "Selesai jika"
<!-- Salin butir dari docs/Architecture.md untuk task ini dan centang yang sudah terbukti. -->
- [ ]

## Bukti
<!-- Nama test atau output perintah: pest, pint --test, phpstan, inventory:verify. -->

## Definition of Done (Architecture §12)
- [ ] `php artisan test` (Pest), `pint --test`, `phpstan analyse` hijau
- [ ] Bila menyentuh stok: `php artisan inventory:verify` 0 selisih
- [ ] Izin diuji dua arah: berhak bisa, tidak berhak 403, data lokasi lain 404
- [ ] Aksi sensitif tercatat di audit log; migration punya `down()` yang teruji
- [ ] Tidak ada secret, data klien, `dd()`, `dump()`, atau kode mati di diff
- [ ] Dokumentasi terkait diperbarui; `TODO.md` diperbarui di PR yang sama
