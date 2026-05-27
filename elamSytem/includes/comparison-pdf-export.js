/**
 * PDF export: one continuous capture of #comparison-pdf-root, sliced across A3 pages.
 * Requires: html2canvas, jspdf (UMD), #comparison-pdf-root, #btnDownloadComparisonPdf
 */
(function () {
    var btn = document.getElementById('btnDownloadComparisonPdf');
    var root = document.getElementById('comparison-pdf-root');
    if (!btn || !root) {
        return;
    }

    function prepareDom() {
        root.style.overflow = 'visible';
        root.style.height = 'auto';
        root.querySelectorAll('.table-wrap, .pdf-table-scroll').forEach(function (el) {
            el.style.overflow = 'visible';
            el.style.maxWidth = 'none';
            el.style.width = '100%';
        });
        root.querySelectorAll('table').forEach(function (tbl) {
            tbl.style.display = 'table';
            tbl.style.visibility = 'visible';
        });
        var main = document.querySelector('.app-main');
        if (main) {
            main.style.overflow = 'visible';
        }
    }

    function captureElement(el) {
        var w = Math.max(el.scrollWidth, el.offsetWidth, 1);
        var h = Math.max(el.scrollHeight, el.offsetHeight, 1);
        return html2canvas(el, {
            scale: 1.15,
            useCORS: true,
            allowTaint: true,
            logging: false,
            backgroundColor: '#ffffff',
            scrollX: 0,
            scrollY: 0,
            width: w,
            height: h,
            windowWidth: w,
            windowHeight: h
        });
    }

    function addCanvasToPdf(pdf, canvas) {
        var margin = 6;
        var pageW = pdf.internal.pageSize.getWidth();
        var pageH = pdf.internal.pageSize.getHeight();
        var usableW = pageW - margin * 2;
        var usableH = pageH - margin * 2;
        var imgH = (canvas.height * usableW) / canvas.width;
        var imgData = canvas.toDataURL('image/jpeg', 0.9);
        var heightLeft = imgH;
        var position = margin;
        var pageIndex = 0;

        pdf.addImage(imgData, 'JPEG', margin, position, usableW, imgH);
        heightLeft -= usableH;
        pageIndex += 1;

        while (heightLeft > 0) {
            position = margin - (imgH - heightLeft);
            pdf.addPage('a3', 'landscape');
            pdf.addImage(imgData, 'JPEG', margin, position, usableW, imgH);
            heightLeft -= usableH;
            pageIndex += 1;
        }

        return pageIndex;
    }

    btn.addEventListener('click', function () {
        if (btn.disabled) {
            return;
        }
        if (typeof html2canvas === 'undefined' || typeof window.jspdf === 'undefined') {
            alert('PDF libraries ntizashoboye gukurura. Refresh page cyangwa ukoreshe Print.');
            return;
        }

        var filename = btn.getAttribute('data-pdf-filename') || 'comparison.pdf';
        btn.disabled = true;
        var statusEl = document.getElementById('pdf-export-status');
        if (statusEl) {
            statusEl.textContent = 'Generating PDF… please wait';
        }

        prepareDom();
        var jsPDF = window.jspdf.jsPDF;
        var pdf = new jsPDF({ unit: 'mm', format: 'a3', orientation: 'landscape', compress: true });

        captureElement(root)
            .then(function (canvas) {
                var pages = addCanvasToPdf(pdf, canvas);
                pdf.save(filename);
                btn.disabled = false;
                if (statusEl) {
                    statusEl.textContent = pages <= 2 ? '' : ('PDF: ' + pages + ' pages');
                    if (pages <= 2) {
                        statusEl.textContent = '';
                    }
                }
            })
            .catch(function (err) {
                console.error(err);
                btn.disabled = false;
                if (statusEl) {
                    statusEl.textContent = '';
                }
                alert('PDF ntiyashoboye gukora. Koresha Print cyangwa ongera ugerageze.');
            });
    });
})();
