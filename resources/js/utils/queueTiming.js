export function formatWaitingDuration(queuedAtIso, now = Date.now()) {
    if (!queuedAtIso) {
        return 'baru saja';
    }

    const queuedAt = new Date(queuedAtIso).getTime();

    if (Number.isNaN(queuedAt)) {
        return 'baru saja';
    }

    const elapsedSeconds = Math.max(0, Math.floor((now - queuedAt) / 1000));

    if (elapsedSeconds < 60) {
        if (elapsedSeconds === 0) {
            return 'baru saja';
        }

        return `${elapsedSeconds} detik`;
    }

    const hours = Math.floor(elapsedSeconds / 3600);
    const minutes = Math.floor((elapsedSeconds % 3600) / 60);
    const seconds = elapsedSeconds % 60;

    if (hours > 0) {
        return `${hours} jam ${minutes} menit`;
    }

    return `${minutes} menit ${String(seconds).padStart(2, '0')} detik`;
}
