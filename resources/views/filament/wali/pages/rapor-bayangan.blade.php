<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Filter Semester Rapor Bayangan --}}
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-xs print:hidden">
            <div class="flex items-center gap-2">
                <x-heroicon-m-academic-cap class="h-5 w-5 text-olive-500" />
                <h3 class="font-extrabold text-navy-900">Rapor Bayangan Tengah Semester (STS)</h3>
            </div>

            <div class="flex items-center gap-3">
                <label for="wali-semester-rapor-page" class="text-xs font-bold text-gray-700">Semester:</label>
                <select id="wali-semester-rapor-page" wire:model.live="semesterRapor"
                    class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-bold focus:border-olive-500 focus:ring-2 focus:ring-olive-500/20">
                    <option value="1">Semester 1 (Ganjil)</option>
                    <option value="2">Semester 2 (Genap)</option>
                </select>

                <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-navy-900 px-3 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-navy-800">
                    <x-heroicon-m-printer class="h-4 w-4" />
                    <span>Cetak Rapor</span>
                </button>
            </div>
        </div>

        {{-- Lembar Rapor Bayangan --}}
        <div class="wali-rapor-sheet">
            {{-- Kop --}}
            <div class="wali-rapor-kop">
                <img src="{{ $rapor['tingkat'] === 'SMA' ? asset('images/Kop SMA.png') : asset('images/Kop SMP.png') }}"
                    alt="Kop Surat Pondok Tahfizh Plus Abu Dzar">
            </div>

            {{-- Judul --}}
            <div class="wali-rapor-judul">
                <h2>Laporan Hasil Evaluasi Formatif dan Sumatif Tengah Semester (STS)</h2>
                <p>Tahun Pelajaran {{ $rapor['tahun'] ?? '-' }}</p>
            </div>

            {{-- Identitas --}}
            <div class="mb-4 text-xs">
                <table class="w-full border-collapse">
                    <tbody>
                        <tr class="h-6">
                            <td class="w-[14%] font-medium text-gray-700">Nama Siswa</td>
                            <td class="w-[2%] font-bold text-gray-400">:</td>
                            <td class="w-[44%] font-bold text-navy-900">{{ strtoupper($santri->nama_lengkap) }}</td>
                            <td class="w-[12%] font-medium text-gray-700">Kelas</td>
                            <td class="w-[2%] font-bold text-gray-400">:</td>
                            <td class="w-[26%] font-bold text-navy-900">{{ $santri->kelas ?? '-' }}</td>
                        </tr>
                        <tr class="h-6">
                            <td class="font-medium text-gray-700">NIS / NISN</td>
                            <td class="font-bold text-gray-400">:</td>
                            <td class="font-semibold text-gray-900">{{ $santri->nis ?? '-' }} / {{ $santri->nisn ?? '-' }}</td>
                            <td class="font-medium text-gray-700">Semester</td>
                            <td class="font-bold text-gray-400">:</td>
                            <td class="font-semibold text-gray-900">{{ $rapor['semester'] }} ({{ $rapor['semester'] === '1' ? 'Ganjil' : 'Genap' }})</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Tabel nilai --}}
            @php
                $totalNilai = 0;
                $jumlahNilai = 0;
            @endphp
            <table class="wali-rapor-table mb-3 wali-rapor-kolom-tengah">
                <thead>
                    <tr class="wali-rapor-kepala">
                        <th style="width: 5%;">No</th>
                        <th class="wali-rapor-kolom-nama" style="width: 40%;">Mata Pelajaran</th>
                        <th style="width: 10%;">KKM</th>
                        <th style="width: 12%;">Nilai Angka</th>
                        <th style="width: 20%;">Nilai Huruf</th>
                        <th style="width: 13%;">Ketuntasan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rapor['mapelSettings'] as $idx => $mapel)
                        @php
                            $nilai = $rapor['nilaiPerMapel'][$mapel->id] ?? null;
                            $kkm = $rapor['kkmPerMapel'][$mapel->mata_pelajaran] ?? 80;
                            $tuntas = $nilai !== null && $nilai >= $kkm;

                            if ($nilai !== null) {
                                $totalNilai += $nilai;
                                $jumlahNilai++;
                            }
                        @endphp
                        <tr>
                            <td>{{ $idx + 1 }}</td>
                            <td class="wali-rapor-kolom-nama">{{ $mapel->mata_pelajaran }}</td>
                            <td>{{ $kkm }}</td>
                            <td class="font-bold">{{ $nilai !== null ? number_format($nilai, 0) : '-' }}</td>
                            <td class="capitalize">{{ $nilai !== null ? \App\Support\Terbilang::dari($nilai) : '-' }}</td>
                            <td @class([
                                $tuntas ? 'wali-rapor-tuntas' : 'wali-rapor-belum-tuntas',
                            ])>{{ $nilai !== null ? ($tuntas ? 'Tuntas' : 'Belum Tuntas') : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="italic">Belum ada data mata pelajaran.</td>
                        </tr>
                    @endforelse

                    <tr class="wali-rapor-jumlah">
                        <td colspan="3" class="wali-rapor-kolom-nama uppercase">Total Nilai</td>
                        <td>{{ number_format($totalNilai, 0) }}</td>
                        <td colspan="2"></td>
                    </tr>
                    <tr class="wali-rapor-jumlah">
                        <td colspan="3" class="wali-rapor-kolom-nama uppercase">Rata-Rata</td>
                        <td>{{ number_format($jumlahNilai > 0 ? round($totalNilai / $jumlahNilai, 2) : 0, 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tbody>
            </table>

            {{-- Ketidakhadiran + kepribadian --}}
            <div class="wali-rapor-grid-dua">
                <table class="wali-rapor-table">
                    <thead>
                        <tr class="wali-rapor-kepala">
                            <th colspan="2" class="uppercase">Ketidakhadiran</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 70%;">Sakit</td>
                            <td class="wali-rapor-kolom-tengah font-bold" style="width: 30%;">{{ $rapor['catatan']->sakit ?? 0 }} hari</td>
                        </tr>
                        <tr>
                            <td>Izin</td>
                            <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->izin ?? 0 }} hari</td>
                        </tr>
                        <tr>
                            <td>Tanpa Keterangan</td>
                            <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->alpha ?? 0 }} hari</td>
                        </tr>
                    </tbody>
                </table>

                <table class="wali-rapor-table">
                    <thead>
                        <tr class="wali-rapor-kepala">
                            <th colspan="2" class="uppercase">Kepribadian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="width: 70%;">Perilaku / Sikap</td>
                            <td class="wali-rapor-kolom-tengah font-bold" style="width: 30%;">{{ $rapor['catatan']->perilaku ?? 'A' }}</td>
                        </tr>
                        <tr>
                            <td>Kedisiplinan / Kerajinan</td>
                            <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->kedisiplinan ?? 'A' }}</td>
                        </tr>
                        <tr>
                            <td>Kebersihan &amp; Kerapihan</td>
                            <td class="wali-rapor-kolom-tengah font-bold">{{ $rapor['catatan']->kebersihan ?? 'A' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Catatan --}}
            <table class="wali-rapor-table mb-4">
                <thead>
                    <tr class="wali-rapor-kepala">
                        <th class="wali-rapor-kolom-nama uppercase">Catatan Kepribadian Siswa</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="italic text-slate-700">
                            &ldquo;{{ $rapor['catatan']->catatan_kepribadian ?? 'Tingkatkan terus kedisiplinan dan keaktifan dalam kegiatan pembelajaran di pondok pesantren.' }}&rdquo;
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Tanda tangan --}}
            <div class="mt-6">
                <div class="wali-rapor-ttd-tanggal">{{ $tanggalCetakRapor }}</div>

                <div class="wali-rapor-ttd">
                    <div class="wali-rapor-ttd-kolom">
                        <div class="wali-rapor-ttd-label">Orang Tua / Wali Santri</div>
                        <div class="wali-rapor-ttd-ruang"></div>
                        <div class="wali-rapor-ttd-nama">( ........................................ )</div>
                    </div>

                    <div class="wali-rapor-ttd-kolom">
                        <div class="wali-rapor-ttd-label">Wali Kelas</div>
                        <div class="wali-rapor-ttd-ruang">
                            @if (! empty($rapor['ttdWalas']))
                                <img src="{{ asset($rapor['ttdWalas']) }}" class="wali-rapor-ttd-gambar" alt="TTD Wali Kelas">
                            @endif
                        </div>
                        <div>
                            <div class="wali-rapor-ttd-nama">{{ $rapor['waliKelasNama'] ?? '........................................' }}</div>
                            <div class="wali-rapor-ttd-nip">NIP: {{ $rapor['waliKelasNip'] ?? '-' }}</div>
                        </div>
                    </div>

                    <div class="wali-rapor-ttd-kolom">
                        <div class="wali-rapor-ttd-label">Mengetahui,<br>Kepala Sekolah {{ $rapor['tingkat'] }}</div>
                        <div class="wali-rapor-ttd-ruang wali-rapor-ttd-ruang--kepsek">
                            @if (! empty($rapor['kepsekTtd']))
                                <img src="{{ asset($rapor['kepsekTtd']) }}" class="wali-rapor-ttd-gambar" alt="TTD Kepala Sekolah">
                            @endif
                        </div>
                        <div>
                            <div class="wali-rapor-ttd-nama">{{ $rapor['kepsekNama'] }}</div>
                            <div class="wali-rapor-ttd-nip">NIY: {{ $rapor['kepsekNiy'] ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
