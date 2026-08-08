# DESIGN SPEC — Website TOEFL iBT
Referensi: screenshot dashboard "hance" (learning platform)
Tujuan: dijadikan acuan desain untuk membangun website latihan/persiapan TOEFL iBT

---

## 1. KONSEP VISUAL
Gaya: soft, friendly, modern SaaS dashboard.
Ciri khas: banyak whitespace, sudut sangat membulat (rounded-2xl), kartu putih dengan bayangan tipis, aksen ilustrasi flat berwarna pastel, kontras kuat antara panel gelap (hero) dan panel terang (background).

Frame luar: seluruh dashboard duduk di dalam "card" besar melayang di atas background gelap, seperti mockup browser/app — beri padding besar di sekeliling card utama.

---

## 2. PALET WARNA

### Warna dasar
- Background halaman (di luar card): `#1E1B3B` (navy gelap keunguan)
- Background card utama / kanvas app: `#F4F4F7` (abu sangat terang)
- Background sidebar: `#FFFFFF`
- Background kartu (statistik, assignment, leaderboard): `#FFFFFF`

### Warna hero banner
- Hero banner (dark card "Hello, Alexandra"): `#191A3B` — navy gelap
- Teks di atas hero: putih `#FFFFFF`, sub-teks abu terang `#B9B8CE`

### Warna aksen kartu lesson (thumbnail)
- Ungu pastel: `#E7DEFB`
- Biru pastel: `#D8E9FB`
- Peach/oranye pastel: `#FBE1C6`
(Gunakan 3 warna pastel ini bergantian untuk kartu materi/skill: Reading, Listening, Speaking, Writing)

### Warna teks
- Judul/heading: `#161328` (hampir hitam, sedikit keunguan)
- Body/subtitle: `#6E6E85` (abu keunguan)
- Muted/caption: `#9B9AB0`

### Warna aksen fungsional
- Progress bar terisi: `#161328` (gelap, sama seperti heading)
- Progress bar track (belum terisi): `#E3E3EA`
- Badge poin (koin/skor): ikon oranye `#F5A623` di atas pill `#FDECD8`
- Badge pencapaian: ikon ungu `#7C6FF0` di atas pill lavender muda
- Notifikasi/dot merah: `#FF5A5F`

---

## 3. TIPOGRAFI
- Font: sans-serif geometris/rounded (mis. "General Sans", "Inter", atau "Plus Jakarta Sans")
- Judul besar (H1, mis. "Overview"): Bold, ~28–32px
- Headline hero (mis. "You have completed 8 lessons"): Bold, ~24px, line-height rapat
- Judul kartu (mis. nama course): Semibold, ~16–18px
- Body text: Regular, ~14px, warna abu
- Caption/meta (tanggal, subtitle kecil): Regular, ~12–13px, warna abu muda
- Angka statistik besar: Bold, ~28px

---

## 4. STRUKTUR LAYOUT

```
[Sidebar kiri, fixed, ~260px]   [Konten utama, scrollable]
```

### Sidebar
- Logo + nama brand di atas (icon kotak + wordmark)
- Menu navigasi vertikal dengan ikon di kiri tiap item
- Item aktif: background pill abu muda / bold text + garis vertikal aksen di sisi kiri
- Menu untuk versi TOEFL: Dashboard, Materi (Reading/Listening/Speaking/Writing), Latihan Soal, Try Out, Skor & Pencapaian, Sesi Live/Kelas, Pengajar

### Top bar (konten utama)
- Judul halaman di kiri (H1)
- Search bar di tengah/kanan dengan placeholder shortcut (mis. "Tekan ⌘+F untuk cari")
- Ikon pesan (dengan dot notifikasi), ikon lonceng, avatar profil di ujung kanan

### Hero section (dark banner)
- Sapaan personal ("Halo, [Nama]!") + headline progres belajar (mis. "Kamu sudah menyelesaikan 8 soal minggu ini")
- Tombol pill putih "Lihat Semua"
- 3 kartu horizontal di sisi kanan (scroll/overflow), tiap kartu:
  - Ilustrasi flat pastel di atas
  - Judul skill/materi (bold)
  - Subtitle progres ("5/9 bagian selesai")
  - Progress bar tipis di bawah

### Statistics section
- 4 kartu kecil sejajar, tiap kartu: angka besar bold + label 2 baris di bawahnya
  Contoh untuk TOEFL: "Skor Rata-rata", "Try Out Selesai", "Streak Hari", "Jam Belajar Minggu Ini"

### Assignments / Latihan section
- Header dengan judul + counter ("Latihan Saya (5)") + chevron "lihat semua"
- List item kartu: ikon bulat di kiri, judul tebal, subtitle abu, tanggal di kanan, menu titik tiga

### Leaderboard / Peringkat section (kolom kanan)
- Header "Peringkat" + chevron
- Kartu highlight posisi user sendiri (avatar, "#14", poin, badge)
- List top 3: avatar, nama, kategori/subskill, jumlah poin dengan ikon koin

---

## 5. KOMPONEN UI (style guide)

- **Card**: putih, radius ±20px, shadow lembut (`0 4px 20px rgba(0,0,0,0.04)`), padding 20–24px
- **Button pill**: radius penuh (full rounded), putih dengan teks gelap untuk button di atas dark background; dark/navy dengan teks putih untuk primary action di atas background terang
- **Progress bar**: tinggi tipis (~6px), radius penuh, warna gelap di atas track abu muda
- **Badge/pill kecil**: radius penuh, background pastel lembut, ikon + teks kecil bold
- **Avatar**: lingkaran, border tipis opsional
- **Ikon**: gaya line icon minimalis (seperti Lucide/Feather), stroke tipis konsisten
- **Ilustrasi**: flat, karakter sederhana dengan outline halus, warna selaras dengan aksen pastel kartu

---

## 6. ADAPTASI KONTEN UNTUK WEBSITE TOEFL iBT

Mapping komponen dashboard asli → fitur TOEFL:

| Komponen asli | Adaptasi TOEFL iBT |
|---|---|
| Overview | Dashboard Belajar |
| Content Library | Materi (Reading, Listening, Speaking, Writing) |
| Achievements | Pencapaian / Sertifikat |
| Live Sessions | Kelas Live / Jadwal Try Out |
| Instructor | Pengajar / Mentor |
| "8 lessons this week" | "8 sesi latihan minggu ini" |
| 3 kartu lesson (hero) | 3 skill utama: mis. Speaking Practice, Listening Comprehension, Writing Task |
| Statistics (4 angka) | Skor rata-rata, Try out selesai, Streak harian, Jam belajar |
| My Assignments | Latihan Soal / PR dari mentor |
| Leaders / Leaderboard | Peringkat Skor Nasional/Kelas |
| Poin & badge | Skor TOEFL (0–120) & badge pencapaian skill |

Halaman yang perlu dibangun:
1. Dashboard (sesuai layout di atas)
2. Halaman Materi per skill (Reading/Listening/Speaking/Writing)
3. Halaman Latihan Soal & Try Out (dengan timer, mirip UI test TOEFL asli)
4. Halaman Skor & Progress (grafik perkembangan skor)
5. Halaman Peringkat/Leaderboard
6. Halaman Profil & Pencapaian
7. Halaman Kelas Live/Jadwal

---

## 7. RESPONSIVE
- Desktop: sidebar tetap terlihat, layout 2 kolom (konten utama + leaderboard kanan)
- Tablet: sidebar bisa collapse jadi ikon saja
- Mobile: sidebar jadi bottom nav atau hamburger drawer, kartu hero jadi horizontal-scroll, statistik jadi grid 2x2

---

## 8. CATATAN TAMBAHAN
- Pertahankan kontras tinggi antara hero banner gelap dan background terang untuk hierarki visual.
- Gunakan warna pastel konsisten untuk membedakan 4 skill TOEFL (Reading/Listening/Speaking/Writing) agar mudah dikenali di seluruh halaman.
- Progress bar dan badge poin sebaiknya konsisten dipakai di semua halaman latihan agar terasa "gamified" seperti referensi.
