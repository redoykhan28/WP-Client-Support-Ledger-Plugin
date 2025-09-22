// *** MODIFIED: The function now accepts an optional parameter for fresh data ***
function initializePortalReports(freshData) {
    var $ = jQuery;
    // *** MODIFIED: Use the freshData if it exists, otherwise fall back to the global object ***
    var reportData = freshData || window.wcsl_report_data_obj || {};

    if (!$('#wcslHoursPerClientChart').length) {
        return;
    }

    function formatDecimalHours(decimalHours) {
        if (isNaN(decimalHours) || decimalHours <= 0) { return '0m'; }
        var totalMinutes = Math.round(decimalHours * 60);
        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;
        var output = '';
        if (hours > 0) { output += hours + 'h'; }
        if (minutes > 0) { if (hours > 0) { output += ' '; } output += minutes + 'm'; }
        return output || '0m';
    }

    var hoursLabel = (reportData.i18n && reportData.i18n.hoursLabel) ? reportData.i18n.hoursLabel : 'Hours';
    var tasksLabel = (reportData.i18n && reportData.i18n.tasksLabel) ? reportData.i18n.tasksLabel : 'Number of Tasks';
    var chartColors = reportData.chartColors || ['rgba(57, 97, 140, 0.7)'];
    var chartBorderColors = reportData.chartBorderColors || ['rgb(57, 97, 140)'];

    if (reportData.totalBillableHours && $('#total-billable-hours-metric').length) {
        $('#total-billable-hours-metric').text(reportData.totalBillableHours.value_string);
    }

    var ctxHoursPerClient = document.getElementById('wcslHoursPerClientChart');
    if (ctxHoursPerClient && reportData.hoursPerClient) {
        if (reportData.hoursPerClient.labels && reportData.hoursPerClient.labels.length > 0) {
            new Chart(ctxHoursPerClient, {
                type: 'bar', 
                data: {
                    labels: reportData.hoursPerClient.labels,
                    datasets: [{ label: hoursLabel, data: reportData.hoursPerClient.data, backgroundColor: chartColors, borderColor: chartBorderColors, borderWidth: 1 }]
                },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, title: { display: true, text: hoursLabel } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c) { return (c.dataset.label || '') + ': ' + formatDecimalHours(c.parsed.x); } } } } }
            });
        } else { $(ctxHoursPerClient).parent().html('<p class="wcsl-chart-nodata">No client hour data to display.</p>'); }
    }

    var ctxBillablePerClient = document.getElementById('wcslBillableHoursPerClientChart');
    if (ctxBillablePerClient && reportData.billablePerClient) {
        if (reportData.billablePerClient.labels && reportData.billablePerClient.labels.length > 0) {
            new Chart(ctxBillablePerClient, {
                type: 'bar',
                data: {
                    labels: reportData.billablePerClient.labels,
                    datasets: [{ label: hoursLabel, data: reportData.billablePerClient.data, backgroundColor: chartColors, borderColor: chartBorderColors, borderWidth: 1 }]
                },
                options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', scales: { x: { beginAtZero: true, title: { display: true, text: hoursLabel } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c) { return (c.dataset.label || '') + ': ' + formatDecimalHours(c.parsed.x); } } } } }
            });
        } else { $(ctxBillablePerClient).parent().html('<p class="wcsl-chart-nodata">No clients with billable hours to display.</p>'); }
    }

    var ctxHoursByEmployee = document.getElementById('wcslHoursByEmployeeChart');
    if (ctxHoursByEmployee && reportData.hoursByEmployee) {
        if (reportData.hoursByEmployee.labels && reportData.hoursByEmployee.labels.length > 0) {
            new Chart(ctxHoursByEmployee, {
                type: 'doughnut',
                data: {
                    labels: reportData.hoursByEmployee.labels,
                    datasets: [{ label: hoursLabel, data: reportData.hoursByEmployee.data, backgroundColor: chartColors, borderColor: '#fff', borderWidth: 2 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' }, tooltip: { callbacks: { label: function(c) { return c.label + ': ' + formatDecimalHours(c.parsed); } } } } }
            });
        } else { $(ctxHoursByEmployee).parent().html('<p class="wcsl-chart-nodata">No employee hour data to display.</p>'); }
    }

    var ctxBillableTrend = document.getElementById('wcslBillableTrendChart');
    if (ctxBillableTrend && reportData.billableTrend) {
        if (reportData.billableTrend.labels && reportData.billableTrend.labels.length > 0) {
            new Chart(ctxBillableTrend, {
                type: 'line',
                data: {
                    labels: reportData.billableTrend.labels,
                    datasets: [{ label: hoursLabel, data: reportData.billableTrend.data, borderColor: 'rgb(57, 97, 140)', backgroundColor: 'rgba(57, 97, 140, 0.2)', borderWidth: 2, tension: 0.1, fill: true }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: hoursLabel } } }, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(c) { return (c.dataset.label || '') + ': ' + formatDecimalHours(c.parsed.y); } } } } }
            });
        } else { $(ctxBillableTrend).parent().html('<p class="wcsl-chart-nodata">No billable trend data to display.</p>'); }
    }

    var ctxSupportTasks = document.getElementById('wcslSupportTasksChart');
    if (ctxSupportTasks && reportData.supportTaskAnalysis) {
        if (reportData.supportTaskAnalysis.labels.length === 0) {
            $('#supportTasksChartContainer').html('<p class="wcsl-chart-nodata">No support task data for this period.</p>');
        } else {
            new Chart(ctxSupportTasks, {
                type: 'bar',
                data: {
                    labels: reportData.supportTaskAnalysis.labels,
                    datasets: [{ label: tasksLabel, data: reportData.supportTaskAnalysis.data, backgroundColor: 'rgba(54, 162, 235, 0.6)', borderColor: 'rgb(54, 162, 235)', borderWidth: 1 }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: tasksLabel }, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
    }

    var ctxFixingTasks = document.getElementById('wcslFixingTasksChart');
    if (ctxFixingTasks && reportData.fixingTaskAnalysis) {
        if (reportData.fixingTaskAnalysis.labels.length === 0) {
            $('#fixingTasksChartContainer').html('<p class="wcsl-chart-nodata">No fixing task data for this period.</p>');
        } else {
            new Chart(ctxFixingTasks, {
                type: 'bar',
                data: {
                    labels: reportData.fixingTaskAnalysis.labels,
                    datasets: [{ label: tasksLabel, data: reportData.fixingTaskAnalysis.data, backgroundColor: 'rgba(75, 192, 192, 0.6)', borderColor: 'rgb(75, 192, 192)', borderWidth: 1 }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: tasksLabel }, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
    }

    $('.wcsl-tab-link').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var targetId = $this.data('target');
        $('.wcsl-tab-link').removeClass('active');
        $this.addClass('active');
        $('.wcsl-tab-content').hide();
        $('#' + targetId).show();
    });
}

jQuery(document).ready(function($) {
    initializePortalReports();
});