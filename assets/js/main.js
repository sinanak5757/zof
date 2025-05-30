// Ana JavaScript dosyası
document.addEventListener('DOMContentLoaded', function() {
    // Bootstrap tooltip'leri etkinleştir
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Mobil menü toggle
    var sidebarToggle = document.querySelector('.navbar-toggler');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('show');
        });
    }

    // Tablo sıralama
    var sortableTables = document.querySelectorAll('.table-sortable');
    sortableTables.forEach(function(table) {
        var headers = table.querySelectorAll('th.sortable');
        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                var headerIndex = Array.from(header.parentNode.children).indexOf(header);
                var currentDirection = header.getAttribute('data-sort-direction') || 'asc';
                var newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
                
                // Diğer başlıklardan yön bilgisini temizle
                headers.forEach(function(h) {
                    h.setAttribute('data-sort-direction', '');
                    h.querySelector('i.bi')?.remove();
                });
                
                // Bu başlığa yön bilgisi ekle
                header.setAttribute('data-sort-direction', newDirection);
                
                // Sıralama göstergesini ekle
                var icon = document.createElement('i');
                icon.className = 'bi bi-' + (newDirection === 'asc' ? 'arrow-up' : 'arrow-down');
                icon.style.marginLeft = '5px';
                header.appendChild(icon);
                
                // Tabloyu sırala
                sortTable(table, headerIndex, newDirection);
            });
        });
    });

    // Tablo sıralama fonksiyonu
    function sortTable(table, columnIndex, direction) {
        var rows = Array.from(table.querySelectorAll('tbody tr'));
        var tbody = table.querySelector('tbody');
        
        rows.sort(function(a, b) {
            var aValue = a.cells[columnIndex].innerText;
            var bValue = b.cells[columnIndex].innerText;
            
            // Sayı ise sayısal sıralama yap
            if (!isNaN(Number(aValue)) && !isNaN(Number(bValue))) {
                return direction === 'asc' ? 
                    Number(aValue) - Number(bValue) : 
                    Number(bValue) - Number(aValue);
            } else {
                // Metin ise alfabetik sıralama yap
                return direction === 'asc' ? 
                    aValue.localeCompare(bValue) : 
                    bValue.localeCompare(aValue);
            }
        });
        
        // Sıralanmış satırları tabloya ekle
        rows.forEach(function(row) {
            tbody.appendChild(row);
        });
    }

    // Form doğrulama
    var forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Onay kutuları
    var confirmBtns = document.querySelectorAll('.confirm-action');
    confirmBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('Bu işlemi gerçekleştirmek istediğinizden emin misiniz?')) {
                e.preventDefault();
            }
        });
    });
});