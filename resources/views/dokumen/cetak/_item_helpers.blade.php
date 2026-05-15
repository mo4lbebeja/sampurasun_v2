@php
    $getItemNama = function ($item) {
        return $item->nama_barang
            ?? $item->nama
            ?? $item->uraian
            ?? '-';
    };

    $getItemSpesifikasi = function ($item) {
        return $item->spesifikasi
            ?? $item->keterangan
            ?? null;
    };

    $getItemSatuan = function ($item) {
        return $item->satuan
            ?? '-';
    };

    $getItemJumlah = function ($item) {
        return $item->jumlah
            ?? $item->qty
            ?? 0;
    };

    $getHargaSatuan = function ($item) {
        if (isset($item->pivot) && isset($item->pivot->harga_satuan_kontrak)) {
            return (float) $item->pivot->harga_satuan_kontrak;
        }

        return (float) (
            $item->harga_satuan_kontrak
            ?? $item->harga_satuan_estimasi
            ?? $item->harga_satuan
            ?? 0
        );
    };

    $getSubtotal = function ($item) use ($getHargaSatuan, $getItemJumlah) {
        return $getHargaSatuan($item) * (float) $getItemJumlah($item);
    };

    $formatRupiah = function ($value) {
        return 'Rp. ' . number_format((float) $value, 0, ',', '.');
    };
@endphp