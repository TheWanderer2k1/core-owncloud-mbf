(function(){
    function initNewsEditor() {
        var form = document.getElementById('news-form');
        if (!form) {
            return;
        }

        var editors = [
            {editorId: 'intro-editor', fieldId: 'intro-value'},
            {editorId: 'terms-editor', fieldId: 'terms-value'},
            {editorId: 'policy-editor', fieldId: 'policy-value'}
        ];

        function syncEditors() {
            editors.forEach(function(item) {
                var editor = document.getElementById(item.editorId);
                var field = document.getElementById(item.fieldId);
                if (editor && field) {
                    field.value = editor.innerHTML;
                }
            });
        }

        function showNotification(message, type) {
            if (typeof OC !== 'undefined' && OC.Notification && OC.Notification.showTemporary) {
                OC.Notification.showTemporary(message, {type: type});
            } else {
                alert(message);
            }
        }

        document.querySelectorAll('.wysiwyg-toolbar button[data-cmd]').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                var cmd = event.target.getAttribute('data-cmd');
                var toolbar = event.target.closest('.wysiwyg-toolbar');
                var editorId = toolbar ? toolbar.getAttribute('data-editor') : null;
                var editor = editorId ? document.getElementById(editorId) : null;
                if (!editor) {
                    return;
                }
                if (cmd === 'createLink') {
                    var url = window.prompt('Nhập URL');
                    if (url) {
                        document.execCommand(cmd, false, url);
                    }
                } else {
                    document.execCommand(cmd, false, null);
                }
                syncEditors();
            });
        });

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            syncEditors();
            var action = form.getAttribute('action');
            var requestToken = form.querySelector('input[name="requesttoken"]');
            var data = new FormData(form);
            if (requestToken) {
                data.set('requesttoken', requestToken.value);
            }

            var xhr = new XMLHttpRequest();
            xhr.open('POST', action, true);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.status === 'success') {
                            showNotification('Đã lưu thành công.', 'success');
                        } else {
                            showNotification(response.message || 'Lưu không thành công.', 'warning');
                        }
                    } catch (e) {
                        showNotification('Lưu không thành công.', 'warning');
                    }
                } else {
                    showNotification('Lưu không thành công.', 'warning');
                }
            };
            xhr.onerror = function() {
                showNotification('Lưu không thành công.', 'warning');
            };
            xhr.send(data);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initNewsEditor);
    } else {
        initNewsEditor();
    }
})();
