// Scripts personalizados para o Gerenciador de Estoque

document.addEventListener('DOMContentLoaded', function() {
    // 1. Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // 2. Destacar status de estoque visualmente
    const itensEstoque = document.querySelectorAll('.status-estoque');
    if (itensEstoque.length > 0) {
        itensEstoque.forEach(item => {
            const valorEstoque = parseInt(item.textContent, 10);
            if (!isNaN(valorEstoque)) {
                if (valorEstoque < 5) {
                    item.classList.add('estoque-baixo');
                } else if (valorEstoque <= 20) {
                    item.classList.add('estoque-medio');
                } else {
                    item.classList.add('estoque-alto');
                }
            }
        });
    }
    
    // 3. Máscara para inputs numéricos
    const inputsNumericos = document.querySelectorAll('.apenas-numeros');
    if (inputsNumericos.length > 0) {
        inputsNumericos.forEach(input => {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    }

    // 4. Manipulador Global do Modal de Exclusão (POST + CSRF)
    const confirmDeleteModalEl = document.getElementById('confirmDeleteModal');
    if (confirmDeleteModalEl) {
        confirmDeleteModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const form = document.getElementById('confirmDeleteForm');
            const itemSpan = document.getElementById('itemToDelete');
            const idInput = document.getElementById('confirmDeleteId');
            const arquivoInput = document.getElementById('confirmDeleteArquivo');

            // Extrair dados do botão acionador
            const itemName = button.getAttribute('data-name') || button.getAttribute('data-item-name') || 'este item';
            let actionUrl = button.getAttribute('data-action') || button.getAttribute('href') || '';
            let id = button.getAttribute('data-id') || '';
            let arquivo = button.getAttribute('data-arquivo') || '';

            // Se o href possui parâmetros de query, extraí-los caso id/arquivo estejam vazios
            if (actionUrl && actionUrl.includes('?')) {
                const parts = actionUrl.split('?');
                const baseUrl = parts[0];
                const queryParams = new URLSearchParams(parts[1]);

                if (!id && queryParams.has('id')) {
                    id = queryParams.get('id');
                }
                if (!arquivo && queryParams.has('arquivo')) {
                    arquivo = queryParams.get('arquivo');
                }
                // Definir action para a rota POST sem query strings
                actionUrl = baseUrl;
            }

            if (itemSpan) itemSpan.textContent = itemName;
            if (form && actionUrl) form.action = actionUrl;
            if (idInput) idInput.value = id;
            if (arquivoInput) arquivoInput.value = arquivo;
        });
    }

    // Interceptar cliques em botões com [data-delete] ou .excluir-btn que não tenham data-bs-toggle
    document.querySelectorAll('[data-delete], .excluir-btn').forEach(button => {
        if (!button.hasAttribute('data-bs-toggle')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = document.getElementById('confirmDeleteForm');
                const itemSpan = document.getElementById('itemToDelete');
                const idInput = document.getElementById('confirmDeleteId');
                const arquivoInput = document.getElementById('confirmDeleteArquivo');

                const itemName = this.getAttribute('data-name') || this.getAttribute('data-item-name') || 'este item';
                let actionUrl = this.getAttribute('data-action') || this.getAttribute('href') || '';
                let id = this.getAttribute('data-id') || '';
                let arquivo = this.getAttribute('data-arquivo') || '';

                if (actionUrl && actionUrl.includes('?')) {
                    const parts = actionUrl.split('?');
                    const baseUrl = parts[0];
                    const queryParams = new URLSearchParams(parts[1]);
                    if (!id && queryParams.has('id')) id = queryParams.get('id');
                    if (!arquivo && queryParams.has('arquivo')) arquivo = queryParams.get('arquivo');
                    actionUrl = baseUrl;
                }

                if (itemSpan) itemSpan.textContent = itemName;
                if (form && actionUrl) form.action = actionUrl;
                if (idInput) idInput.value = id;
                if (arquivoInput) arquivoInput.value = arquivo;

                if (confirmDeleteModalEl) {
                    const modal = bootstrap.Modal.getInstance(confirmDeleteModalEl) || new bootstrap.Modal(confirmDeleteModalEl);
                    modal.show();
                }
            });
        }
    });

    // 5. Manipulador Global do Modal de Restauração de Backup (POST + CSRF)
    const confirmRestoreModalEl = document.getElementById('confirmRestoreModal');
    if (confirmRestoreModalEl) {
        confirmRestoreModalEl.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            const form = document.getElementById('confirmRestoreForm');
            const itemSpan = document.getElementById('itemToRestore');
            const arquivoInput = document.getElementById('confirmRestoreArquivo');

            const itemName = button.getAttribute('data-name') || button.getAttribute('data-item-name') || button.getAttribute('data-arquivo') || 'este backup';
            let actionUrl = button.getAttribute('data-action') || button.getAttribute('href') || '';
            let arquivo = button.getAttribute('data-arquivo') || '';

            if (actionUrl && actionUrl.includes('?')) {
                const parts = actionUrl.split('?');
                const baseUrl = parts[0];
                const queryParams = new URLSearchParams(parts[1]);
                if (!arquivo && queryParams.has('arquivo')) arquivo = queryParams.get('arquivo');
                actionUrl = baseUrl;
            }

            if (itemSpan) itemSpan.textContent = itemName;
            if (form && actionUrl) form.action = actionUrl;
            if (arquivoInput) arquivoInput.value = arquivo;
        });
    }

    // Interceptar cliques em botões .restaurar-btn que não tenham data-bs-toggle
    document.querySelectorAll('.restaurar-btn').forEach(button => {
        if (!button.hasAttribute('data-bs-toggle')) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = document.getElementById('confirmRestoreForm');
                const itemSpan = document.getElementById('itemToRestore');
                const arquivoInput = document.getElementById('confirmRestoreArquivo');

                const itemName = this.getAttribute('data-name') || this.getAttribute('data-item-name') || this.getAttribute('data-arquivo') || 'este backup';
                let actionUrl = this.getAttribute('data-action') || this.getAttribute('href') || '';
                let arquivo = this.getAttribute('data-arquivo') || '';

                if (actionUrl && actionUrl.includes('?')) {
                    const parts = actionUrl.split('?');
                    const baseUrl = parts[0];
                    const queryParams = new URLSearchParams(parts[1]);
                    if (!arquivo && queryParams.has('arquivo')) arquivo = queryParams.get('arquivo');
                    actionUrl = baseUrl;
                }

                if (itemSpan) itemSpan.textContent = itemName;
                if (form && actionUrl) form.action = actionUrl;
                if (arquivoInput) arquivoInput.value = arquivo;

                if (confirmRestoreModalEl) {
                    const modal = bootstrap.Modal.getInstance(confirmRestoreModalEl) || new bootstrap.Modal(confirmRestoreModalEl);
                    modal.show();
                }
            });
        }
    });

    // 6. Auto-fechamento suave de alertas de sucesso (5 segundos)
    document.querySelectorAll('.alert.alert-success:not(.no-auto-dismiss)').forEach(alertEl => {
        setTimeout(() => {
            if (document.body.contains(alertEl)) {
                const bsAlert = bootstrap.Alert.getInstance(alertEl) || new bootstrap.Alert(alertEl);
                if (bsAlert) {
                    bsAlert.close();
                }
            }
        }, 5000);
    });
});

// Funções globais de interface
function getCsrfToken() {
    const input = document.querySelector('input[name="csrf_token"]');
    return input ? input.value : '';
}

function markAllRead(e) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    // Efeito visual imediato
    document.querySelectorAll('.notification-item.unread, .modal-notif-item.is-unread').forEach(item => {
        item.classList.remove('unread', 'is-unread', 'bg-light', 'bg-opacity-75');
    });
    document.querySelectorAll('.btn-mark-single').forEach(btn => btn.remove());
    
    const badges = document.querySelectorAll('.notification-badge, .notification-badge-count');
    badges.forEach(badge => {
        badge.textContent = '0';
        badge.style.display = 'none';
    });
    
    const btnTodas = document.getElementById('btnMarcarTodasLidasModal');
    if (btnTodas) btnTodas.style.display = 'none';

    // Requisição assíncrona ao backend
    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());

    fetch('/pages/notificacoes/marcar_lidas.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).catch(err => console.error('Erro ao marcar notificações:', err));
}

function markSingleRead(e, notifId) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const item = document.querySelector(`.modal-notif-item[data-id="${notifId}"]`);
    if (item) {
        item.classList.remove('is-unread', 'bg-light', 'bg-opacity-75');
        const btn = item.querySelector('.btn-mark-single');
        if (btn) btn.remove();
    }

    const dropItem = document.querySelector(`.notification-item[href*="${notifId}"]`);
    if (dropItem) dropItem.classList.remove('unread');

    const formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('id', notifId);

    fetch('/pages/notificacoes/marcar_lidas.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const count = data.unread;
            document.querySelectorAll('.notification-badge-count').forEach(b => b.textContent = count);
            const topBadge = document.querySelector('.notification-badge');
            if (topBadge) {
                if (count > 0) {
                    topBadge.textContent = count > 99 ? '99+' : count;
                } else {
                    topBadge.style.display = 'none';
                }
            }
        }
    })
    .catch(err => console.error('Erro ao marcar notificação:', err));
}

let currentDetailNotifId = null;

function openNotificationDetail(data) {
    if (!data) return;
    currentDetailNotifId = data.id;

    const modalEl = document.getElementById('modalDetalheNotificacao');
    if (!modalEl) return;

    // Configurar mensagem e data
    const msgEl = document.getElementById('notifDetailMessage');
    const dateEl = document.getElementById('notifDetailDate');
    if (msgEl) msgEl.textContent = data.message || 'Sem detalhes informados.';
    if (dateEl) dateEl.innerHTML = `<i class="bi bi-clock me-1"></i>${data.date || ''}`;

    // Configurar Tipo e Cores
    const badgeEl = document.getElementById('notifDetailBadge');
    const iconEl = document.getElementById('notifDetailIcon');
    const iconWrapper = document.getElementById('notifDetailIconWrapper');
    const type = data.type || 'info';

    let badgeClass = 'bg-primary';
    let badgeLabel = 'Informativo';
    let iconClass = 'bi-info-circle-fill';
    let bgWrapperClass = 'bg-primary';

    if (type === 'danger') {
        badgeClass = 'bg-danger text-white';
        badgeLabel = 'Alerta Crítico';
        iconClass = 'bi-exclamation-triangle-fill';
        bgWrapperClass = 'bg-danger';
    } else if (type === 'warning') {
        badgeClass = 'bg-warning text-dark';
        badgeLabel = 'Aviso de Estoque';
        iconClass = 'bi-exclamation-circle-fill';
        bgWrapperClass = 'bg-warning';
    } else if (type === 'success') {
        badgeClass = 'bg-success text-white';
        badgeLabel = 'Sucesso';
        iconClass = 'bi-check-circle-fill';
        bgWrapperClass = 'bg-success';
    }

    if (badgeEl) {
        badgeEl.className = `badge px-3 py-2 fs-6 ${badgeClass}`;
        badgeEl.textContent = badgeLabel;
    }
    if (iconEl) iconEl.className = `bi ${iconClass} fs-5 text-white`;
    if (iconWrapper) iconWrapper.className = `rounded-circle p-2 me-2 d-flex align-items-center justify-content-center ${bgWrapperClass}`;

    // Configurar Status de Leitura
    const isRead = (data.read === '1' || data.read === true);
    const statusEl = document.getElementById('notifDetailStatus');
    const btnMarcar = document.getElementById('btnMarcarLidaDetail');

    if (statusEl) {
        if (isRead) {
            statusEl.className = 'badge bg-success-subtle text-success border border-success px-3 py-2';
            statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>Lida';
        } else {
            statusEl.className = 'badge bg-danger-subtle text-danger border border-danger px-3 py-2';
            statusEl.innerHTML = '<i class="bi bi-exclamation-circle me-1"></i>Não Lida';
        }
    }

    if (btnMarcar) {
        btnMarcar.style.display = isRead ? 'none' : 'inline-block';
    }

    // Configurar Botão de Ação
    const actionWrapper = document.getElementById('notifDetailActionWrapper');
    const actionBtn = document.getElementById('notifDetailActionBtn');
    if (actionBtn && actionWrapper) {
        if (data.link && data.link !== '#' && data.link !== '') {
            actionBtn.href = data.link;
            actionWrapper.style.display = 'block';
        } else {
            actionWrapper.style.display = 'none';
        }
    }

    // Fechar modal de lista anterior se estiver aberto
    const modalListaEl = document.getElementById('modalNotificacoes');
    if (modalListaEl) {
        const bsModalLista = bootstrap.Modal.getInstance(modalListaEl);
        if (bsModalLista) bsModalLista.hide();
    }

    // Fechar dropdown de notificações se estiver aberto
    const dropdownMenu = document.querySelector('.notification-dropdown .dropdown-menu.show');
    if (dropdownMenu) {
        const toggle = document.querySelector('.notification-dropdown [data-bs-toggle="dropdown"]');
        if (toggle) {
            const bsDrop = bootstrap.Dropdown.getInstance(toggle);
            if (bsDrop) bsDrop.hide();
        }
    }

    // Abrir o modal de detalhes
    const bsModalDetail = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    bsModalDetail.show();
}

function markCurrentDetailRead() {
    if (!currentDetailNotifId) return;

    markSingleRead(null, currentDetailNotifId);

    const statusEl = document.getElementById('notifDetailStatus');
    const btnMarcar = document.getElementById('btnMarcarLidaDetail');
    if (statusEl) {
        statusEl.className = 'badge bg-success-subtle text-success border border-success px-3 py-2';
        statusEl.innerHTML = '<i class="bi bi-check-circle me-1"></i>Lida';
    }
    if (btnMarcar) btnMarcar.style.display = 'none';
}

// Filtros interativos e cliques em notificações
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.notif-filter-btn');
    const notifItems = document.querySelectorAll('.modal-notif-item');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const filter = this.getAttribute('data-filter');

            notifItems.forEach(item => {
                if (filter === 'all') {
                    item.style.display = '';
                } else if (filter === 'unread') {
                    item.style.display = item.classList.contains('is-unread') ? '' : 'none';
                } else if (filter === 'danger') {
                    item.style.display = (item.getAttribute('data-type') === 'danger') ? '' : 'none';
                }
            });
        });
    });

    // Delegar clique em qualquer elemento de notificação clicável
    document.addEventListener('click', function(e) {
        // Ignorar se clicou no botão de marcar individualmente
        if (e.target.closest('.btn-mark-single')) {
            return;
        }

        const clickable = e.target.closest('.notif-clickable');
        if (clickable) {
            e.preventDefault();
            e.stopPropagation();

            const notifData = {
                id: clickable.getAttribute('data-notif-id'),
                type: clickable.getAttribute('data-notif-type') || 'info',
                message: clickable.getAttribute('data-notif-message') || '',
                date: clickable.getAttribute('data-notif-date') || '',
                link: clickable.getAttribute('data-notif-link') || '',
                read: clickable.getAttribute('data-notif-read') || '0'
            };

            openNotificationDetail(notifData);
        }
    });

    // Botão Voltar para a lista no modal de detalhes
    const btnVoltar = document.getElementById('btnVoltarListaNotif');
    if (btnVoltar) {
        btnVoltar.addEventListener('click', function() {
            const detailModalEl = document.getElementById('modalDetalheNotificacao');
            const listModalEl = document.getElementById('modalNotificacoes');

            if (detailModalEl) {
                const bsDetail = bootstrap.Modal.getInstance(detailModalEl);
                if (bsDetail) bsDetail.hide();
            }

            if (listModalEl) {
                setTimeout(() => {
                    const bsList = bootstrap.Modal.getInstance(listModalEl) || new bootstrap.Modal(listModalEl);
                    bsList.show();
                }, 300);
            }
        });
    }
});

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.getElementById('mainWrapper');
    const content = document.getElementById('content');
    if (sidebar) sidebar.classList.toggle('active');
    if (mainWrapper) mainWrapper.classList.toggle('collapsed');
    if (content) content.classList.toggle('collapsed');
}
