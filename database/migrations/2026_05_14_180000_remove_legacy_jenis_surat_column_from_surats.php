<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill jenis_surat_id from legacy text when missing.
        if (Schema::hasColumn('surats', 'jenis_surat')) {
            $rows = DB::table('surats')
                ->select('id', 'jenis_surat', 'jenis_surat_id')
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                if (! empty($row->jenis_surat_id)) {
                    continue;
                }

                $name = trim((string) ($row->jenis_surat ?? ''));
                if ($name === '') {
                    $name = 'Lainnya';
                }

                $jenisId = DB::table('jenis_surats')->where('nama', $name)->value('id');
                if (! $jenisId) {
                    $jenisId = DB::table('jenis_surats')->insertGetId([
                        'nama' => $name,
                        'deskripsi' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('surats')
                    ->where('id', $row->id)
                    ->update(['jenis_surat_id' => $jenisId]);
            }
        }

        $fallbackId = DB::table('jenis_surats')->where('nama', 'Lainnya')->value('id');
        if (! $fallbackId) {
            $fallbackId = DB::table('jenis_surats')->insertGetId([
                'nama' => 'Lainnya',
                'deskripsi' => 'Fallback untuk data lama tanpa jenis surat.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('surats')->whereNull('jenis_surat_id')->update(['jenis_surat_id' => $fallbackId]);

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            // Existing FK is nullOnDelete from old migration, so alter FK behavior first.
            Schema::table('surats', function (Blueprint $table) {
                $table->dropForeign(['jenis_surat_id']);
            });

            Schema::table('surats', function (Blueprint $table) {
                $table->foreignId('jenis_surat_id')->nullable(false)->change();
            });

            Schema::table('surats', function (Blueprint $table) {
                $table->foreign('jenis_surat_id')
                    ->references('id')
                    ->on('jenis_surats')
                    ->restrictOnDelete();
            });
        }

        if (Schema::hasColumn('surats', 'jenis_surat')) {
            Schema::table('surats', function (Blueprint $table) {
                $table->dropColumn('jenis_surat');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('surats', 'jenis_surat')) {
            Schema::table('surats', function (Blueprint $table) {
                $table->string('jenis_surat')->nullable()->after('nama_pemohon');
            });
        }

        $rows = DB::table('surats')
            ->leftJoin('jenis_surats', 'surats.jenis_surat_id', '=', 'jenis_surats.id')
            ->select('surats.id', 'jenis_surats.nama')
            ->get();

        foreach ($rows as $row) {
            DB::table('surats')
                ->where('id', $row->id)
                ->update(['jenis_surat' => $row->nama ?? 'Lainnya']);
        }

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            Schema::table('surats', function (Blueprint $table) {
                $table->dropForeign(['jenis_surat_id']);
            });

            Schema::table('surats', function (Blueprint $table) {
                $table->foreignId('jenis_surat_id')->nullable()->change();
            });

            Schema::table('surats', function (Blueprint $table) {
                $table->foreign('jenis_surat_id')
                    ->references('id')
                    ->on('jenis_surats')
                    ->nullOnDelete();
            });
        }
    }
};
