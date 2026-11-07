@extends('layouts.dasar')

@section('judul', 'Panduan — Sahih Bodyfeed')

@section('sidebar')
    <x-nav-anggota />
@endsection

@section('topbar')
    <div>
        <h1 class="text-xl font-bold text-slate-900">Panduan Reward</h1>
        <p class="text-sm text-slate-600">Mekanisme poin, bonus, dan hadiah</p>
    </div>
@endsection

@section('isi')
    <div class="prose prose-slate max-w-4xl">
        {{-- Jenis Poin --}}
        <section class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Jenis Poin</h2>
            <p class="mt-2 text-sm text-slate-700">Ada tiga jenis poin di sistem Sahih Bodyfeed:</p>

            <dl class="mt-4 space-y-4">
                <div class="rounded-lg bg-blue-50 p-4">
                    <dt class="font-semibold text-blue-900">Poin PA</dt>
                    <dd class="mt-1 text-sm text-blue-900/80">Didapat saat anggota yang Anda sponsori langsung mendaftar, dan dari repeat order (RO) Anda sendiri. Syarat menjadi Manager: Distributor + 20 poin PA.</dd>
                </div>

                <div class="rounded-lg bg-emerald-50 p-4">
                    <dt class="font-semibold text-emerald-900">Poin PR (Poin Reward)</dt>
                    <dd class="mt-1 text-sm text-emerald-900/80">Didapat dari transaksi Anda sendiri (pendaftaran, upgrade, RO) dan dari transaksi anggota yang Anda sponsori langsung. Menentukan peringkat otomatis dan hadiah milestone.</dd>
                </div>

                <div class="rounded-lg bg-amber-50 p-4">
                    <dt class="font-semibold text-amber-900">Poin PL (Leadership)</dt>
                    <dd class="mt-1 text-sm text-amber-900/80">Hanya diterima Manager. Mengalir ke atas sampai Manager pertama di rantai upline, lalu berhenti.</dd>
                </div>
            </dl>
        </section>

        {{-- Peringkat --}}
        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Peringkat</h2>
            <p class="mt-2 text-sm text-slate-700">Naik peringkat bisa dengan dua cara:</p>

            <div class="mt-4 space-y-3">
                <div class="rounded-lg border border-slate-300 p-4">
                    <h3 class="font-semibold text-slate-900">1. Beli PIN Upgrade</h3>
                    <p class="mt-1 text-sm text-slate-700">Hubungi admin untuk membeli PIN upgrade ke peringkat tertentu.</p>
                </div>

                <div class="rounded-lg border border-slate-300 p-4">
                    <h3 class="font-semibold text-slate-900">2. Otomatis dari Total PR</h3>
                    <p class="mt-1 text-sm text-slate-700">Peringkat naik otomatis saat total PR Anda mencapai ambang:</p>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <li>• <strong>Reseller</strong>: 1 poin PR</li>
                        <li>• <strong>Agent</strong>: 3 poin PR</li>
                        <li>• <strong>Distributor</strong>: 6 poin PR</li>
                        <li>• <strong>Manager</strong>: Distributor + 20 poin PA (tidak dijual via PIN)</li>
                    </ul>
                </div>
            </div>
        </section>

        {{-- Bonus Sponsor --}}
        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Bonus Sponsor</h2>
            <p class="mt-2 text-sm text-slate-700">Setiap transaksi di jaringan Anda menghasilkan bonus sponsor yang dibagi ke upline sampai 50 tingkat.</p>

            <div class="mt-4 rounded-lg bg-slate-50 p-4">
                <h3 class="font-semibold text-slate-900">Tarif per Peringkat</h3>
                <ul class="mt-2 space-y-1 text-sm text-slate-700">
                    <li>• <strong>Reseller</strong>: 15%</li>
                    <li>• <strong>Agent</strong>: 18%</li>
                    <li>• <strong>Distributor</strong>: 20%</li>
                    <li>• <strong>Manager</strong>: 20%</li>
                </ul>
                <p class="mt-3 text-sm text-slate-600">Total maksimal bonus per transaksi: 20%. Selisih peringkat dibagi dengan sistem kompresi.</p>
            </div>

            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm text-amber-900"><strong>Penting:</strong> Nominal rupiah bonus tidak ditampilkan di halaman anggota. Yang ditampilkan hanya persentase dan posisi tingkat.</p>
            </div>
        </section>

        {{-- Hadiah Milestone --}}
        <section class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Hadiah Milestone</h2>
            <p class="mt-2 text-sm text-slate-700">Hadiah berbentuk barang (bukan uang) yang jadi hak Anda saat total PR melewati ambang tertentu.</p>

            <div class="mt-4 space-y-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <h3 class="font-semibold text-slate-900">Cara Kerja</h3>
                    <ul class="mt-2 space-y-1 text-sm text-slate-700">
                        <li>• Satu putaran hadiah = <strong>3.500 poin</strong>, lalu berulang.</li>
                        <li>• Daftar ambang dan nama hadiah ada di halaman <strong>Hadiah &amp; Bonus</strong>.</li>
                        <li>• Status hadiah: <span class="font-semibold">jadi hak Anda</span> → <span class="font-semibold">sedang diproses</span> → <span class="font-semibold">sudah diserahkan</span>.</li>
                    </ul>
                </div>

                <div class="rounded-lg bg-emerald-50 p-4">
                    <h3 class="font-semibold text-emerald-900">Cara Mengajukan Klaim</h3>
                    <ol class="mt-2 space-y-1 text-sm text-emerald-900/90">
                        <li>1. Buka halaman <strong>Hadiah & Bonus</strong>.</li>
                        <li>2. Klik tombol <strong>Ajukan</strong> pada hadiah berstatus "jadi hak Anda".</li>
                        <li>3. Tunggu admin memproses dan menghubungi Anda untuk pengambilan.</li>
                        <li>4. Ambil produk di kantor dengan membawa bukti identitas.</li>
                    </ol>
                </div>
            </div>
        </section>

        {{-- Kontak --}}
        <section class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-6">
            <h2 class="text-lg font-bold text-blue-900">Butuh Bantuan?</h2>
            <p class="mt-2 text-sm text-blue-900/80">Hubungi admin untuk pertanyaan lebih lanjut tentang sistem reward.</p>
        </section>
    </div>
@endsection
