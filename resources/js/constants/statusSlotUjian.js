export function slotStatus(slotUjian) {
    const now = new Date();
    const slotDate = new Date(slotUjian.tanggal);
    const [hours, minutes] = (slotUjian.waktu_mulai || '00:00').split(':').map(Number);
    slotDate.setHours(hours, minutes, 0, 0);

    const endSlot = new Date(slotDate);
    const [endHours, endMinutes] = (slotUjian.waktu_selesai || '23:59').split(':').map(Number);
    endSlot.setHours(endHours, endMinutes, 0, 0);

    if (now >= slotDate && now <= endSlot) return 'active';
    if (now > endSlot) return 'past';

    return 'upcoming';
}

export function slotStatusLabel(slotUjian) {
    return {
        active: 'Berlangsung',
        past: 'Selesai',
        upcoming: 'Akan Datang',
    }[slotStatus(slotUjian)] || '-';
}

export function slotStatusVariant(slotUjian) {
    return {
        active: 'success',
        past: 'neutral',
        upcoming: 'pastel-blue',
    }[slotStatus(slotUjian)] || 'neutral';
}

export function isPendaftaranSlotAktif(status) {
    return ['pending', 'approved'].includes(status);
}

export function bisaDaftarSlot(slotUjian) {
    return slotUjian.pendaftaran_terbuka === true
        && slotStatus(slotUjian) === 'upcoming'
        && !isPendaftaranSlotAktif(slotUjian.pendaftaran_status);
}

export function bisaMulaiSlot(slotUjian) {
    return slotStatus(slotUjian) === 'active'
        && slotUjian.pendaftaran_status === 'approved';
}

export function slotRelevanUntukPeserta(slotUjian) {
    return slotStatus(slotUjian) !== 'past' || isPendaftaranSlotAktif(slotUjian.pendaftaran_status);
}
