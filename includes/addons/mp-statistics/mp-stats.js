jQuery(document).ready(function ($) {
    let salesChart;

    function fetchSalesData(period, month1 = null, month2 = null) {
        $.ajax({
            url: mpStatsAjax.ajax_url,
            method: 'POST',
            data: {
                action: 'mp_get_sales_data',
                nonce: mpStatsAjax.nonce,
                period: period,
                month1: month1,
                month2: month2,
            },
            success: function (response) {
                if (salesChart) {
                    salesChart.destroy();
                }

                const labels = response.data.map(item => item.month);
                const data = response.data.map(item => parseFloat(item.total));

                const ctx = document.getElementById('salesChart').getContext('2d');
                salesChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Umsatz',
                            data: data,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                        }],
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                            },
                        },
                    },
                });

                // Gesamtumsatz anzeigen
                $('#mp-stats-total-value').text(response.total.toFixed(2));
            },
        });
    }

    // Filter anwenden
    $('#mp-stats-apply-filters').on('click', function () {
        const period = $('#mp-stats-period').val();
        const month1 = $('#mp-stats-month1').val();
        const month2 = $('#mp-stats-month2').val();

        fetchSalesData(period, month1, month2);
    });

    // Initiale Daten laden
    fetchSalesData('this_month');
});