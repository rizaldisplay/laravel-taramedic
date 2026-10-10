<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ==========================================
        // 1. TABEL UTAMA: sdmks
        // ==========================================
        Schema::create('sdmks', function (Blueprint $table) {
            $table->id();
            
            // --- Identitas Sistem ---
            $table->string('sdmk_id')->unique()->comment('Dibuat otomatis oleh sistem, cth: SDMK-0006');
            $table->string('foto_profil_url')->nullable();

            // --- Identitas Pribadi ---
            $table->string('nik', 16)->unique();
            $table->string('nama_lengkap');
            $table->string('gelar_depan')->nullable();
            $table->string('gelar_belakang')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->string('kewarganegaraan')->default('Indonesia');
            $table->string('no_hp', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('alamat_lengkap');
            
            // Wilayah
            $table->string('provinsi')->nullable();
            $table->string('kabupaten_kota')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kelurahan_desa')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('kode_pos', 10)->nullable();

            // --- Kontak Darurat ---
            $table->string('darurat_nama_kontak');
            $table->string('darurat_hubungan')->nullable();
            $table->string('darurat_no_hp', 20);

            // --- Profesi & Kompetensi ---
            $table->string('jenis_sdmk')->comment('cth: Tenaga Medis');
            $table->string('kode_profesi_internal')->nullable();
            $table->string('profesi');
            $table->string('pendidikan_terakhir');
            $table->string('kompetensi_utama');
            $table->string('institusi_pendidikan')->nullable();
            $table->string('spesialisasi')->nullable();
            $table->year('tahun_lulus')->nullable();
            $table->string('sub_spesialisasi')->nullable();
            $table->string('status_sdmk')->default('Draf');

            // --- STR (Surat Tanda Registrasi) ---
            $table->string('str_nomor')->nullable();
            $table->date('str_tanggal_terbit')->nullable();
            $table->date('str_berlaku_hingga')->nullable();
            $table->string('str_organisasi_penerbit')->nullable();
            $table->string('str_status')->nullable();
            $table->date('str_tanggal_verifikasi')->nullable();
            $table->string('sertifikat_lain_opsional')->nullable();
            $table->string('lampiran_sertifikat_lain_url')->nullable();

            // --- Jadwal Master ---
            $table->string('jadwal_tipe')->nullable();
            $table->integer('jadwal_kapasitas_per_hari')->nullable();
            $table->string('jadwal_durasi_layanan')->nullable();
            $table->string('jadwal_status')->nullable();
            $table->json('jadwal_jenis_pasien')->nullable()->comment('Menyimpan array ["Umum", "BPJS"]');

            // --- Integrasi: SatuSehat ---
            $table->string('ihs_nomor')->nullable();
            $table->string('ihs_nama')->nullable();
            $table->string('ihs_kesiapan_data')->nullable();

            // --- Integrasi: BPJS ---
            $table->string('bpjs_nomor_provider')->nullable();
            $table->string('bpjs_nama')->nullable();
            $table->string('bpjs_jenis_provider')->nullable();
            $table->string('bpjs_status_kredensial')->nullable();

            // --- Integrasi: FHIR API ---
            $table->string('fhir_endpoint_base_url')->nullable();
            $table->string('fhir_versi')->nullable();
            $table->string('fhir_autentikasi_client_id')->nullable();
            $table->string('fhir_status_koneksi')->nullable();

            // --- Catatan ---
            $table->text('catatan_internal')->nullable();

            $table->timestamps();
            $table->softDeletes(); // Disarankan untuk data rekam kesehatan / ERP
        });

        // ==========================================
        // 2. TABEL RELASI: sdmk_sips (Surat Izin Praktik)
        // ==========================================
        Schema::create('sdmk_sips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdmk_id')->constrained('sdmks')->onDelete('cascade');
            
            $table->string('nomor_sip');
            $table->string('jenis_sip');
            $table->date('tanggal_terbit');
            $table->date('berlaku_hingga');
            $table->string('status_sip');
            
            $table->string('tempat_terbit')->nullable();
            $table->string('nama_fasilitas');
            $table->string('unit_poli');
            $table->string('lokasi_ruangan')->nullable();
            $table->text('alamat_praktik')->nullable();
            $table->string('lampiran_dokumen_url')->nullable();
            
            $table->timestamps();
        });

        // ==========================================
        // 3. TABEL RELASI: sdmk_penempatans
        // ==========================================
        Schema::create('sdmk_penempatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdmk_id')->constrained('sdmks')->onDelete('cascade');
            
            $table->string('fasyankes');
            $table->string('unit_poli');
            $table->string('lokasi_ruangan')->nullable();
            $table->string('peran_di_unit')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('status_penempatan');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_berakhir')->nullable();
            $table->string('penanggung_jawab_unit')->nullable();
            $table->text('catatan')->nullable();
            
            $table->timestamps();
        });

        // ==========================================
        // 4. TABEL RELASI: sdmk_jadwal_slots
        // ==========================================
        Schema::create('sdmk_jadwal_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdmk_id')->constrained('sdmks')->onDelete('cascade');
            
            $table->string('hari')->comment('Senin, Selasa, dst');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->time('jam_istirahat_mulai')->nullable();
            $table->time('jam_istirahat_selesai')->nullable();
            
            $table->integer('kapasitas_kuota')->nullable();
            $table->integer('kuota_online')->nullable();
            
            $table->date('berlaku_mulai')->nullable();
            $table->date('berlaku_hingga')->nullable();
            $table->string('status_slot')->default('Aktif');
            
            $table->timestamps();
        });
        
        // ==========================================
        // 5. TABEL RELASI: sdmk_kompetensi_tambahans
        // ==========================================
        Schema::create('sdmk_kompetensi_tambahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdmk_id')->constrained('sdmks')->onDelete('cascade');
            $table->string('nama_sertifikat');
            $table->string('file_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdmk_kompetensi_tambahans');
        Schema::dropIfExists('sdmk_jadwal_slots');
        Schema::dropIfExists('sdmk_penempatans');
        Schema::dropIfExists('sdmk_sips');
        Schema::dropIfExists('sdmks');
    }
};