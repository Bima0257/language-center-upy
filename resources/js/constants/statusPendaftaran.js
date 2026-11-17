export const statusPendaftaranOptions = [
    { value: 'pending', label: 'Menunggu' },
    { value: 'approved', label: 'Disetujui' },
    { value: 'rejected', label: 'Ditolak' },
    { value: 'cancelled', label: 'Dibatalkan' },
];

export function statusPendaftaranLabel(value) {
    return statusPendaftaranOptions.find(option => option.value === value)?.label || value;
}

export function statusPendaftaranVariant(value) {
    return {
        pending: 'warning',
        approved: 'success',
        rejected: 'danger',
        cancelled: 'neutral',
    }[value] || 'neutral';
}
