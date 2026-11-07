@extends('layouts.dasar')

@section('judul', 'Panduan Admin — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-admin />
@endsection

@section('topbar')
    <div>
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Admin</p>
        <p class="text-base font-bold text-slate-900">Panduan Admin</p>
    </div>
@endsection

@section('isi')
    <div class="max-w-4xl space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Alur Kerja Harian</h2>
            <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm text-slate-700">
                <li>Anggota membeli PIN dari Anda (pembayaran dilakukan di luar sistem).</li>
                <li>Buat PIN di <strong>Kelola PIN → Buat PIN</strong>. Isi jenis, jumlah pin, harga, dan username pemegang bila ada.</li>
                <li>Berikan kode PIN ke anggota. Anggota menukarnya sendiri di halaman <strong>Tukar PIN</strong>.</li>
                <li>Poin, bonus sponsor, peringkat, dan hadiah dihitung otomatis saat PIN ditukar.</li>
                <li>Proses klaim hadiah di <strong>Klaim Hadiah</strong>, lalu tandai <strong>Serahkan</strong> setelah barang diterima anggota.</li>
            </ol>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Jenis PIN</h2>
            <dl class="mt-3 space-y-3 text-sm text-slate-700">
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="font-semibold text-slate-900">Registrasi / naik peringkat</dt>
                    <dd class="mt-1">Username belum punya akun: PIN dipakai untuk mendaftarkan anggota baru (1 pin = Reseller, 3 = Agent, 6 = Distributor). Username sudah punya akun: PIN menaikkan peringkatnya sesuai peringkat tujuan. Manager tidak dijual lewat PIN.</dd>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <dt class="font-semibold text-slate-900">Repeat order (RO)</dt>
                    <dd class="mt-1">Memberi poin PR dan PA ke pembeli. Tidak membayar bonus sponsor.</dd>
                </div>
            </dl>
            <p class="mt-3 text-sm text-slate-600">Harga harus sama dengan harga satuan × jumlah pin, kalau tidak ditolak. Ubah harga satuan di Pengaturan (Superadmin).</p>
            <p class="mt-2 text-sm text-slate-600">PIN yang belum dipakai bisa dibatalkan dengan alasan. PIN yang sudah dipakai tidak bisa dibatalkan.</p>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Kelola Anggota</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
                <li>Cari anggota berdasarkan username atau nama, saring berdasarkan peringkat dan status.</li>
                <li>Halaman detail menampilkan profil, total poin dari ledger, dan pohon genealogi sampai 3 tingkat.</li>
                <li>Edit: nama, telepon, alamat, dan status akun. Username dan NIK tidak bisa diubah.</li>
                <li>Anggota baru hanya bisa dibuat lewat PIN registrasi supaya poin dan bonus tetap tercatat benar. Anggota yang sudah punya transaksi tidak dihapus, cukup <strong>dibekukan</strong>.</li>
            </ul>
        </section>

        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Klaim Hadiah</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
                <li>Status: <strong>jadi hak</strong> (anggota belum mengajukan) → <strong>diajukan</strong> → <strong>diserahkan</strong>.</li>
                <li>Klik <strong>Serahkan</strong> hanya setelah barang benar-benar diterima anggota. Isi catatan bila perlu.</li>
                <li>Yang diserahkan adalah barang, bukan uang.</li>
            </ul>
        </section>

        <section class="rounded-lg border border-amber-200 bg-amber-50 p-6">
            <h2 class="text-lg font-bold text-amber-900">Yang Perlu Diperhatikan</h2>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-amber-900">
                <li>Nilai rupiah tidak boleh tampil di halaman anggota.</li>
                <li>Perubahan pengaturan hanya berlaku untuk transaksi berikutnya, bukan yang sudah tercatat.</li>
                <li>Setiap perubahan penting tercatat di jejak audit.</li>
            </ul>
        </section>
    </div>
@endsection
