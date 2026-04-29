import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

export const queueAlertMuted = ref(false);

const queueAlertReady = ref(false);
const queueAlertAnnouncementActive = ref(false);
const queueAnnouncementSnoozed = ref(false);
const queueAlertVoice = 'Ada Antrian';

let queueAlertCountSource = null;
let queueAnnouncementTimer = null;
let queueAlertAckHandler = null;
let queueAlertStorageLoaded = false;

function readMuteState() {
    if (typeof window === 'undefined') {
        return false;
    }

    try {
        return window.localStorage.getItem('antrian.queueAlertMuted') === '1';
    } catch {
        return false;
    }
}

function persistQueueAlertMute(value) {
    queueAlertMuted.value = value;

    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem('antrian.queueAlertMuted', value ? '1' : '0');
    } catch {
        // Ignore storage failures and keep the in-memory state only.
    }
}

function speakQueueAnnouncement() {
    if (typeof window === 'undefined' || !window.speechSynthesis || !window.SpeechSynthesisUtterance) {
        return;
    }

    const utterance = new window.SpeechSynthesisUtterance(queueAlertVoice);

    utterance.lang = 'id-ID';
    utterance.rate = 0.92;
    utterance.pitch = 1;
    utterance.volume = 1;

    window.speechSynthesis.cancel();
    window.speechSynthesis.speak(utterance);
}

function stopQueueAnnouncements() {
    queueAlertAnnouncementActive.value = false;

    if (queueAnnouncementTimer) {
        window.clearInterval(queueAnnouncementTimer);
        queueAnnouncementTimer = null;
    }

    if (typeof window !== 'undefined' && window.speechSynthesis) {
        window.speechSynthesis.cancel();
    }
}

function updateQueueAnnouncements() {
    if (!queueAlertReady.value || typeof window === 'undefined') {
        return;
    }

    if (!queueAlertCountSource || !window.speechSynthesis || !window.SpeechSynthesisUtterance) {
        stopQueueAnnouncements();
        return;
    }

    const currentCount = Number(queueAlertCountSource.value ?? queueAlertCountSource ?? 0);
    const shouldAnnounce = currentCount > 0 && !queueAlertMuted.value && !queueAnnouncementSnoozed.value;

    if (!shouldAnnounce) {
        stopQueueAnnouncements();
        return;
    }

    if (!queueAlertAnnouncementActive.value) {
        queueAlertAnnouncementActive.value = true;
        speakQueueAnnouncement();

        queueAnnouncementTimer = window.setInterval(() => {
            const latestCount = Number(queueAlertCountSource.value ?? queueAlertCountSource ?? 0);

            if (latestCount > 0 && !queueAlertMuted.value && !queueAnnouncementSnoozed.value) {
                speakQueueAnnouncement();
                return;
            }

            stopQueueAnnouncements();
        }, 5000);
    }
}

export function acknowledgeQueueAnnouncements() {
    queueAnnouncementSnoozed.value = true;
    stopQueueAnnouncements();
}

export function toggleQueueAlertMute() {
    persistQueueAlertMute(!queueAlertMuted.value);

    if (queueAlertMuted.value) {
        stopQueueAnnouncements();
        return;
    }

    queueAnnouncementSnoozed.value = false;
    updateQueueAnnouncements();
}

export function useQueueAlertVoice(queueAlertCount) {
    queueAlertCountSource = queueAlertCount;

    onMounted(() => {
        if (!queueAlertStorageLoaded) {
            queueAlertStorageLoaded = true;
            persistQueueAlertMute(readMuteState());
        }

        queueAlertReady.value = true;

        queueAlertAckHandler = () => {
            acknowledgeQueueAnnouncements();
        };

        window.addEventListener('queue-alert-acknowledged', queueAlertAckHandler);
        updateQueueAnnouncements();
    });

    onBeforeUnmount(() => {
        if (queueAlertAckHandler) {
            window.removeEventListener('queue-alert-acknowledged', queueAlertAckHandler);
        }

        stopQueueAnnouncements();
    });

    watch(
        queueAlertCount,
        (current) => {
            if (!queueAlertReady.value) {
                return;
            }

            if (Number(current ?? 0) <= 0) {
                queueAnnouncementSnoozed.value = false;
                stopQueueAnnouncements();
                return;
            }

            queueAnnouncementSnoozed.value = false;
            updateQueueAnnouncements();
        },
        { immediate: true },
    );

    return {
        queueAlertMuted,
        toggleQueueAlertMute,
        acknowledgeQueueAnnouncements,
    };
}
