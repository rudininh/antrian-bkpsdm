import { reactive } from 'vue';

export const serverIssueState = reactive({
    active: false,
    message: '',
});

export function reportServerIssue(message = 'Server sedang bermasalah. Data terakhir tetap ditampilkan.') {
    serverIssueState.active = true;
    serverIssueState.message = message;
}

export function clearServerIssue() {
    serverIssueState.active = false;
    serverIssueState.message = '';
}
