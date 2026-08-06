(function(){
    var editors = {};

    // Helper to generate the actual player markup based on media URL
    function createRealVideoMarkup(url) {
        // YouTube
        var ytMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([\w-]+)/);
        if (ytMatch) {
            var iframe = document.createElement('iframe');
            iframe.setAttribute('src', 'https://www.youtube.com/embed/' + ytMatch[1]);
            iframe.setAttribute('width', '100%');
            iframe.setAttribute('height', '315');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', 'true');
            return iframe;
        }

        // Vimeo
        var vimeoMatch = url.match(/(?:vimeo\.com\/|player\.vimeo\.com\/video\/|vimeo\.com\/embed\/)(\d+)/);
        if (vimeoMatch) {
            var iframe = document.createElement('iframe');
            iframe.setAttribute('src', 'https://player.vimeo.com/video/' + vimeoMatch[1]);
            iframe.setAttribute('width', '100%');
            iframe.setAttribute('height', '315');
            iframe.setAttribute('frameborder', '0');
            iframe.setAttribute('allowfullscreen', 'true');
            return iframe;
        }

        // Direct Video files (mp4, webm, ogg)
        if (url.match(/\.(mp4|webm|ogg)(?:\?|$)/i)) {
            var video = document.createElement('video');
            video.setAttribute('src', url);
            video.setAttribute('controls', 'true');
            video.setAttribute('width', '100%');
            video.style.maxWidth = '100%';
            return video;
        }

        // Catch-all general iframe fallback for other online video platforms
        var iframe = document.createElement('iframe');
        iframe.setAttribute('src', url);
        iframe.setAttribute('width', '100%');
        iframe.setAttribute('height', '315');
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allowfullscreen', 'true');
        return iframe;
    }

    // Custom AJAX Upload Adapter for CKEditor 5
    function CustomUploadAdapter(loader) {
        this.loader = loader;
    }
    CustomUploadAdapter.prototype.upload = function() {
        var loader = this.loader;
        return loader.file.then(function(file) {
            return new Promise(function(resolve, reject) {
                var xhr = new XMLHttpRequest();
                var uploadUrl = OC.generateUrl('/apps/news/upload');
                
                xhr.open('POST', uploadUrl, true);
                
                var requestTokenElement = document.querySelector('input[name="requesttoken"]');
                if (requestTokenElement) {
                    xhr.setRequestHeader('requesttoken', requestTokenElement.value);
                }
                
                xhr.setRequestHeader('Accept', 'application/json');
                
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response && response.url) {
                                resolve({ default: response.url });
                            } else {
                                reject(response.error && response.error.message ? response.error.message : 'Tải ảnh lên thất bại');
                            }
                        } catch (e) {
                            reject('Tải ảnh lên thất bại');
                        }
                    } else {
                        reject('Tải ảnh lên thất bại với mã trạng thái: ' + xhr.status);
                    }
                };
                
                xhr.onerror = function() {
                    reject('Lỗi kết nối khi tải ảnh lên');
                };
                
                var data = new FormData();
                data.append('upload', file);
                xhr.send(data);
            });
        });
    };
    CustomUploadAdapter.prototype.abort = function() {
        // Nothing to abort
    };

    function CustomUploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
            return new CustomUploadAdapter(loader);
        };
    }

    function initNewsEditor() {
        var form = document.getElementById('news-form');
        if (!form) {
            return;
        }

        var saveButton = document.getElementById('news-save');
        var saveButtonText = saveButton ? saveButton.querySelector('span') : null;
        var originalText = saveButtonText ? saveButtonText.textContent : 'Lưu';

        function showNotification(message, type) {
            if (typeof OC !== 'undefined' && OC.Notification && OC.Notification.showTemporary) {
                OC.Notification.showTemporary(message, {type: type});
            } else {
                alert(message);
            }
        }

        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Sync CKEditor data to textareas and convert static card placeholders back to actual player iframe/video elements
            Object.keys(editors).forEach(function(id) {
                var textarea = document.getElementById(id);
                if (textarea && editors[id]) {
                    var html = editors[id].getData();
                    
                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    var placeholders = tempDiv.querySelectorAll('.ck-media-preview-custom');
                    placeholders.forEach(function(placeholder) {
                        var url = placeholder.getAttribute('data-url');
                        if (url) {
                            var realPlayer = createRealVideoMarkup(url);
                            placeholder.parentNode.replaceChild(realPlayer, placeholder);
                        }
                    });
                    
                    textarea.value = tempDiv.innerHTML;
                }
            });

            // Set button loading state
            if (saveButton) {
                saveButton.disabled = true;
                saveButton.classList.add('loading');
                if (saveButtonText) {
                    saveButtonText.textContent = 'Đang lưu...';
                }
            }

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
                // Restore button state
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.classList.remove('loading');
                    if (saveButtonText) {
                        saveButtonText.textContent = originalText;
                    }
                }

                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.status === 'success') {
                            showNotification('Đã lưu thành công.', 'warning');
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
                // Restore button state
                if (saveButton) {
                    saveButton.disabled = false;
                    saveButton.classList.remove('loading');
                    if (saveButtonText) {
                        saveButtonText.textContent = originalText;
                    }
                }
                showNotification('Lưu không thành công.', 'warning');
            };
            xhr.send(data);
        });
    }

    function initCKEditor() {
        if (typeof ClassicEditor === 'undefined') {
            return false;
        }

        var editorIds = ['intro-editor', 'terms-editor', 'policy-editor'];
        editorIds.forEach(function(id) {
            var element = document.getElementById(id);
            if (element) {
                ClassicEditor.create(element, {
                    extraPlugins: [CustomUploadAdapterPlugin],
                    mediaEmbed: {
                        previewsInData: true,
                        providers: [
                            // Catch-all general media provider that matches any http/https link and displays a static card
                            {
                                name: 'general-media',
                                url: /^(?:https?:)?\/\/\S+/,
                                html: function(match) {
                                    var url = match[0];
                                    return '<div class="ck-media-preview-custom" data-url="' + url + '" style="position:relative; padding:15px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px; text-align:center; font-family:sans-serif; pointer-events:none; user-select:none;">' +
                                           '<div style="font-size:24px; margin-bottom:5px;">🎥 Video / Media</div>' +
                                           '<span style="text-decoration:none; color:#0082c9; font-weight:bold; font-size:12px; word-break:break-all;">' +
                                           url +
                                           '</span>' +
                                           '</div>';
                                }
                            }
                        ]
                    }
                })
                .then(function(editor) {
                    editors[id] = editor;
                })
                .catch(function(error) {
                    console.error('CKEditor initialization error for ' + id + ':', error);
                });
            }
        });
        return true;
    }

    function waitForCKEditor() {
        if (initCKEditor()) {
            return;
        }
        var attempts = 0;
        var interval = setInterval(function() {
            attempts += 1;
            if (typeof ClassicEditor !== 'undefined') {
                clearInterval(interval);
                initCKEditor();
            } else if (attempts >= 50) {
                clearInterval(interval);
            }
        }, 200);
    }

    function init() {
        initNewsEditor();
        waitForCKEditor();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
