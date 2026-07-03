document.addEventListener('DOMContentLoaded', function () {
    const chatBox = document.getElementById('chatBox');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    const chatForm = document.getElementById('chatForm');
    if (chatForm) {
        chatForm.addEventListener('submit', function (e) {
            const btn = document.getElementById('btnInvia');
            const input = document.getElementById('messageInput');
            const fileInput = document.getElementById('fileInput');

            if (input.value.trim() === '' && (!fileInput || fileInput.files.length === 0)) {
                e.preventDefault();
                return;
            }

            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
            }
        });
    }
});

function renameChat(id, currentTitle) {
    const newTitle = prompt('Inserisci il nuovo nome per la chat:', currentTitle);
    if (newTitle !== null && newTitle.trim() !== '') {
        const renameInput = document.getElementById('input-rename-' + id);
        const renameForm = document.getElementById('form-rename-' + id);
        if (renameInput && renameForm) {
            renameInput.value = newTitle.trim();
            renameForm.submit();
        }
    }
}

function deleteChat(id) {
    if (confirm('Sei sicuro di voler eliminare questa conversazione?')) {
        const deleteForm = document.getElementById('form-delete-' + id);
        if (deleteForm) {
            deleteForm.submit();
        }
    }
}
