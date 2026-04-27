# Dokumentasi Struktur Database

Berikut adalah rincian skema tabel beserta relasi antar tabel (Foreign Keys):

### 1. Tabel `users`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_user** | bigint(20) UNSIGNED | No | AUTO_INCREMENT | **Primary Key** |
| name | varchar(255) | No | | |
| email | varchar(255) | Yes | UNIQUE | Email untuk OTP dan Google login |
| google_id | varchar(255) | Yes | UNIQUE | ID akun Google yang terhubung |
| otp_code | varchar(255) | Yes | | Hash kode OTP login sementara |
| otp_expires_at | timestamp | Yes | | Batas berlaku OTP |
| otp_requested_at | timestamp | Yes | | Waktu request OTP terakhir |
| password | varchar(255) | No | | |
| password_changed_at | timestamp | Yes | | |
| **role** | int(11) | No | | **Foreign Key** ke `role.id_role` |
| remember_token| varchar(100) | Yes | | |
| created_at | timestamp | Yes | | |
| created_by | int(11) | Yes | | |
| updated_at | timestamp | Yes | | |
| updated_by | int(11) | Yes | | |
| deleted_at | timestamp | Yes | | |
| deleted_by | int(11) | Yes | | |

### 2. Tabel `role`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_role** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| role | varchar(50) | No | | Nama role (admin, pembimbing, dll) |

role table details:
id 1=siswa
id 2=kajur
id 3=instruktur
id 4=pembimbing
id 5=kesiswaan
id 6=kepsek
id 7=admin
id 8=superadmin

### 3. Tabel `siswa`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **nis** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| **id_user** | int(11) | Yes | | **Foreign Key** ke `users.id_users` |
| nama_siswa | varchar(50) | No | | |
| **id_kelas** | int(11) | No | | **Foreign Key** ke `kelas.id_kelas` |
| **id_jurusan**| int(11) | No | | **Foreign Key** ke `jurusan.id_jurusan` |
| **id_rombel** | int(11) | No | | **Foreign Key** ke `rombel.id_rombel` |
| tahun_ajaran | varchar(50) | No | | |
| **id_tempat** | int(11) | Yes | | **Foreign Key** ke `tempat_pkl.id_tempat` |
| **id_instruktur**| int(11) | Yes | | **Foreign Key** ke `instruktur.id_instruktur` |
| **id_pembimbing**| int(11) | Yes | | **Foreign Key** ke `pembimbing.id_pembimbing` |
| created_at | timestamp | Yes | | |
| updated_at | timestamp | Yes | | |

### 4. Tabel `kelas`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_kelas** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| kelas | int(11) | No | | |

### 5. Tabel `jurusan`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_jurusan**| int(11) | No | | **Primary Key** |
| nama_jurusan | varchar(50) | No | | |
| **id_kajur** | int(11) | No | | **Foreign Key** ke `kajur.id_kajur` |

### 6. Tabel `kajur` (Kepala Jurusan)
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_kajur** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| **id_user** | int(11) | Yes | | **Foreign Key** ke `users.id_users` |
| nama_kajur | varchar(50) | No | | |

### 7. Tabel `rombel`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_rombel** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| nama_rombel | varchar(50) | No | | |
| **id_wali** | int(11) | No | | **Foreign Key** ke `users.id_user` |

### 8. Tabel `tempat_pkl`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_tempat** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| nama_perusahaan| varchar(50) | No | | |
| alamat | varchar(255) | No | | |

### 9. Tabel `pembimbing`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_pembimbing**| int(11) | No | AUTO_INCREMENT | **Primary Key** |
| **id_user** | int(11) | Yes | | **Foreign Key** ke `users.id_users` |
| nama_pembimbing| varchar(50) | No | | |

### 10. Tabel `instruktur`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_instruktur**| int(11) | No | AUTO_INCREMENT | **Primary Key** |
| nama_instruktur| varchar(50) | No | | |
| **id_tempat** | int(11) | No | | **Foreign Key** ke `tempat_pkl.id_tempat` |

### 11. Tabel `absensi`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_absensi**| int(11) | No | AUTO_INCREMENT | **Primary Key** |
| **id_siswa** | int(11) | No | | **Foreign Key** ke `siswa.nis` |
| tanggal | date | No | | |
| jam_datang | datetime | Yes | | |
| jam_pulang | datetime | Yes | | |
| ip_datang | int(11) | Yes | | |
| ip_pulang | int(11) | Yes | | |
| ip_address_datang | varchar(45) | Yes | | IP address check-in hasil request |
| ip_address_pulang | varchar(45) | Yes | | IP address check-out hasil request |
| lokasi_datang | varchar(255) | Yes | | Lokasi pendek hasil lookup IP saat datang |
| lokasi_pulang | varchar(255) | Yes | | Lokasi pendek hasil lookup IP saat pulang |
| status | int(11) | No | | |
| keterangan | varchar(255) | Yes | | |
| foto_bukti | varchar(255) | Yes | | |
| foto_bukti_pulang | varchar(255) | Yes | | Foto bukti check-out |
| qr_token | varchar(64) | Yes | | Token QR yang dipakai saat absensi |

### 12. Tabel `agenda`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_agenda** | int(11) | No | AUTO_INCREMENT | **Primary Key** |
| **id_siswa** | int(11) | No | | **Foreign Key** ke `siswa.nis` |
| tanggal | date | No | | |
| rencana_pekerjaan| varchar(255) | Yes | | |
| realisasi_pekerjaan| varchar(255) | Yes | | |
| penugasan_khusus_dari_atasan | varchar(255) | Yes | | |
| penemuan_masalah | varchar(255) | Yes | | |
| catatan | varchar(255) | Yes | | |
| **id_instruktur**| int(11) | Yes | | **Foreign Key** ke `instruktur.id_instruktur` |
| **id_pembimbing**| int(11) | Yes | | **Foreign Key** ke `pembimbing.id_pembimbing` |

### 13. Tabel `penilaian`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id_penilaian**| int(11) | No | AUTO_INCREMENT | **Primary Key** |
| **id_siswa** | int(11) | No | | **Foreign Key** ke `siswa.nis` |
| **id_agenda** | int(11) | No | | **Foreign Key** ke `agenda.id_agenda` |
| senyum | int(11) | No | | Lihat Catatan Penilaian di bawah |
| keramahan | int(11) | No | | Lihat Catatan Penilaian di bawah |
| penampilan | int(11) | No | | Lihat Catatan Penilaian di bawah |
| komunikasi | int(11) | No | | Lihat Catatan Penilaian di bawah |
| realisasi_kerja| int(11) | No | | Lihat Catatan Penilaian di bawah |
| created_at | timestamp | Yes | | |
| created_by | int(11) | Yes | | |
| updated_at | timestamp | Yes | | |
| updated_by | int(11) | Yes | | |

> **Catatan Penilaian:**
> Untuk kolom `senyum`, `keramahan`, `penampilan`, `komunikasi`, dan `realisasi_kerja`, input pada antarmuka web menggunakan sistem *checkbox/radio button*. Logika penyimpanan nilainya adalah sebagai berikut:
> * Jika **Belum dicentang / Belum dinilai**: Tidak bernilai (NULL atau default aplikasi).
> * Jika dicentang **"Baik"**: Disimpan dengan nilai `1`.
> * Jika dicentang **"Kurang"**: Disimpan dengan nilai `0`.

### 14. Tabel `attendance_qr_tokens`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id** | bigint UNSIGNED | No | AUTO_INCREMENT | **Primary Key** |
| token | varchar(64) | No | UNIQUE | Token internal QR |
| payload | varchar(120) | No | UNIQUE | String yang diencode ke QR |
| active_on | date | No | INDEX | Tanggal aktif QR |
| expires_at | timestamp | Yes | | Batas akhir pemakaian QR |
| created_by | bigint UNSIGNED | Yes | | User pembuat QR |
| used_count | int UNSIGNED | No | default 0 | Jumlah scan sukses |
| created_at | timestamp | Yes | | |
| updated_at | timestamp | Yes | | |

### 15. Tabel `activity_logs`
| Nama Kolom | Tipe Data | Null | Ekstra | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| **id** | bigint UNSIGNED | No | AUTO_INCREMENT | **Primary Key** |
| user_id | bigint UNSIGNED | Yes | INDEX | ID user pelaku aktivitas |
| user_name | varchar(255) | Yes | | Snapshot nama user |
| role_name | varchar(255) | Yes | | Snapshot role user |
| module_key | varchar(100) | Yes | INDEX | Modul terkait |
| action | varchar(100) | No | | Nama aksi |
| description | varchar(255) | No | | Deskripsi aktivitas |
| route_name | varchar(255) | Yes | INDEX | Nama route Laravel |
| http_method | varchar(10) | Yes | | GET/POST/PUT/DELETE |
| path | varchar(255) | Yes | | Path request |
| status_code | smallint UNSIGNED | Yes | | Status response |
| ip_address | varchar(45) | Yes | | IP request user |
| location_label | varchar(255) | Yes | | Ringkasan lokasi hasil lookup IP |
| subject_type | varchar(255) | Yes | | Nama tabel/entitas target |
| subject_id | varchar(255) | Yes | | ID entitas target |
| properties | json | Yes | | Payload tambahan ter-sanitasi |
| created_at | timestamp | Yes | INDEX | Waktu aktivitas |
