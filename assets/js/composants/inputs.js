function formatDate(dateInput) {
    dateInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // supprime tout sauf chiffres
        if (value.length > 8) value = value.substring(0, 8); // max 8 chiffres
        let formatted = '';
        if (value.length > 0) {
            formatted += value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += '/' + value.substring(2, 4);
        }
        if (value.length > 4) {
            formatted += '/' + value.substring(4, 8);
        }
        // Appliquer le format
        e.target.value = formatted;
    });
    dateInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/\D/g, '').substring(0, 8);
        let formatted = '';
        if (digits.length >= 2) formatted += digits.substring(0, 2);
        if (digits.length >= 4) formatted += '/' + digits.substring(2, 4);
        if (digits.length > 4) formatted += '/' + digits.substring(4, 8);
        e.target.value = formatted;
    });
}

function formatMonthDay(dateInput) {
    dateInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // supprime tout sauf chiffres
        if (value.length > 4) value = value.substring(0, 4); // max 8 chiffres
        let formatted = '';
        if (value.length > 0) {
            formatted += value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += '/' + value.substring(2, 4);
        }
        // Appliquer le format
        e.target.value = formatted;
    });
    dateInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/\D/g, '').substring(0, 8);
        let formatted = '';
        if (digits.length >= 2) formatted += digits.substring(0, 2);
        if (digits.length >= 4) formatted += '/' + digits.substring(2, 4);
        e.target.value = formatted;
    });
}

function formatTel(telInput) {
    telInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // supprime tout sauf chiffres
        if (value.length > 8) value = value.substring(0, 10); // max 8 chiffres
        let formatted = '';
        if (value.length > 0) {
            formatted += value.substring(0, 2);
        }
        if (value.length > 2) {
            formatted += ' ' + value.substring(2, 4);
        }
        if (value.length > 4) {
            formatted += ' ' + value.substring(4, 6);
        }
        if (value.length > 6) {
            formatted += ' ' + value.substring(6, 8);
        }
        if (value.length > 8) {
            formatted += ' ' + value.substring(8, 10);
        }
        // Appliquer le format
        e.target.value = formatted;
    });
    telInput.addEventListener('paste', function (e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/\D/g, '').substring(0, 10);
        let formatted = '';
        if (digits.length >= 2) formatted += digits.substring(0, 2);
        if (digits.length >= 4) formatted += ' ' + digits.substring(2, 4);
        if (digits.length >= 6) formatted += ' ' + digits.substring(4, 6);
        if (digits.length >= 8) formatted += ' ' + digits.substring(6, 8);
        if (digits.length >= 10) formatted += ' ' + digits.substring(8, 10);
        e.target.value = formatted;
    });
}

window.formatDate = formatDate;
window.formatTel = formatTel;
window.formatMonthDay = formatMonthDay;