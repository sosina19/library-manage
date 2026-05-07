const API_URL = 'api.php';

async function apiCall(action, data = {}) {
    const formData = new FormData();
    formData.append('action', action);
    for (const key in data) {
        formData.append(key, data[key]);
    }
    
    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (e) {
        console.error('API Error:', e);
        return { success: false, message: 'Network error occurred' };
    }
}

function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

async function logout() {
    await apiCall('logout');
    window.location.href = 'index.php';
}

function showNotification(msg, isError = false) {
    showAlert(msg, isError ? 'Error' : 'Notice');
}

function ensureUiMessageModal() {
    let modal = document.getElementById('uiMessageModal');
    if (modal) return modal;

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div id="uiMessageModal" class="modal">
            <div class="modal-content" style="max-width:420px; text-align:center;">
                <button class="modal-close" id="uiMessageCloseBtn" aria-label="Close">&times;</button>
                <h3 id="uiMessageTitle" style="margin-bottom:10px;color:black;">Notice</h3>
                <p id="uiMessageBody" style="margin-bottom:20px; color:black;"></p>
                <div id="uiMessageActions" style="display:flex; justify-content:center; gap:10px;"></div>
            </div>
        </div>
    `;
    document.body.appendChild(wrapper.firstElementChild);
    return document.getElementById('uiMessageModal');
}

function showAlert(message, title = 'Notice') {
    return new Promise(resolve => {
        ensureUiMessageModal();
        document.getElementById('uiMessageTitle').textContent = title;
        document.getElementById('uiMessageBody').textContent = message;
        const actions = document.getElementById('uiMessageActions');
        actions.innerHTML = `<button class="btn btn-primary" id="uiMessageOkBtn">OK</button>`;
        const close = () => {
            closeModal('uiMessageModal');
            resolve(true);
        };
        document.getElementById('uiMessageCloseBtn').onclick = close;
        document.getElementById('uiMessageOkBtn').onclick = close;
        openModal('uiMessageModal');
    });
}

function showConfirm(message, title = 'Confirm Action') {
    return new Promise(resolve => {
        ensureUiMessageModal();
        document.getElementById('uiMessageTitle').textContent = title;
        document.getElementById('uiMessageBody').textContent = message;
        const actions = document.getElementById('uiMessageActions');
        actions.innerHTML = `
            <button class="btn btn-danger" id="uiMessageConfirmBtn">Confirm</button>
            <button class="btn btn-secondary" id="uiMessageCancelBtn">Cancel</button>
        `;
        document.getElementById('uiMessageConfirmBtn').onclick = () => {
            closeModal('uiMessageModal');
            resolve(true);
        };
        const cancel = () => {
            closeModal('uiMessageModal');
            resolve(false);
        };
        document.getElementById('uiMessageCancelBtn').onclick = cancel;
        document.getElementById('uiMessageCloseBtn').onclick = cancel;
        openModal('uiMessageModal');
    });
}

window.alert = function(message) {
    showAlert(String(message ?? ''), 'Notice');
};

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.sidebar nav a').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabId).classList.remove('hidden');
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
}
