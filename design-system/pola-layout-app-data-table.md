# Pola Layout AppDataTable

Blueprint untuk menyalin **layout dan perilaku** tabel generik `AppDataTable` ke project lain dengan stack yang sama. Palet warna tidak disalin: seluruh warna wajib mengikuti design system project target.

- Sumber acuan: `resources/js/Components/Ui/AppDataTable.vue` (2.332 baris), `resources/js/Composables/useTable.ts`, komponen `resources/js/Components/Ui/App*`.
- Stack acuan: Laravel + Inertia v2 + Vue 3 `<script setup>` + TanStack Table v9 + Tailwind CSS v4 + Ziggy.
- Status dokumen: blueprint layout dan spesifikasi perilaku, bukan source code siap pakai.
- Cakupan perilaku: full parity (client-side, server-side, persistence, highlight).

> **Aturan warna:** dokumen ini tidak menetapkan nilai warna. Kolom "referensi sumber" hanya menunjuk elemen yang dimaksud; jangan menyalin class, hex, rgb, atau nama token warna project sumber. Isi kolom token target dengan token design system project target.

---

## 1. Prinsip

1. Yang disalin: struktur DOM, hierarki komponen, ukuran, spacing, radius, sticky positioning, z-index, alur interaksi, state, dan kontrak data.
2. Yang tidak disalin: nilai warna, palet, dan token visual project sumber.
3. Komponen pendukung (`AppDropdown`, `AppFilterDropdown`, `AppIcon`, `AppTabs`) memakai komponen project target. Dokumen ini hanya menetapkan kontrak API minimum.
4. Jangan memigrasikan tabel/halaman existing di target tanpa persetujuan. Sediakan komponen generik terlebih dahulu.
5. Perilaku server-side mengikuti kontrak backend di §11; jangan mengarang query param baru.
6. Layout harus tetap utuh tanpa JavaScript: header, kolom, dan footer berada dalam struktur tabel yang sama.

---

## 2. Dependensi

| Kategori | Kebutuhan | Dipakai untuk |
|---|---|---|
| Vue | `vue ^3.4` | `<script setup>`, slot, computed, watcher |
| Tabel | `@tanstack/vue-table ^9` | `tableFeatures`, `createTableHook`, `FlexRender`, sorting/pagination/filter/column visibility |
| Inertia | `@inertiajs/vue3 ^2` | `router.visit`, `router.on('start'/'finish'/'flash')`, `usePage` |
| Ikon | `@tabler/icons-vue ^3` | minimal `IconSortDescendingShapes` untuk tombol sort mobile |
| Dropdown/tooltip | `floating-vue ^5` | `VDropdown` dan directive `v-tooltip` |
| Skeleton | `@aejkatappaja/phantom-ui ^0.7` | custom element `<phantom-ui>` |
| Routing | Ziggy | global `route()` dan `window.route` |
| Styling | Tailwind CSS v4 | token via `@theme`, varian `dark:` berbasis class |

Registrasi global yang dibutuhkan:

- `import '@aejkatappaja/phantom-ui'`
- `import FloatingVue from 'floating-vue'` + `import 'floating-vue/dist/style.css'`, lalu `app.use(FloatingVue)`
- `window.route = route` dari Ziggy

Jika salah satu tidak tersedia, komponen tidak boleh diimplementasikan setengah jalan. Laporkan gap dan minta keputusan.

---

## 3. Peta komponen

```text
AppDataTable.vue
|-- Header desktop
|   |-- Baris judul + actions
|   |-- Baris tabs + tabs-actions
|   `-- Baris toolbar (toolbar-start, column toggle, filter, search)
|-- Header mobile (hanya jika slot mobile dipakai)
|-- Viewport tabel
|   |-- Header sticky (termasuk grouped header)
|   |-- Body (skeleton / rows / empty)
|   |-- Kolom aksi sticky
|   `-- Tombol panah scroll horizontal
|-- Footer pagination desktop
`-- Footer pagination mobile

Composables/useTable.ts
|-- features (sorting, pagination, global filter, column visibility)
`-- useTable(options) + tableStateQuery(tableKey)
```

---

## 4. Anatomi layout desktop

### 4.1 Container

- Satu card membungkus seluruh header, tabel, dan footer.
- Sudut membulat sedang, tanpa padding internal pada container.
- `overflow-visible` agar menu dropdown/tooltip tidak terpotong.
- Varian `activity` memakai container yang lebih tertutup (`overflow-hidden`), radius lebih besar, border, dan bayangan lebih tegas. Varian ini juga mengubah warna header dan footer mengikuti peran "activity header" (§8).
- Container tidak memiliki tinggi tetap. Tinggi ditentukan header + viewport tabel + footer.

### 4.2 Header desktop

Header hanya dirender jika salah satu terpenuhi: ada `title`, `searchable`, ada `filters`, ada slot `actions`, atau ada `tabs`.

Urutan baris:

1. **Baris judul**
   - Dirender jika ada `title`, slot `actions`, atau `searchInTitle && searchable`.
   - Padding keliling 24px.
   - Kiri: judul dengan gaya headline kecil (18/24, semibold).
   - Varian `activity`: ada titik aksen 12×12 di kiri judul jika `showTitleIcon`.
   - Kanan: input search (jika `searchInTitle`) selebar 256px di desktop, lalu slot `actions`.
   - Di layar sempit, kolom kiri dan kanan menumpuk vertikal dengan jarak 16px; di `md` ke atas menjadi satu baris dengan `justify-between`.

2. **Baris tabs**
   - Dirender jika `tabs` berisi item.
   - Padding horizontal 24px, vertikal 12px.
   - `AppTabs` mengisi lebar yang tersedia; varian `preview-pills` memakai lebar fit-content dengan maksimum 100%.
   - Slot `tabs-actions` di kanan, tidak menyusut.
   - Jika baris judul ada, beri border atas sebagai pemisah.

3. **Baris toolbar**
   - Dirender jika `searchable && !searchInTitle`, atau `columnToggle`, atau ada `filters`, atau ada slot `toolbar-start`.
   - Padding horizontal 24px, vertikal 12px, `flex-wrap`, jarak antar item 12px.
   - Urutan item: `toolbar-start` (opsional) → column toggle → filter → search.
   - `toolbarStartAlign = 'right'`: slot `toolbar-start` didorong ke kanan (`md:ml-auto`).
   - Column toggle mendapat `md:ml-auto` jika tidak ada `toolbar-start` atau `toolbarStartAlign = 'left'`.
   - Filter memakai `ml-auto`; jika column toggle tampil, pada `md` menjadi `md:ml-0`.
   - Search memakai lebar penuh di mobile dan 256px di desktop; mendapat `md:ml-auto` jika column toggle dan filter tidak tampil.
   - Border atas ditambahkan jika ada baris judul, filter, atau slot `actions`.

Detail kontrol header:

- **Tombol column toggle**: pill, tinggi 36px, lebar 112px (desktop 128px), ikon + label "Kolom", border, background subtle, fokus ring aksen.
- **Menu column toggle**: absolute di bawah tombol, lebar 224px, maksimum tinggi 320px, scroll vertikal, radius sedang, border, bayangan, padding 8px. Isi: tombol "Pilih Semua" dengan checkbox, divider, lalu daftar kolom dengan checkbox. Item terakhir yang terlihat tidak boleh disembunyikan.
- **Tombol filter**: pill, tinggi 36px, lebar minimum 112px (desktop 128px), ikon + label "Filter" + badge jumlah filter aktif. Badge hanya menghitung filter yang tidak `hidden` dan nilainya tidak kosong.
- **Menu filter**: absolute kanan, lebar `min(384px, calc(100vw - 80px))`, padding 16px, radius sedang, border, bayangan, z-index di atas tabel. Isi: baris label (128px) + dropdown per filter, slot `filter-extra`, lalu dua tombol setinggi 36px: Reset dan Terapkan.
- **Input search toolbar**: pill, tinggi 36px, ikon search di kiri, padding kiri 36px, lebar 256px di desktop.
- **Input search judul**: pill, background subtle, padding vertikal 8px, padding kiri 36px, lebar 256px di desktop.

### 4.3 Viewport tabel

- Pembungkus luar `relative`; area scroll memakai `overflow-x-auto`.
- `overflow-y` mengikuti `tableMaxHeight`:
  - `none` jika `pageScrollAtVisibleLimit` dan page size ≤ `tableVisibleRows`.
  - Selain itu: `calc(max(tableVisibleRows, 1) * 56px + 64px)`, yaitu 56px per baris + 64px untuk header.
  - Prop `tableMaxHeight` menimpa perhitungan tersebut.
- Scroll horizontal memakai `overscroll-x-contain`; scroll vertikal memakai `overscroll-y-auto`.
- Tabel memakai `w-full text-left`.

**Header tabel**

- Baris header sticky di `top: 0`.
- Sel header: padding horizontal 24px, vertikal 16px, label kapital 12/16 dengan tracking lebar, teks muted, `whitespace-nowrap`.
- Header sortable memakai kursor pointer dan `select-none`; klik area header memicu sorting.
- Ikon sort: `arrow_upward` (asc), `arrow_downward` (desc), `unfold_more` (belum disortir). Warna ikon mengikuti peran aksen saat aktif, muted saat tidak.
- Perataan sel mengikuti `meta.align` (`left`, `center`, `right`).
- Varian `activity`: padding 16px/14px, teks 11px bold, background header mengikuti peran activity.

**Grouped header** (`meta.group` pada salah satu kolom)

- Header terdiri dari dua baris:
  1. Baris atas: sel grup dengan `colspan` sesuai jumlah kolom terlihat dalam grup. Kolom tanpa grup memakai `rowspan=2`.
  2. Baris bawah: hanya sel anak dari grup, sticky di `top: 47px`, border bawah, `whitespace-nowrap`.
- Kolom yang disembunyikan tidak dihitung dalam `colspan` dan tidak dirender.
- Kolom nomor otomatis ikut dihitung, sehingga jumlah sel header selalu sejajar dengan body.
- Offset 47px adalah tinggi baris header pertama pada tipografi acuan. Jika tipografi/padding target berbeda, hitung ulang dan sesuaikan agar tidak ada celah atau tumpang tindih.

**Body**

- Baris data memakai border pemisah antar baris (divider), background surface, dan transisi warna 150ms.
- Hover baris mengikuti peran hover row.
- Sel body: padding horizontal 16px, vertikal 16px, teks body 14/20, `whitespace-nowrap`.
- Sel mengikuti `meta.align` dan `meta.cellClass`.
- Baris membawa atribut `data-row-id` (`row.original.id ?? row.id`) untuk restore dan highlight.
- Baris yang di-highlight mendapat class animasi dan `aria-label` "Baris baru diperbarui".
- Kolom aksi:
  - Dikenali dari `id === 'actions'` atau `accessorKey === 'actions'`.
  - Tidak bisa disortir.
  - Lebar `w-px min-w-25` dengan padding 12px/12px.
  - Saat tabel overflow horizontal: sticky di kanan, z-index di atas body, border kiri, dan bayangan tipis ke kiri.
  - Header kolom aksi ikut sticky di kanan dengan z-index lebih tinggi dari sel.
  - Isi sel aksi dibungkus `flex min-w-max flex-nowrap items-center justify-center whitespace-nowrap`.
- Baris kosong: satu sel `colspan` seluruh kolom, padding vertikal 64px, teks tengah, ikon `search_off` ukuran besar, pesan `emptyMessage`.
- Loading: lima baris skeleton dengan jumlah sel = kolom terlihat. Setiap sel menampilkan teks header sebagai placeholder dengan `minWidth`:
  - 160px untuk header yang mengandung nama/name/judul/title/deskripsi/description/visi/alamat.
  - 90px untuk kode/code/sks/semester/tahun/nilai/status/jenjang.
  - 120px untuk lainnya.
  - `minWidth` eksplisit dari `columnDef.minWidth` menang.
  - Header tabel diberi `data-shimmer-ignore` agar tidak ikut shimmer.
- Jika semua kolom disembunyikan: tampilkan state "Tidak ada kolom yang ditampilkan." setinggi minimum 384px, bukan tabel kosong.

**Tombol panah horizontal**

- Muncul hanya jika ada overflow ke arah tersebut, dengan toleransi 8px.
- Tombol bulat 40×40, absolute, z-index di atas tabel, posisi vertikal di tengah area data (setelah header).
- Panah kiri di kiri container; panah kanan di kanan container.
- Jika kolom aksi terlihat, panah kanan digeser sebesar lebar kolom aksi agar tidak menutupi aksi.
- Klik menggeser 75% lebar viewport atau minimum 240px, smooth.
- Status panah diperbarui saat scroll, resize, perubahan data, perubahan kolom, dan perubahan page size.

### 4.4 Footer desktop

- Dirender jika `showPagination && !empty`; disembunyikan di mobile jika slot `mobile` dipakai.
- Border atas, padding 24px/16px, latar footer subtle.
- Kiri: "Menampilkan X-Y dari N data" dengan X/Y/N semibold.
- Kanan: dropdown "baris per halaman" (lebar minimum 80px) + navigasi halaman.
- Navigasi hanya tampil jika jumlah halaman > 1:
  - Tombol sebelumnya/berikutnya bulat 32×32, disabled saat tidak tersedia, opacity 40%.
  - Nomor halaman bulat 32×32, minimum lebar 32px, padding horizontal 8px.
  - Halaman aktif memakai background aksen dan teks di atas aksen.
  - Elipsis dirender sebagai teks, bukan tombol.
- Varian `activity`: background footer mengikuti peran activity.

---

## 5. Anatomi layout mobile

Mobile hanya aktif jika slot `mobile` disediakan. Jika tidak, tabel desktop tetap dipakai di semua ukuran.

- Section mobile `md:hidden`; section desktop `hidden md:block`.
- Header mobile: border bawah, padding 16px, background header.
  - Baris atas: judul (jika ada) + slot `mobile-actions` + tombol sort + tombol print.
  - Baris search: input tinggi 40px, radius field, ikon search di kiri, lebar penuh.
  - `mobileSearchOnly` menyembunyikan baris judul/aksi dan hanya menampilkan search.
  - `hideMobileSearch` menyembunyikan input search.
  - `mobileSearchPlaceholder` mengubah placeholder.
  - `mobilePrint` menampilkan tombol print.
- Body mobile: padding 16px.
  - Loading: empat skeleton card tinggi 76px, radius field, `aria-busy`.
  - Data: slot `mobile` dengan scoped slot `rows`; parent merender kartu.
  - Kosong: state kosong dengan ikon + pesan, tinggi minimum 192px.
- Footer mobile: border atas, padding 16px/12px.
  - Kiri: "X-Y dari N".
  - Kanan: dropdown page size + tombol sebelumnya/berikutnya + indikator `halaman/total` jika total halaman > 1.
- Tombol kontrol mobile: 40×40, radius field, border.
- Sort mobile: dropdown berisi daftar kolom yang bisa disortir; item aktif menampilkan ikon arah dan warna aksen; klik men-toggle asc/desc.

---

## 6. Layout skeleton (color-agnostic)

Gunakan skeleton berikut sebagai urutan DOM. Placeholder `{{...}}` wajib diganti token project target. Class non-warna (spacing, flex, sticky, radius, z-index) dipertahankan.

```html
<div class="relative overflow-visible rounded-xl {{card-surface}} {{card-border}}">
  <!-- ================= MOBILE (< md) ================= -->
  <div class="overflow-hidden rounded-2xl md:hidden">
    <div class="border-b p-4 {{divider}} {{mobile-header-surface}}">
      <div class="flex items-center justify-between gap-3">
        <h3 class="min-w-0 flex-1 text-headline-sm {{text-primary}}">Judul</h3>
        <div class="ml-auto flex shrink-0 items-center gap-2">
          <!-- slot mobile-actions, dropdown sort, tombol print -->
        </div>
      </div>
      <div class="mt-3 flex items-center gap-2">
        <div class="relative min-w-0 flex-1">
          <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2">{{icon-muted}}</span>
          <input class="h-10 w-full rounded-xl border py-0 pl-9 pr-3 text-body-md {{input-bg}} {{input-border}} {{focus-ring}}">
        </div>
      </div>
    </div>

    <div class="p-4">
      <!-- loading: 4 x <div class="h-[76px] animate-pulse rounded-xl {{skeleton-bg}}"> -->
      <!-- data: <slot name="mobile" :rows="rows" /> -->
      <!-- kosong: ikon + pesan, min-h-48 flex-col items-center justify-center -->
    </div>

    <div class="flex items-center justify-between gap-3 border-t px-4 py-3 {{divider}} {{footer-surface}}">
      <!-- ringkasan halaman + page size + prev/next -->
    </div>
  </div>

  <!-- ================= HEADER DESKTOP (md+) ================= -->
  <div class="border-b {{divider}} {{header-surface}}">
    <!-- Baris 1: judul + actions -->
    <div class="p-6">
      <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <h3 class="flex items-center gap-2 text-headline-sm {{text-primary}}">
          <!-- activity: <span class="h-3 w-3 shrink-0 rounded-full {{accent-bg}}"></span> -->
          Judul
        </h3>
        <div class="flex w-full flex-wrap items-center gap-3 md:w-auto">
          <!-- searchInTitle: input rounded-full pl-9 pr-4 py-2 text-body-md -->
          <!-- slot actions -->
        </div>
      </div>
    </div>

    <!-- Baris 2: tabs -->
    <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-3 {{divider-top}}">
      <AppTabs class="!mb-0 min-w-0" />
      <div class="shrink-0"><!-- slot tabs-actions --></div>
    </div>

    <!-- Baris 3: toolbar -->
    <div class="flex flex-wrap items-center gap-3 px-6 py-3 {{divider-top}}">
      <!-- slot toolbar-start -->
      <!-- column toggle: tombol h-9 rounded-full border px-4 text-body-md -->
      <!-- filter: tombol h-9 rounded-full border px-4 text-body-md + badge -->
      <!-- search: input rounded-full pl-9 pr-4 py-2 text-body-md w-full md:w-64 -->
    </div>
  </div>

  <!-- ================= TABEL ================= -->
  <div>
    <!-- semua kolom tersembunyi -->
    <div class="flex min-h-96 items-center justify-center px-6 {{text-muted}}">
      Tidak ada kolom yang ditampilkan.
    </div>

    <phantom-ui :loading="isTableLoading" fallback-radius="9999">
      <div class="relative">
        <div ref="scrollContainer" class="overflow-x-auto {{scrollbar}}">
          <table class="w-full text-left">
            <thead data-shimmer-ignore>
              <!-- normal -->
              <tr>
                <th class="sticky top-0 z-10 whitespace-nowrap px-6 py-4 text-label-caps uppercase {{thead-bg}} {{text-muted}}">
                  Header
                </th>
                <!-- kolom aksi: sticky right-0 z-20 + border-l + shadow saat overflow -->
              </tr>

              <!-- grouped: baris atas grup (colspan) + baris bawah sticky top-[47px] -->
            </thead>

            <tbody class="divide-y {{divider}} {{body-surface}}">
              <!-- loading: 5 baris skeleton, jumlah sel = kolom terlihat -->
              <!-- data -->
              <tr class="group transition-colors duration-150 {{row-hover}}">
                <td class="whitespace-nowrap px-4 py-4 text-body-md {{text-primary}}">...</td>
                <td data-action-column class="sticky right-0 z-10 w-px min-w-25 px-3 py-3 align-middle {{action-cell-bg}} {{action-cell-hover}}">
                  <!-- aksi -->
                </td>
              </tr>
              <!-- kosong: colspan = jumlah semua kolom -->
            </tbody>

            <tfoot v-if="$slots.tfoot">
              <!-- slot tfoot -->
            </tfoot>
          </table>
        </div>

        <!-- panah kiri/kanan: absolute z-30 h-10 w-10 rounded-full -translate-y-1/2 -->
      </div>
    </phantom-ui>
  </div>

  <!-- ================= FOOTER DESKTOP ================= -->
  <div class="flex flex-col items-center justify-between gap-3 border-t px-6 py-4 sm:flex-row {{divider}} {{footer-surface}}">
    <p class="text-body-md {{text-muted}}">
      Menampilkan <span class="font-semibold {{text-primary}}">X-Y</span>
      dari <span class="font-semibold {{text-primary}}">N</span> data
    </p>
    <div class="flex w-full flex-col items-center gap-3 sm:w-auto sm:flex-row sm:justify-end">
      <!-- AppFilterDropdown page size + navigasi halaman -->
    </div>
  </div>
</div>
```

---

## 7. Ukuran, spacing, radius, tipografi

| Elemen | Nilai acuan | Catatan |
|---|---|---|
| Padding baris judul | 24px | `p-6` |
| Padding baris tabs/toolbar | 24px horizontal, 12px vertikal | `px-6 py-3` |
| Gap toolbar | 12px | `gap-3` |
| Padding sel header | 24px horizontal, 16px vertikal | 16px/14px untuk varian activity |
| Padding sel body | 16px horizontal, 16px vertikal | `px-4 py-4` |
| Padding sel aksi | 12px | `px-3 py-3` |
| Padding footer | 24px horizontal, 16px vertikal | mobile 16px/12px |
| Tinggi kontrol toolbar | 36px | tombol column/filter/page size |
| Tinggi input search mobile | 40px | `h-10` |
| Tinggi tombol pagination | 32px | desktop; mobile 36px |
| Radius card | 12px | activity 16px |
| Radius field/tombol pill | penuh (pill) atau 12px | mengikuti design system target |
| Radius menu | 12px | dropdown filter/column |
| Lebar menu column toggle | 224px, maks 320px tinggi | scroll internal |
| Lebar menu filter | `min(384px, calc(100vw - 80px))` | |
| Max tinggi viewport | `max(visibleRows,1) * 56px + 64px` | atau prop `tableMaxHeight` |
| Breakpoint mobile/desktop | 768px (`md`) | |
| Durasi transisi hover | 150ms | |
| Durasi highlight | 800ms | animasi background |
| Debounce search (server) | 300ms | |
| Debounce persist state | 300ms | |
| Retry render highlight | 60 kali × 50ms | |
| Toleransi overflow horizontal | 8px | |
| Judul | headline kecil 18/24, semibold | |
| Header tabel | label kapital 12/16, tracking lebar, uppercase | |
| Body/control | body 14/20 | |
| Badge | label 10/14, semibold | |
| Footer | body 14/20; mobile 12px | |

Tipografi memakai peran, bukan nama class sumber. Petakan ke skala tipografi target.

---

## 8. Color Role Mapping

Isi kolom terakhir dengan token target. Jangan menyalin nilai dari kolom referensi sumber.

| # | Elemen layout | Peran visual | Referensi elemen di sumber (jangan disalin nilainya) | Token target |
|---|---|---|---|---|
| 1 | Container card | Permukaan utama | surface | |
| 2 | Container border | Garis batas netral | border netral | |
| 3 | Container shadow | Elevasi card | glass shadow | |
| 4 | Header tabel | Permukaan header/subtle | surface subtle | |
| 5 | Teks header tabel | Teks muted, uppercase | teks muted | |
| 6 | Body tabel | Permukaan data | surface | |
| 7 | Teks body | Teks utama | teks utama | |
| 8 | Teks sekunder | Teks muted | teks muted | |
| 9 | Divider | Garis pemisah | border/divider | |
| 10 | Hover baris | Permukaan hover | surface hover | |
| 11 | Sel aksi | Permukaan sel aksi | surface | |
| 12 | Hover sel aksi | Permukaan hover aksi | surface hover | |
| 13 | Border/shadow kolom aksi sticky | Pemisah saat sticky | border + shadow | |
| 14 | Aksen | Warna brand/aksi utama | accent | |
| 15 | Tint aksen | Latar transparan aksen | accent/10 | |
| 16 | Teks di atas aksen | Kontras di atas aksen | on-accent | |
| 17 | Focus ring | Cincin fokus | accent | |
| 18 | Input search | Permukaan input | input surface | |
| 19 | Border input | Garis input | border netral | |
| 20 | Badge filter | Latar badge + teks | accent + on-accent | |
| 21 | Header varian activity | Permukaan header activity | container tint | |
| 22 | Teks header activity | Teks header activity | on-container | |
| 23 | Skeleton | Permukaan skeleton | surface high | |
| 24 | Empty icon | Ikon kosong | muted dengan opacity | |
| 25 | Menu surface/border/shadow | Permukaan menu | surface + border + shadow | |
| 26 | Hover menu | Permukaan hover menu | surface variant | |
| 27 | Checkbox | Aksen checkbox | accent | |
| 28 | Scroll arrow | Permukaan/tombol panah | surface + accent + border + shadow | |
| 29 | Tooltip | Permukaan/teks tooltip | theme floating-vue target | |
| 30 | Dark mode | Seluruh peran di atas | token dark target | |

Aturan tambahan:

- Jika target memakai `dark` mode berbasis class, pastikan setiap peran punya pasangan dark yang eksplisit.
- Jangan memakai opacity warna sebagai pengganti token; gunakan token tint yang disediakan design system target.
- Highlight baris memakai tint aksen (sekitar 8% campuran) selama 800ms, lalu kembali transparan.
- Warna status (aktif/nonaktif, error, sukses) bukan bagian dari `AppDataTable`; itu tanggung jawab definisi kolom di halaman.

---

## 9. Kontrak komponen

### 9.1 Props

| Prop | Tipe | Default | Fungsi |
|---|---|---|---|
| `tableKey` | String | `app-data-table` | Identitas tabel untuk persistence dan highlight |
| `columns` | Array | wajib | Definisi kolom TanStack |
| `data` | Array | wajib | Baris data (satu halaman pada mode server) |
| `title` | String | null | Judul tabel |
| `searchable` | Boolean | true | Tampilkan search |
| `pageSize` | Number | 10 | Ukuran halaman awal |
| `pageSizeOptions` | Array | `[10,25,50,100]` | Opsi ukuran halaman |
| `tableVisibleRows` | Number | 10 | Jumlah baris yang memicu max-height |
| `tableMaxHeight` | String | null | Max-height eksplisit |
| `pageScrollAtVisibleLimit` | Boolean | true | Nonaktifkan scroll internal jika data ≤ visible rows |
| `showPagination` | Boolean | true | Tampilkan footer pagination |
| `emptyMessage` | String | `Tidak ada data.` | Pesan kosong |
| `filters` | Array | `[]` | Definisi filter (`key`, `label`, `options`, `hidden`, `allOption`) |
| `initialFilters` | Object | null | Nilai default saat reset/restore |
| `fixedFilters` | Object | `{}` | Filter tetap yang tidak tampil di UI |
| `columnToggle` | Boolean | true | Tampilkan menu kolom |
| `showTitleIcon` | Boolean | true | Titik aksen judul varian activity |
| `searchInTitle` | Boolean | false | Pindahkan search ke baris judul |
| `mobileSearchPlaceholder` | String | `Cari...` | Placeholder search mobile |
| `mobilePrint` | Boolean | false | Tombol print mobile |
| `mobileSearchOnly` | Boolean | false | Mobile hanya search |
| `hideMobileSearch` | Boolean | false | Sembunyikan search mobile |
| `showRowNumber` | Boolean | true | Kolom nomor otomatis |
| `loading` | Boolean | false | State loading eksternal |
| `highlightIds` | Array | `[]` | ID baris yang di-highlight via prop |
| `highlightPage` | Number | null | Halaman target highlight |
| `draggable` | Boolean | false | Aktifkan drag reorder baris |
| `tabs` | Array | null | Item tabs |
| `activeTab` | String | null | Tab aktif |
| `tabsVariant` | String | `chrome` | Varian AppTabs |
| `serverSide` | Boolean | false | Aktifkan mode server |
| `serverTotalRows` | Number | 0 | Total baris dari server |
| `reloadOnly` | Array | `[]` | Prop Inertia yang di-reload |
| `toolbarStartAlign` | String | `right` | `left` atau `right` |
| `variant` | String | `default` | `default` atau `activity` |

### 9.2 Emits

| Event | Payload | Kapan |
|---|---|---|
| `row-drop` | `{ from: number, to: number }` | Baris di-drop pada posisi baru |
| `update:activeTab` | String | Tab berubah |
| `filter-change` | Object | Nilai filter berubah |
| `filters-reset` | - | Filter direset |

### 9.3 Slots

| Slot | Scope | Fungsi |
|---|---|---|
| `mobile` | `{ rows }` | Rendering kartu mobile |
| `mobile-actions` | - | Aksi di header mobile |
| `actions` | - | Aksi di baris judul desktop |
| `toolbar-start` | - | Item tambahan di awal toolbar |
| `tabs-actions` | - | Aksi di baris tabs |
| `filter-extra` | - | Field tambahan di menu filter |
| `grouped-body` | `{ rows }` | Body kustom saat grouped header dipakai |
| `tfoot` | - | Footer tabel kustom |

### 9.4 Definisi kolom

- `accessorKey` atau `id`.
- `header` string atau render function.
- `cell` render function TanStack.
- `enableSorting` false untuk kolom non-sortir.
- `minWidth` angka (px) untuk skeleton.
- `meta.align`: `left`, `center`, `right`.
- `meta.cellClass`: class tambahan sel.
- `meta.group`: label grup header.
- Kolom aksi: `id`/`accessorKey` = `actions`.

### 9.5 Identitas baris

- `getRowId`: `row.id ?? row.uuid ?? index`.
- `data-row-id`: `row.original.id ?? row.id`.
- ID dipakai untuk restore anchor dan highlight, jadi harus unik dan stabil.

---

## 10. Perilaku full parity

### 10.1 Client-side (default)

- Search memakai global filter `includesString` dan mereset page ke 0.
- Filter lokal:
  - Hanya key dengan nilai tidak kosong yang dipakai.
  - Nilai baris berupa array dianggap cocok jika salah satu elemen sama.
  - Selain array, bandingkan `String(rowValue) === String(filterValue)`.
  - Pada mode server, filter lokal tidak dijalankan; data dirender apa adanya.
- Pagination memakai TanStack. Nomor halaman yang ditampilkan: halaman pertama, terakhir, dan ±1 dari halaman aktif; sisanya elipsis. Elipsis tidak berulang.
- Nomor baris otomatis:
  - Tidak ditambahkan jika `showRowNumber=false` atau sudah ada kolom `no`.
  - Kolom nomor berada di paling kiri, header "No.", rata tengah, tidak bisa disortir.
  - Nilai = `(serverSide ? pageIndex * pageSize : 0) + rowIndex + 1`.
- Column visibility:
  - "Pilih Semua" men-toggle seluruh kolom.
  - Minimal satu kolom harus terlihat; item terakhir yang terlihat tidak bisa disembunyikan.
  - Visibilitas disimpan ke state persistence.
- Page size awal dibaca dari query `per_page` jika termasuk `pageSizeOptions`; jika tidak, memakai prop `pageSize`.
- Saat ganti halaman, area scroll tabel kembali ke atas.
- Guard halaman kosong: jika `pageIndex >= pageCount`, pindah ke halaman terakhir. Guard tidak berjalan saat restore.

### 10.2 Server-side

Aktif jika `serverSide=true`.

- Query yang dikirim:
  - `q` jika search tidak kosong.
  - `f_<key>` untuk setiap filter aktif (termasuk `fixedFilters`).
  - `page` 1-based hanya jika page index > 0.
  - `per_page` selalu dikirim.
  - `sort` dan `dir` jika ada sorting aktif.
- Query tabel digabung dengan query non-tabel yang sudah ada di URL; key tabel lama diganti, key non-tabel dipertahankan.
- Reload memakai `router.visit(url, { only: reloadOnly, preserveScroll: true, preserveState: true, replace: true })`.
- Jika `reloadOnly` kosong, jangan lakukan visit.
- Search di-debounce 300ms. Perubahan page/sort/page size langsung visit dan membatalkan debounce search.
- `serverTableLoading` aktif dari event `start` sampai `finish`; digabung dengan prop `loading`.
- `pageCount` = `ceil(serverTotalRows / pageSize)`; `totalRows` = `serverTotalRows`.
- `pageSize` server disinkronkan dari state tabel melalui watcher, karena opsi tabel dibaca sinkron saat pembuatan.
- Guard `restoring` dan `pendingRestore` mencegah visit ganda saat restore dari URL/sessionStorage.
- Interaksi user saat restore ditandai `restoreInteractionPending`; setelah restore selesai, kunjungan server dijadwalkan ulang agar state user tidak tertimpa.

### 10.3 Persistence dan restore

- Key: `${tableKey}:state` di `sessionStorage`.
- Field state: `filters`, `search`, `page` (0-based), `perPage`, `sort`, `dir`, `menuGroup`, `columnVisibility`, `anchorRowId`, `scrollTop`, `scrollLeft`, `query`.
- Sinkronisasi URL:
  - Tulis `q`, `f_*`, `page` (1-based), `per_page`, `sort`, `dir`.
  - Pertahankan query non-tabel.
  - `history.replaceState` hanya jika pathname saat ini sama dengan `mountPath` (path ketika komponen di-mount).
  - Tanpa guard `mountPath`, persist saat unmount pasca-navigasi bisa menimpa URL halaman tujuan.
- Persist dijalankan:
  - Debounce 300ms setelah search/filter/fixedFilters berubah.
  - Segera setelah pagination/sorting/column visibility berubah.
  - Saat unmount, hanya jika bukan bagian dari navigasi Inertia.
- Precedence restore:
  - URL menang untuk `filters`, `search`, `page`, `perPage`, `sort`, `dir`.
  - sessionStorage menang untuk `anchorRowId`, `scrollTop`, `scrollLeft`, `columnVisibility`.
  - Jika tidak ada keduanya, pakai `initialFilters` bila ada.
- Sanitasi:
  - Filter hanya diterima jika nilainya ada di opsi filter.
  - Search hanya string tidak kosong.
  - `perPage` harus ada di `pageSizeOptions`.
  - `page` integer ≥ 0.
  - `sort` harus accessorKey valid dan bukan `actions`; `dir` hanya `asc`/`desc`.
  - `columnVisibility` hanya key kolom valid dengan nilai boolean.
  - `anchorRowId` string tidak kosong.
  - `scrollTop`/`scrollLeft` finite dan ≥ 0.
- Restore anchor:
  - Setelah render, cari baris dengan `data-row-id` yang disimpan.
  - Set `scrollTop` dan `scrollLeft` container.
  - Retry maksimal 60 kali × 50ms selama baris belum ditemukan.
- Reset saat pindah menu group:
  - Bandingkan group saat ini dengan key `__appTableLastMenuGroup`.
  - Jika berbeda, hapus semua key `*:state` di sessionStorage dan bersihkan query URL.
  - Group ditentukan dari `menuConfig` + `route()` Ziggy; jika struktur menu target berbeda, adaptasi tanpa mengubah perilaku reset.
- `captureRowAction`:
  - Klik pada `button`, `a`, atau `[role="button"]` di dalam baris menyimpan `anchorRowId` + posisi scroll.
  - Ini mendukung tombol "Kembali" dari halaman Create/Edit.
- `tableStateQuery(tableKey)` membaca `query` dari state tersimpan untuk menyusun URL index saat kembali.

### 10.4 Highlight baris

- Bentuk payload:
  - Wajib: `tableKey`, `rowIds`, `action`, `token`.
  - Opsional: `page`, `source`.
  - `action`: `created`, `updated`, atau `imported`.
- Sumber:
  - `page.flash.highlight` / `page.props.flash.highlight`.
  - Event Inertia `flash`.
  - Prop `highlightIds` + `highlightPage` sebagai fallback.
- Validasi:
  - `tableKey` harus sama.
  - `rowIds` harus array.
  - `action` harus salah satu nilai yang diizinkan.
  - `token` string tidak kosong dan belum dikonsumsi.
- Perilaku:
  - Tabel yang ter-mount tetapi tersembunyi (tab tidak aktif) membuang payload.
  - Highlight baru menggantikan highlight lama (last action wins).
  - Jika `page` dikirim, pindah ke halaman tersebut; pada mode server, jadwalkan visit.
  - Jika tidak ada ID yang tampil, retry render maksimal 60 kali × 50ms.
  - Scroll ke baris pertama yang ter-highlight, ke tengah area scroll.
  - Setelah 800ms, highlight dibersihkan.
  - Token disimpan sebagai "consumed" agar tidak diputar ulang saat re-render.
- Batas jumlah record (N=10) dan aturan kapan highlight dikirim adalah tanggung jawab backend; lihat `design-system/pola-highlight-data.md`.

### 10.5 Grouped header

- Aktif jika minimal satu kolom punya `meta.group`.
- Baris atas berisi grup dengan `colspan`; kolom non-grup memakai `rowspan=2`.
- Baris bawah hanya berisi anak grup dan sticky di 47px.
- Visibilitas kolom memengaruhi `colspan`; grup kosong tidak dirender.
- Sorting pada header grouped hanya untuk sel leaf/anak, bukan sel grup.

### 10.6 Loading, kosong, dan tanpa kolom

- `isTableLoading = props.loading || serverTableLoading`.
- Skeleton dirender di dalam `<phantom-ui>` agar shimmer konsisten.
- Empty state memakai `emptyMessage`.
- Tanpa kolom terlihat: state khusus, bukan tabel kosong.

### 10.7 Drag and drop

- `draggable` menambahkan atribut `draggable` pada baris.
- Baris yang sedang didrag diberi opacity 50.
- Baris target drop diberi tint aksen.
- Drop mengemit `row-drop` `{ from, to }`; parent bertanggung jawab menyimpan urutan.
- Jangan mengubah data props secara langsung.

### 10.8 Overflow horizontal

- Toleransi 8px untuk menentukan tombol panah kiri/kanan.
- Lebar kolom aksi dihitung dari `getBoundingClientRect` elemen `[data-action-column]`.
- Posisi vertikal panah dihitung dari tinggi `thead`.
- ResizeObserver mengamati container scroll; listener window resize ditambahkan sekali.
- Semua observer, listener, animation frame, dan timer dibersihkan saat unmount.

### 10.9 Aksesibilitas

- Tombol kontrol memakai `aria-label`.
- Menu memakai `role="menu"`; item memakai `role="menuitemradio"` atau `menuitemcheckbox` + `aria-checked`.
- Pagination memakai `aria-current="page"`.
- Tombol column/filter memakai `aria-expanded` + `aria-haspopup`.
- Skeleton mobile memakai `aria-busy` + label.
- Baris highlight memakai `aria-label`.
- Fokus terlihat pada seluruh kontrol (ring aksen).

---

## 11. Kontrak backend mode server-side

- Controller membaca `q`, `page`, `per_page`, `sort`, `dir`, dan semua `f_*`.
- Whitelist kolom sort; jangan memakai input mentah untuk `orderBy`.
- Whitelist `per_page` dengan nilai yang sama seperti `pageSizeOptions`.
- Filter yang dipakai server harus sama dengan definisi `filters` + `fixedFilters`.
- Kembalikan paginator pada prop yang tercantum di `reloadOnly`.
- `serverTotalRows` diambil dari `paginator.total`.
- Gunakan `withQueryString()` agar link pagination mempertahankan filter.
- Partial reload `only` harus menyertakan data + total + opsi filter yang berubah.

Contoh ringkas:

```php
$sortable = ['kode_prodi', 'nama_prodi', 'status'];
$sort = in_array($request->query('sort'), $sortable, true)
    ? $request->query('sort')
    : 'nama_prodi';
$dir = $request->query('dir') === 'desc' ? 'desc' : 'asc';

$allowedPerPage = [10, 25, 50, 100];
$perPage = in_array((int) $request->query('per_page'), $allowedPerPage, true)
    ? (int) $request->query('per_page')
    : 10;

$rows = ProgramStudi::query()
    ->when($request->query('q'), fn ($query, $search) => $query
        ->where('nama_prodi', 'like', "%{$search}%"))
    ->when($request->query('f_jenjang'), fn ($query, $jenjang) => $query
        ->where('jenjang_id', $jenjang))
    ->orderBy($sort, $dir)
    ->paginate($perPage)
    ->withQueryString();

return Inertia::render('Admin/MasterData/ProgramStudi/Index', [
    'programStudis' => $rows,
    'filters' => [
        'jenjang' => Jenjang::select('id', 'nama')->get(),
    ],
]);
```

---

## 12. Kontrak komponen pendukung

Komponen pendukung memakai implementasi project target. Yang wajib tersedia secara perilaku:

### 12.1 AppDropdown

| Prop | Tipe | Default | Fungsi |
|---|---|---|---|
| `placement` | String | `bottom-end` | Posisi popper |
| `disabled` | Boolean | false | Nonaktifkan |
| `distance` | Number | 8 | Jarak trigger-popper |
| `popperClass` | String | `''` | Class tambahan popper |
| `strategy` | String | `absolute` | `absolute` atau `fixed` |
| `flip` | Boolean | true | Flip saat ruang tidak cukup |
| `shift` | Boolean | true | Geser saat keluar viewport |
| `overflowPadding` | Number | 8 | Padding batas viewport |
| `autoHide` | Boolean | true | Tutup saat klik luar |
| `hideOnScroll` | Boolean | false | Tutup saat scroll |

Slot: default = trigger; `items` = isi popper, menerima `hide`.

### 12.2 AppFilterDropdown

| Prop | Tipe | Default | Fungsi |
|---|---|---|---|
| `label` | String | wajib | Label aksesibilitas |
| `options` | Array | `[]` | `{ value, label }` |
| `modelValue` | String/Number | `''` | Nilai terpilih |
| `allOption` | Boolean | true | Tambahkan opsi "Semua" bernilai kosong |
| `placeholder` | String | null | Label saat tidak ada pilihan |
| `strategy` | String | `absolute` | Diteruskan ke AppDropdown |
| `placement` | String | `bottom` | Diteruskan ke AppDropdown |
| `hideOnScroll` | Boolean | false | Diteruskan ke AppDropdown |
| `solidBackground` | Boolean | false | Gaya trigger |
| `tall` | Boolean | false | Tinggi trigger 40px |

Emit: `update:modelValue`, `change`. Klik opsi mengemit keduanya lalu menutup dropdown.

### 12.3 AppIcon

| Prop | Tipe | Default | Fungsi |
|---|---|---|---|
| `name` | String | wajib | Key icon map |
| `filled` | Boolean | false | Stroke lebih tebal |
| `size` | Number/String | `1em` | Ukuran ikon |

Key yang wajib tersedia:

`arrow_upward`, `arrow_downward`, `unfold_more`, `search`, `search_off`, `chevron_left`, `chevron_right`, `chevron_down`, `check_circle`, `filter`, `refresh`, `progress_activity`, `adjustments_horizontal`, `print`.

Catatan: pada repo sumber, `search_off` dipakai oleh `AppDataTable` tetapi belum terdaftar di `config/iconMap`. Pastikan target mendaftarkannya.

### 12.4 AppTabs

| Prop | Tipe | Default | Fungsi |
|---|---|---|---|
| `tabs` | Array | wajib | Item tab |
| `modelValue` | String | null | Tab aktif |
| `variant` | String | `chrome` | Varian tampilan; `preview-pills` dipakai untuk lebar fit-content |

Emit: `update:modelValue`.

---

## 13. Panduan update project target

1. **Inventaris target**
   - Cari komponen tabel existing (`AppDataTable`, `DataTable`, `Table`).
   - Catat props/emits/slots yang sudah dipakai halaman lain; jangan merusak call site.
   - Catat komponen pendukung yang sudah ada dan token design system target.
2. **Map warna**
   - Isi tabel §8 dengan token target.
   - Tentukan strategi dark mode target.
3. **Terapkan layout**
   - Ikuti skeleton §6.
   - Pertahankan komponen pendukung target.
   - Jangan membawa class warna project sumber.
4. **Implementasikan perilaku berurutan**
   - Tabel dasar + sorting + pagination + search.
   - Toolbar: column toggle, filter, page size.
   - Persistence + restore anchor.
   - Mode server-side + kontrak backend.
   - Highlight + grouped header.
   - Mobile + horizontal overflow + drag.
5. **Jaga kompatibilitas**
   - Jika target masih TanStack v8, API `tableFeatures`/`createTableHook` tidak kompatibel; porting hook terlebih dahulu.
   - Jika target belum memakai Inertia v2, event `flash` dan partial reload perlu adapter terpisah.
   - Jika nama token/komponen berbeda, adaptasi di layer mapping; jangan rename token global target.
6. **Rollback**
   - Simpan versi komponen lama sebelum update.
   - Update bertahap per fitur; jangan mengganti seluruh file sekaligus tanpa baseline test.

---

## 14. Checklist verifikasi

1. Search, sort, filter, ganti page size, dan pindah halaman berfungsi di client-side.
2. Reload halaman mengembalikan search/filter/sort/page/kolom tersembunyi/scroll.
3. Buka Create/Edit lalu Kembali: posisi baris dan scroll pulih; URL index membawa query yang sama.
4. Mode server-side hanya me-reload prop di `reloadOnly`; query `q/page/per_page/sort/dir/f_*` benar.
5. Total dan pagination sesuai paginator; halaman kosong tidak mungkin terjadi.
6. Simpan data: baris baru/berubah ter-highlight, halaman berpindah bila perlu, highlight hilang setelah 800ms.
7. Tabel lebar: header sticky, kolom aksi sticky, panah horizontal muncul/hilang tepat.
8. Grouped header sejajar dengan body; kolom tersembunyi tidak merusak `colspan`.
9. Mobile: slot kartu, search, sort, print, dan pagination tampil benar.
10. Semua kolom disembunyikan: state khusus muncul, bukan tabel rusak.
11. Navigasi cepat saat restore tidak menulis URL/state halaman lama.
12. `npm run lint` dan `npm run build` lulus.

---

## 15. Jebakan implementasi

- Jangan membaca `table.store.state` saat membuat opsi tabel; opsi dibaca sinkron. Sinkronkan page size server melalui watcher.
- Jangan menghapus guard `restoring`, `pendingRestore`, `navigationInProgress`, dan `restoreInteractionPending`; tanpa itu visit ganda dan state user bisa tertimpa.
- Jangan menghapus guard `mountPath`; persist saat unmount pasca-navigasi bisa menimpa URL halaman tujuan.
- Mode server tidak boleh visit jika `reloadOnly` kosong.
- Offset sticky grouped header (47px) bergantung pada tinggi header baris pertama; hitung ulang jika tipografi target berbeda.
- Class Tailwind yang dihasilkan dari computed harus berupa string literal utuh di source agar terdeteksi compiler.
- `CSS.escape` wajib dipakai saat mencari `data-row-id` di DOM.
- `phantom-ui` dan `floating-vue` harus terdaftar global; `v-tooltip` berasal dari floating-vue.
- Menu group reset bergantung pada `route()` Ziggy dan `menuConfig`; adaptasi tanpa mengubah perilaku.
- Query param tabel tidak berprefix; aman hanya untuk satu tabel per halaman. Jika target punya dua tabel dalam satu halaman, tambahkan prefix dari `tableKey` dan sesuaikan backend.
- Jangan mengubah props data secara langsung saat drag; emit `row-drop` dan biarkan parent yang menyimpan.
- Jangan menyalin class warna project sumber; gunakan tabel mapping dan token target.
- Jangan memakai `Boolean(...)` di kondisi ternary dan jangan mendeklarasikan `const props` jika tidak dipakai, sesuai aturan lint project.

---

## 16. Definition of Done

- Layout mengikuti anatomi §4–§7 dan skeleton §6.
- Seluruh peran warna di §8 sudah diisi token target; tidak ada nilai warna project sumber.
- Kontrak props/emits/slots/columns di §9 terpenuhi.
- Perilaku client-side, server-side, persistence, highlight, grouped header, mobile, dan overflow berfungsi.
- Kontrak backend §11 diterapkan dan diuji.
- Komponen pendukung memakai implementasi target dan kontrak §12 terpenuhi.
- Checklist §14 lulus.
- Tidak ada halaman/tabel existing yang dimigrasikan tanpa persetujuan.
