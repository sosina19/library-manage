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
    alert(msg); // Fallback to alert for simplicity, could be enhanced to toast
}

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.sidebar nav a').forEach(el => el.classList.remove('active'));
    
    document.getElementById(tabId).classList.remove('hidden');
    document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
}
