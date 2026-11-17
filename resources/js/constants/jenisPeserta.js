export const jenisPesertaOptions = [
    { value: 'mahasiswa', label: 'Mahasiswa' },
    { value: 'dosen', label: 'Dosen' },
    { value: 'umum', label: 'Umum' },
];

export const jenisPesertaLabel = {
    mahasiswa: 'Mahasiswa',
    dosen: 'Dosen',
    umum: 'Umum',
};

export const jenisPesertaIdentitasLabel = {
    mahasiswa: 'NIM',
    dosen: 'NIDN/NIP',
    umum: 'NIK',
};

export const jenisPesertaVariant = {
    mahasiswa: 'secondary',
    dosen: 'pastel-blue',
    umum: 'pastel-purple',
};

export function labelJenisPeserta(value) {
    return jenisPesertaLabel[value] || value || '-';
}

export function labelIdentitasPeserta(value) {
    return jenisPesertaIdentitasLabel[value] || 'Identitas';
}
