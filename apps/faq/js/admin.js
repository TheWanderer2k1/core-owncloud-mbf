(function() {
    // Helper to show notifications
    function showNotification(message, type) {
        if (typeof OC !== 'undefined' && OC.Notification && OC.Notification.show) {
            OC.Notification.show(message, { type: type });
        } else {
            alert(message);
        }
    }

    function initFaq() {
        // Modal elements
        var modal = document.getElementById('faq-modal');
        if (!modal) {
            return; // Exit if we are not on the FAQ settings page
        }

        var modalTitle = document.getElementById('faq-modal-title');
        var modalForm = document.getElementById('faq-modal-form');
        var fieldId = document.getElementById('faq-field-id');
        var fieldQuestion = document.getElementById('faq-field-question');
        var fieldAnswer = document.getElementById('faq-field-answer');
        var fieldStatus = document.getElementById('faq-field-status');
        var btnSave = document.getElementById('faq-modal-btn-save');

        // Open modal for Create
        var btnCreate = document.getElementById('faq-btn-create');
        if (btnCreate) {
            btnCreate.addEventListener('click', function() {
                modalTitle.textContent = t('faq', 'Add FAQ');
                fieldId.value = '';
                fieldQuestion.value = '';
                fieldAnswer.value = '';
                fieldStatus.value = '1'; // Default: Đã hiện
                modal.classList.remove('faq-hidden');
            });
        }

        // Close modal actions
        function closeModal() {
            modal.classList.add('faq-hidden');
        }
        
        var btnClose = document.getElementById('faq-modal-btn-close');
        if (btnClose) { btnClose.addEventListener('click', closeModal); }
        
        var btnCancel = document.getElementById('faq-modal-btn-cancel');
        if (btnCancel) { btnCancel.addEventListener('click', closeModal); }

        // Table Actions (Edit / Delete)
        var faqTable = document.getElementById('faq-table-element');
        if (faqTable) {
            faqTable.addEventListener('click', function(e) {
                var target = e.target;
                
                // Find closest button if click landed on SVG path
                var editBtn = target.closest('.faq-edit-btn');
                var deleteBtn = target.closest('.faq-delete-btn');
                
                if (editBtn) {
                    var row = editBtn.closest('.faq-row');
                    if (row) {
                        var id = row.getAttribute('data-id');
                        var question = row.getAttribute('data-question');
                        var answer = row.getAttribute('data-answer');
                        var status = row.getAttribute('data-status');
                        
                        modalTitle.textContent = t('faq', 'Edit FAQ');
                        fieldId.value = id;
                        fieldQuestion.value = question;
                        fieldAnswer.value = answer;
                        fieldStatus.value = status;
                        
                        modal.classList.remove('faq-hidden');
                    }
                } else if (deleteBtn) {
                    var row = deleteBtn.closest('.faq-row');
                    if (row) {
                        var id = row.getAttribute('data-id');
                        if (confirm(t('faq', 'Are you sure you want to delete this FAQ?'))) {
                            performDelete(id);
                        }
                    }
                }
            });
        }

        // AJAX Create / Update
        if (modalForm) {
            modalForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                var id = fieldId.value;
                var question = fieldQuestion.value.trim();
                var answer = fieldAnswer.value.trim();
                var status = fieldStatus.value;
                
                if (!question || !answer) {
                    showNotification(t('faq', 'Please fill in all required fields (*)'), 'warning');
                    return;
                }
                
                // Disable submit button & show loading state
                btnSave.disabled = true;
                btnSave.classList.add('loading');
                
                var isNew = (id === '');
                var url = OC.generateUrl(isNew ? '/apps/faq/create' : '/apps/faq/update');
                
                var data = new FormData();
                if (!isNew) {
                    data.append('id', id);
                }
                data.append('question', question);
                data.append('answer', answer);
                data.append('status', status);
                
                // Add request token
                var requestTokenElement = document.querySelector('input[name="requesttoken"]');
                if (requestTokenElement) {
                    data.append('requesttoken', requestTokenElement.value);
                }
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', url, true);
                
                var requestTokenElement = document.querySelector('input[name="requesttoken"]');
                if (requestTokenElement) {
                    xhr.setRequestHeader('requesttoken', requestTokenElement.value);
                }
                
                xhr.setRequestHeader('Accept', 'application/json');
                
                xhr.onload = function() {
                    btnSave.disabled = false;
                    btnSave.classList.remove('loading');
                    
                    if (xhr.status >= 200 && xhr.status < 300) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response && !response.error) {
                                showNotification(isNew ? t('faq', 'Created successfully.') : t('faq', 'Updated successfully.'), 'warning');
                                closeModal();
                                window.location.reload();
                            } else {
                                showNotification(response.error || t('faq', 'Save failed.'), 'warning');
                            }
                        } catch (err) {
                            showNotification(t('faq', 'Save failed.'), 'warning');
                        }
                    } else {
                        showNotification(t('faq', 'Save failed.'), 'warning');
                    }
                };
                
                xhr.onerror = function() {
                    btnSave.disabled = false;
                    btnSave.classList.remove('loading');
                    showNotification(t('faq', 'Connection error.'), 'warning');
                };
                
                xhr.send(data);
            });
        }

        // AJAX Delete
        function performDelete(id) {
            var url = OC.generateUrl('/apps/faq/delete');
            var data = new FormData();
            data.append('id', id);
            
            var requestTokenElement = document.querySelector('input[name="requesttoken"]');
            if (requestTokenElement) {
                data.append('requesttoken', requestTokenElement.value);
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url, true);
            
            var requestTokenElement = document.querySelector('input[name="requesttoken"]');
            if (requestTokenElement) {
                xhr.setRequestHeader('requesttoken', requestTokenElement.value);
            }
            
            xhr.setRequestHeader('Accept', 'application/json');
            
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.status === 'success') {
                            showNotification(t('faq', 'Deleted successfully.'), 'warning');
                            window.location.reload();
                        } else {
                            showNotification(response.error || t('faq', 'Delete failed.'), 'warning');
                        }
                    } catch (err) {
                        showNotification(t('faq', 'Delete failed.'), 'warning');
                    }
                } else {
                    showNotification(t('faq', 'Delete failed.'), 'warning');
                }
            };
            
            xhr.onerror = function() {
                showNotification(t('faq', 'Connection error.'), 'warning');
            };
            
            xhr.send(data);
        }

        // Client-side Search and Status Filtering
        var searchInput = document.getElementById('faq-search');
        var statusFilter = document.getElementById('faq-status-filter');
        
        function applyFilters() {
            var searchText = searchInput ? searchInput.value.toLowerCase().trim() : '';
            var selectedStatus = statusFilter ? statusFilter.value : 'all';
            
            var rows = document.querySelectorAll('.faq-row');
            var visibleCount = 0;
            
            rows.forEach(function(row) {
                var questionText = row.getAttribute('data-question').toLowerCase();
                var rowStatus = row.getAttribute('data-status');
                
                var matchesText = (searchText === '' || questionText.indexOf(searchText) !== -1);
                var matchesStatus = (selectedStatus === 'all' || rowStatus === selectedStatus);
                
                if (matchesText && matchesStatus) {
                    row.classList.remove('faq-hidden');
                    visibleCount++;
                } else {
                    row.classList.add('faq-hidden');
                }
            });

            // Re-index visible rows
            var visibleIndex = 1;
            rows.forEach(function(row) {
                if (!row.classList.contains('faq-hidden')) {
                    var indexCell = row.querySelector('.faq-row-index');
                    if (indexCell) {
                        indexCell.textContent = visibleIndex++;
                    }
                }
            });

            // Show/hide no data row if all matches are filtered out
            var tableBody = document.querySelector('#faq-table-element tbody');
            var noDataRow = document.querySelector('.faq-no-data');
            
            if (visibleCount === 0) {
                if (!noDataRow && tableBody) {
                    noDataRow = document.createElement('tr');
                    noDataRow.className = 'faq-no-data';
                    var cell = document.createElement('td');
                    cell.colSpan = 6;
                    cell.textContent = t('faq', 'No matching questions found.');
                    noDataRow.appendChild(cell);
                    tableBody.appendChild(noDataRow);
                } else if (noDataRow) {
                    noDataRow.classList.remove('faq-hidden');
                    noDataRow.querySelector('td').textContent = t('faq', 'No matching questions found.');
                }
            } else {
                if (noDataRow) {
                    noDataRow.classList.add('faq-hidden');
                }
            }
        }

        if (searchInput) { searchInput.addEventListener('input', applyFilters); }
        if (statusFilter) { statusFilter.addEventListener('change', applyFilters); }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaq);
    } else {
        initFaq();
    }
})();
