/**
 * user.js — Diplomat management and contact removal.
 * Native fetch() — no jQuery dependency.
 */

document.addEventListener('DOMContentLoaded', function () {
    // Read CSRF token once from the meta tag injected by the layout.
    const csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '';

    /**
     * Build POST fetch options with CSRF header and JSON accept.
     * @param {string|null} body - URL-encoded body string, or null for empty body.
     * @returns {RequestInit}
     */
    function postOptions(body) {
        return {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: body || '',
        };
    }

    // -------------------------------------------------------------------------
    // Diplomat counter elements
    // -------------------------------------------------------------------------
    const maxDiplomatsEl = document.getElementById('maxDiplomats');
    const usedDiplomatsEl = document.getElementById('usedDiplomats');

    const max = maxDiplomatsEl ? parseInt(maxDiplomatsEl.textContent, 10) : 0;

    // -------------------------------------------------------------------------
    // Helper: get current used-diplomat count from the DOM element.
    // -------------------------------------------------------------------------
    function getUsedCount() {
        return usedDiplomatsEl ? parseInt(usedDiplomatsEl.textContent, 10) : 0;
    }

    // -------------------------------------------------------------------------
    // Helper: update the used-diplomat counter in the DOM.
    // -------------------------------------------------------------------------
    function setUsedCount(value) {
        if (usedDiplomatsEl) {
            usedDiplomatsEl.textContent = value;
        }
    }

    // -------------------------------------------------------------------------
    // Helper: show/hide all diplomat action links.
    // -------------------------------------------------------------------------
    function setDiplomatLinksVisible(visible) {
        document.querySelectorAll('.diplomats a').forEach(function (link) {
            link.style.display = visible ? '' : 'none';
        });
    }

    // -------------------------------------------------------------------------
    // Add a diplomat to a contact.
    // -------------------------------------------------------------------------
    document.querySelectorAll('.diplomats .addDiplomat').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            setDiplomatLinksVisible(false);

            const parent = btn.parentElement;
            const icons = parent.querySelectorAll('img');
            const url = btn.getAttribute('href');

            fetch(url, postOptions(null))
                .then(function (res) {
                    return res.json();
                })
                .then(function () {
                    // Clone the last icon and prepend it to the parent container.
                    if (icons.length > 0) {
                        const clone = icons[icons.length - 1].cloneNode(true);
                        parent.insertBefore(clone, parent.firstChild);
                    }

                    setDiplomatLinksVisible(true);

                    const count = getUsedCount() + 1;
                    setUsedCount(count);

                    if (count >= max) {
                        document.querySelectorAll('.diplomats .addDiplomat').forEach(function (el) {
                            el.style.display = 'none';
                        });
                    }
                });
        });
    });

    // -------------------------------------------------------------------------
    // Remove a diplomat from a contact.
    // -------------------------------------------------------------------------
    document.querySelectorAll('.diplomats .removeDiplomat').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            setDiplomatLinksVisible(false);

            const removeIcon = btn;
            const parent = btn.parentElement;
            const icons = parent.querySelectorAll('img');
            const url = btn.getAttribute('href');

            fetch(url, postOptions(null))
                .then(function (res) {
                    return res.json();
                })
                .then(function () {
                    if (icons.length > 0) {
                        icons[icons.length - 1].remove();
                    }

                    setDiplomatLinksVisible(true);

                    // Hide the remove button when only one or fewer icons remain.
                    if (icons.length - 1 <= 1) {
                        removeIcon.style.display = 'none';
                    }

                    const count = getUsedCount() - 1;
                    setUsedCount(count);
                });
        });
    });

    // -------------------------------------------------------------------------
    // Initial state: hide removeDiplomat buttons where count <= 1.
    // -------------------------------------------------------------------------
    document.querySelectorAll('.diplomats').forEach(function (container) {
        const count = container.querySelectorAll('img').length;
        if (count <= 1) {
            const removeBtn = container.querySelector('.removeDiplomat');
            if (removeBtn) {
                removeBtn.style.display = 'none';
            }
        }
    });

    // -------------------------------------------------------------------------
    // Initial state: hide addDiplomat buttons when all slots are filled.
    // -------------------------------------------------------------------------
    const initialIconCount = document.querySelectorAll('.diplomats img').length;
    if (initialIconCount >= max) {
        document.querySelectorAll('.diplomats .addDiplomat').forEach(function (el) {
            el.style.display = 'none';
        });
    }

    // -------------------------------------------------------------------------
    // Remove a contact entry.
    // Note: uses event delegation to handle dynamically inserted rows as well.
    // -------------------------------------------------------------------------
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.removeContact');
        if (!btn) return;

        e.preventDefault();

        const url = btn.getAttribute('href');

        fetch(url, postOptions(null))
            .then(function (res) {
                return res.json();
            })
            .then(function () {
                // Hide the contact row (btn → td → tr).
                const row = btn.closest('tr');
                if (row) {
                    row.style.display = 'none';
                }
            });
    });

    // -------------------------------------------------------------------------
    // Spider chart — requires the Flot charting library ($.plot).
    // This feature is not currently active; kept for future re-integration.
    // -------------------------------------------------------------------------
    function showSpiderChart() {
        var labels1 = document.querySelectorAll('#spider-chart-1-data tr th');
        var values1 = document.querySelectorAll('#spider-chart-1-data tr td');

        var options1 = {
            series: {
                spider: {
                    active: true,
                    connection: { width: 1 },
                    legs: {
                        font: '11px Verdana',
                        data: [
                            { label: labels1[0].innerHTML },
                            { label: labels1[1].innerHTML },
                            { label: labels1[2].innerHTML },
                            { label: labels1[3].innerHTML },
                        ],
                        legScaleMax: 1,
                        legScaleMin: 0.8,
                        legStartAngle: -90,
                    },
                    spiderSize: 0.9,
                    scaleMode: 'others',
                },
            },
            grid: {
                hoverable: false,
                clickable: false,
                tickColor: 'rgba(0,0,0,0.2)',
                mode: 'spider',
            },
        };

        var data1 = [
            {
                label: '',
                data: [
                    [0, values1[0].innerHTML],
                    [1, values1[1].innerHTML],
                    [2, values1[2].innerHTML],
                    [3, values1[3].innerHTML],
                ],
                spider: { show: true, lineWidth: 0 },
            },
        ];

        // $.plot() is the Flot charting API — requires flot + flot-spider plugin.
        if (typeof $.plot === 'function') {
            $.plot(document.getElementById('spider-chart-1'), data1, options1);
        }

        var labels2 = document.querySelectorAll('#spider-chart-2-data tr th');
        var values2 = document.querySelectorAll('#spider-chart-2-data tr td');

        var options2 = {
            series: {
                spider: {
                    active: true,
                    connection: { width: 1 },
                    legs: {
                        font: '11px Verdana',
                        data: [
                            { label: labels2[0].innerHTML },
                            { label: labels2[1].innerHTML },
                            { label: labels2[2].innerHTML },
                            { label: labels2[3].innerHTML },
                            { label: labels2[4].innerHTML },
                        ],
                        legScaleMax: 1,
                        legScaleMin: 0.8,
                        legStartAngle: -90,
                    },
                    spiderSize: 0.9,
                    scaleMode: 'others',
                },
            },
            grid: {
                hoverable: false,
                clickable: false,
                tickColor: 'rgba(0,0,0,0.2)',
                mode: 'spider',
            },
        };

        var data2 = [
            {
                label: '',
                data: [
                    [0, values2[0].innerHTML],
                    [1, values2[1].innerHTML],
                    [2, values2[2].innerHTML],
                    [3, values2[3].innerHTML],
                    [4, values2[4].innerHTML],
                ],
                spider: { show: true, lineWidth: 0 },
            },
        ];

        // $.plot() is the Flot charting API — requires flot + flot-spider plugin.
        if (typeof $.plot === 'function') {
            $.plot(document.getElementById('spider-chart-2'), data2, options2);
        }
    }

    if (document.getElementById('spider-chart-1')) {
        showSpiderChart();
    }
});
