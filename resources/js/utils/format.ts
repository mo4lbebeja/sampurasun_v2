export const formatMetode = (val: string): string => {
    const map: Record<string, string> = {
        pengadaan_langsung:  'Pengadaan Langsung',
        penunjukan_langsung: 'Penunjukan Langsung',
        tender:              'Tender',
        e_purchasing:        'E-Purchasing',
        belanja_langsung:    'Belanja Langsung',
    };
    return map[val] ?? val;
};

export const formatRupiah = (val: string | number): string =>
    new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(Number(val) || 0);