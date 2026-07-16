document.addEventListener("DOMContentLoaded", function () {
    // 1. Currency Formatting Helper for Input Fields
    const currencyInputs = document.querySelectorAll('.currency-input');
    
    currencyInputs.forEach(input => {
        // Format on load if value exists
        if (input.value) {
            input.value = formatRupiah(input.value);
        }
        
        input.addEventListener('input', function (e) {
            this.value = formatRupiah(this.value);
        });
    });

    /**
     * Helper to format numbers to Indonesian Rupiah standard (thousands separator)
     * @param {string} angka 
     * @returns {string}
     */
    function formatRupiah(angka) {
        let number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);

        if (ribuan) {
            let separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }

        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    const formsWithCurrency = document.querySelectorAll('form');
    formsWithCurrency.forEach(form => {
        form.addEventListener('submit', function () {
            const inputsToClean = form.querySelectorAll('.currency-input');
            inputsToClean.forEach(input => {
                // Remove all non-digits (dots) to send a clean decimal/integer value to MySQL
                let cleanVal = input.value.replace(/\./g, '');
                input.value = cleanVal;
            });
        });
    });

    const editButtons = document.querySelectorAll('.btn-edit-transaction');
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const type = this.getAttribute('data-type');
            const category = this.getAttribute('data-category');
            const amount = this.getAttribute('data-amount');
            const date = this.getAttribute('data-date');
            const description = this.getAttribute('data-description');

            document.getElementById('edit-id').value = id;
            document.getElementById('edit-type').value = type;
            document.getElementById('edit-category').value = category;
            
            const amountField = document.getElementById('edit-amount');
            amountField.value = amount;
            amountField.value = formatRupiah(amountField.value);
            
            document.getElementById('edit-date').value = date;
            document.getElementById('edit-description').value = description;
        });
    });

    const deleteButtons = document.querySelectorAll('.btn-delete-transaction');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            document.getElementById('delete-id').value = id;
        });
    });
});
