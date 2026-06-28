<?php
declare(strict_types=1);

?><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    var today = new Date().toISOString().slice(0, 10);

    document.querySelectorAll('[data-min-today="true"]').forEach(function (field) {
        if (!field.getAttribute('min')) {
            field.setAttribute('min', today);
        }
    });

    document.querySelectorAll('[data-max-today="true"]').forEach(function (field) {
        if (!field.getAttribute('max')) {
            field.setAttribute('max', today);
        }
    });

    document.querySelectorAll('[data-after]').forEach(function (field) {
        var source = document.querySelector('[name="' + field.getAttribute('data-after') + '"]');
        if (!source) {
            return;
        }

        var syncMin = function () {
            if (source.value) {
                field.setAttribute('min', source.value);
                if (field.value && field.value < source.value) {
                    field.value = source.value;
                }
            }
        };

        source.addEventListener('change', syncMin);
        source.addEventListener('input', syncMin);
        syncMin();
    });

    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}());
</script>
</body>
</html>