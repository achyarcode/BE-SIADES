<?php

namespace Database\Seeders;

use App\Models\JenisSurat;
use App\Models\KategoriKatalog;
use Illuminate\Database\Seeder;

class LookupTableSeeder extends Seeder
{
    public function run(): void
    {
        // === Jenis Surat ===
        $jenisSurats = [
            ['nama' => 'Surat Keterangan Usaha',       'deskripsi' => 'Surat keterangan untuk keperluan usaha/bisnis warga.'],
            ['nama' => 'Surat Keterangan Domisili',     'deskripsi' => 'Surat keterangan tempat tinggal/domisili warga.'],
            ['nama' => 'Surat Keterangan Tidak Mampu',  'deskripsi' => 'Surat keterangan ekonomi kurang mampu.'],
            ['nama' => 'Surat Pengantar',               'deskripsi' => 'Surat pengantar umum untuk berbagai keperluan administrasi.'],
            ['nama' => 'Surat Keterangan Kematian',     'deskripsi' => 'Surat keterangan meninggal dunia.'],
            ['nama' => 'Surat Keterangan Kelahiran',    'deskripsi' => 'Surat keterangan lahir untuk pendaftaran akta.'],
        ];

        foreach ($jenisSurats as $item) {
            JenisSurat::firstOrCreate(['nama' => $item['nama']], $item);
        }

        $this->command->info('Seeded '.count($jenisSurats).' jenis surat.');

        // === Kategori Katalog ===
        $kategoriKatalogs = [
            ['nama' => 'Jasa'],
            ['nama' => 'Produk'],
            ['nama' => 'Makanan & Minuman'],
            ['nama' => 'Pertanian'],
            ['nama' => 'Kerajinan'],
        ];

        foreach ($kategoriKatalogs as $item) {
            KategoriKatalog::firstOrCreate(['nama' => $item['nama']], $item);
        }

        $this->command->info('Seeded '.count($kategoriKatalogs).' kategori katalog.');
    }
}
