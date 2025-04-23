// Scripts personalizados para o Gerenciador de Estoque

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips do Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirmação de exclusão com modal
    document.querySelectorAll('[data-delete]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const itemName = this.getAttribute('data-item-name') || 'este item';
            const deleteUrl = this.getAttribute('href');
            
            // Atualizar o modal com as informações do item
            document.getElementById('itemToDelete').textContent = itemName;
            document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);
            
            // Abrir o modal
            const modal = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
            modal.show();
        });
    });
    
    // Destacar status de estoque
    const itensEstoque = document.querySelectorAll('.status-estoque');
    if (itensEstoque) {
        itensEstoque.forEach(item => {
            const valorEstoque = parseInt(item.textContent);
            if (valorEstoque < 5) {
                item.classList.add('estoque-baixo');
            } else if (valorEstoque >= 5 && valorEstoque <= 20) {
                item.classList.add('estoque-medio');
            } else {
                item.classList.add('estoque-alto');
            }
        });
    }
    
    // Máscara para inputs numéricos
    const inputsNumericos = document.querySelectorAll('.apenas-numeros');
    if (inputsNumericos) {
        inputsNumericos.forEach(input => {
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    }
});