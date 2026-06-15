{{--
    File: resources/views/pembelian/export-excel.blade.php

    Catatan:
    File ini sudah tidak digunakan lagi untuk export invoice pembelian.

    Export Excel invoice pembelian sekarang dibuat sebagai file Excel asli (.xlsx)
    langsung dari method:

    App\Http\Controllers\PembelianController::exportExcel()

    menggunakan library:
    phpoffice/phpspreadsheet

    Alasan perubahan:
    - Export sebelumnya berbasis HTML yang dipaksa menjadi .xls
    - Logo tidak tertanam permanen
    - Excel dapat menampilkan error linked image
    - Sekarang file yang dihasilkan adalah .xlsx asli dan logo tertanam langsung
--}}

@php
abort(410, 'Template export Excel HTML sudah tidak digunakan. Export invoice pembelian sekarang dibuat langsung melalui PhpSpreadsheet di PembelianController::exportExcel().');
@endphp