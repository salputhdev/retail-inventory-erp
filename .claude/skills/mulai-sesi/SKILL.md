---
name: mulai-sesi
description: Memulai sesi baru di project Retail Inventory dengan membaca checkpoint (CLAUDE.md, TODO.md handoff/ringkasan/isu, docs/LESSONS.md), memeriksa keadaan git dan PR yang sebenarnya, lalu memberi ringkasan posisi dan langkah berikutnya. Pakai di awal setiap chat baru, dan setiap kali Salman bilang "lanjut", "lanjutkan", "mulai sesi", "kita sampai mana", "baca handoff", "cek progres", atau membuka chat tanpa instruksi yang jelas, walau ia tidak menyebut nama skill ini.
allowed-tools: Read Grep Glob Bash(git status *) Bash(git log *) Bash(git fetch *) Bash(git diff *) Bash(git branch *) Bash(git show *) Bash(gh pr list *) Bash(gh pr view *) Bash(gh pr checks *) Bash(gh run list *) Bash(php scripts/todo-summary.php --check)
---

Tujuan: sesi baru langsung tahu posisi project tanpa mengulang pekerjaan atau menebak. Catatan di `TODO.md` bisa tertinggal dari kenyataan (mis. PR sudah ter-merge setelah handoff ditulis), jadi **bandingkan catatan dengan keadaan git/GitHub yang sebenarnya**, jangan hanya mengulang isi file.

Skill ini **hanya membaca**. Jangan commit, push, merge, mengubah file, atau mulai mengerjakan task sebelum Salman memilih.

## Langkah

1. **Baca checkpoint** (secukupnya, bukan seluruh file panjang):
   - `CLAUDE.md` dan `WORKFLOW.md` (zona izin hijau/kuning/merah, definisi selesai).
   - `TODO.md`: §2 Ringkasan progres, §3 Handoff, §4 Siap dikerjakan, §8 isu yang belum `closed`.
   - `docs/LESSONS.md` **seluruhnya**: isinya jebakan yang sudah memakan waktu; baca sebelum menyentuh kode.
2. **Periksa keadaan sebenarnya:**
   - `git fetch`, lalu `git status -sb`, `git branch --show-current`, dan `git log --oneline -5` (catat tanggal commit terakhir).
   - `gh pr list` (terbuka dan ter-merge terbaru) dan, untuk PR di branch aktif, `gh pr checks`.
   - `php scripts/todo-summary.php --check` (hanya validasi; jangan jalankan tanpa `--check` karena menulis ulang `TODO.md`).
   - **Handoff terbaru sering ada di branch PR yang belum ter-merge**, bukan di `main`. Untuk tiap PR terbuka, baca versi branch-nya: `git show origin/<branch-pr>:TODO.md` (bagian §3). Bila handoff di branch lebih baru dari yang di `main`, anggap itu yang berlaku dan sebutkan bahwa catatan baru baru masuk `main` setelah PR itu di-merge.
   - Bila `gh` gagal (belum login/offline), katakan itu dan lanjutkan dari git saja.
3. **Cocokkan.** Cari selisih antara handoff dan kenyataan: PR yang disebut terbuka tetapi sudah ter-merge, branch yang berbeda dari yang dicatat, perubahan belum ter-commit, `main` lokal tertinggal dari `origin/main`, `--check` merah. Laporkan selisih apa adanya; usulkan perbaikan `TODO.md` tetapi jangan menulisnya sendiri.
4. **Pilih jebakan yang relevan.** Dari LESSONS, ambil 3 sampai 5 butir yang paling mungkin kena pada task berikutnya (mis. task keamanan: ADR-003 dan bagian autentikasi), jangan menyalin semuanya.

## Format ringkasan (Bahasa Indonesia, singkat, tabel bila membantu)

- **Posisi:** task terakhir dan statusnya, jumlah task selesai, branch aktif, commit terakhir (hash, pesan, tanggal).
- **PR:** nomor, status, CI.
- **Perlu keputusan Salman:** item dari §3/§8 yang menunggu dia (mis. keputusan desain, pertanyaan produk, review keamanan R4, merge).
- **Selisih catatan vs kenyataan:** bila ada.
- **Langkah berikutnya yang disarankan:** satu rekomendasi jelas dengan alasannya, plus alternatifnya bila ada.
- **Jebakan relevan:** 3 sampai 5 butir dari LESSONS.

Akhiri dengan satu pertanyaan: lanjut dengan rekomendasi itu, atau yang lain? Tunggu jawaban sebelum bekerja.

## Aturan yang tetap berlaku (dari kesepakatan project)
- Satu PR per task, langsung dari `main`; merge dan status `done` hanya atas konfirmasi Salman.
- Task berisiko R4 (auth, RBAC, scope lokasi, ledger) butuh `/review-security main` oleh Salman sebelum merge.
- Bahasa Indonesia, jawaban ringkas, laporkan hasil dengan bukti (jumlah test, status CI) dan sebut yang tidak terverifikasi.
