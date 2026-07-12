        </div><!-- /.page-content -->
    </div><!-- /.main-content -->
</div><!-- /.app-wrapper -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.27/dist/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function () {

    // ── DataTables (Bahasa Indonesia - inline) ──────────────────
    var dtLanguage = {
        "decimal": ",",
        "thousands": ".",
        "emptyTable": "Tidak ada data tersedia",
        "info": "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
        "infoEmpty": "Menampilkan 0 - 0 dari 0 entri",
        "infoFiltered": "(difilter dari _MAX_ total entri)",
        "lengthMenu": "Tampilkan _MENU_ entri",
        "loadingRecords": "Memuat...",
        "processing": "Memproses...",
        "search": "Cari:",
        "zeroRecords": "Tidak ditemukan data yang sesuai",
        "paginate": {
            "first": "«",
            "last": "»",
            "next": "›",
            "previous": "‹"
        }
    };

    if ($('.dt-table').length > 0) {
        $('.dt-table').DataTable({
            language: dtLanguage,
            pageLength: 10,
            ordering: true,
            responsive: false,
            dom: '<"dt-top-row"lf>rt<"dt-bottom-row"ip>',
            initComplete: function () {
                // Style the top row
                $('.dt-top-row').addClass('d-flex justify-content-between align-items-center px-3 py-2');
                $('.dt-bottom-row').addClass('d-flex justify-content-between align-items-center px-3 py-2');
            }
        });
    }

    // ── SweetAlert2 Flash Toast ──────────────────────────────────
    if (window.flashMessage) {
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
        Toast.fire({ icon: window.flashMessage.type, title: window.flashMessage.text });
    }

    // ── SweetAlert2 Confirm (form onsubmit) ─────────────────────
    $('form').on('submit', function (e) {
        var attr = $(this).attr('onsubmit');
        if (attr && attr.includes('confirm(')) {
            e.preventDefault();
            var form = this;
            var msg = 'Apakah Anda yakin ingin melanjutkan?';
            var m = attr.match(/confirm\('([^']+)'\)/);
            if (m) msg = m[1];
            Swal.fire({
                title: 'Konfirmasi',
                text: msg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Ya, Lanjutkan!',
                cancelButtonText: 'Batal',
                borderRadius: '16px'
            }).then(function (result) {
                if (result.isConfirmed) {
                    $(form).removeAttr('onsubmit');
                    form.submit();
                }
            });
        }
    });

    // ── SweetAlert2 Confirm (tombol Hapus dengan data-confirm) ───
    // Menggunakan event delegation agar bekerja di dalam DataTable
    $(document).on('click', '.btn-hapus', function (e) {
        e.preventDefault();
        var form = $(this).closest('.hapus-form');
        var msg  = form.data('confirm') || 'Apakah Anda yakin ingin menghapus data ini?';
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) {
                form[0].submit();
            }
        });
    });

    // ── SweetAlert2 Confirm (anchor onclick) ─────────────────────
    $('a[onclick*="confirm"]').on('click', function (e) {
        e.preventDefault();
        var link = this.href;
        var attr = $(this).attr('onclick');
        var msg = 'Apakah Anda yakin?';
        var m = attr.match(/confirm\('([^']+)'\)/);
        if (m) msg = m[1];
        Swal.fire({
            title: 'Konfirmasi',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Lanjutkan!',
            cancelButtonText: 'Batal'
        }).then(function (result) {
            if (result.isConfirmed) window.location.href = link;
        });
    });

    // ── Sidebar Toggle Mobile ────────────────────────────────────
    $('#sidebarToggle, .sidebar-backdrop').on('click', function() {
        $('.sidebar').toggleClass('show');
        $('.sidebar-backdrop').toggleClass('show');
    });
});
</script>
</body>
</html>
