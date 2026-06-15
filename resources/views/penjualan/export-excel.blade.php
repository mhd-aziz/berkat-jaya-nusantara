{{--
    File: resources/views/penjualan/export-excel.blade.php

    Catatan:
    File ini sudah tidak digunakan lagi untuk export invoice penjualan.

    Export Excel invoice penjualan sekarang dibuat sebagai file Excel asli (.xlsx)
    langsung dari method:

    App\Http\Controllers\PenjualanController::exportExcel()

    menggunakan library:
    phpoffice/phpspreadsheet

    Alasan perubahan:
    - Export sebelumnya berbasis HTML yang dipaksa menjadi .xls
    - Logo tidak tertanam permanen
    - Excel menampilkan error "The linked image cannot be displayed"
    - Sekarang file yang dihasilkan adalah .xlsx asli dan logo tertanam langsung
--}}

@php
abort(410, 'Template export Excel HTML sudah tidak digunakan. Export invoice penjualan sekarang dibuat langsung melalui PhpSpreadsheet di PenjualanController::exportExcel().');
@endphp