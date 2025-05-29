jQuery(document).ready(function ($) {
    // console.log('WCSL Admin Reports JS Loaded.');
    var reportData = window.wcsl_report_data_obj || {};
    // console.log('WCSL Localized Data for Reports Page:', reportData);

    function formatChartTitle(titleTemplateKey, defaultTitleBase) {
        let title = defaultTitleBase || 'Report';
        if (reportData.i18n && reportData.i18n[titleTemplateKey] &&
            reportData.report_start_date_formatted && reportData.report_end_date_formatted) {
            try {
                title = reportData.i18n[titleTemplateKey]
                    .replace('%s', reportData.report_start_date_formatted)
                    .replace('%s', reportData.report_end_date_formatted);
            } catch (e) {
                console.error("Error formatting title with key: " + titleTemplateKey, e);
                title = defaultTitleBase + ' (' + reportData.report_start_date_formatted + ' - ' + reportData.report_end_date_formatted + ')';
            }
        } else if (reportData.report_start_date_formatted && reportData.report_end_date_formatted) {
            title = defaultTitleBase + ' (' + reportData.report_start_date_formatted + ' - ' + reportData.report_end_date_formatted + ')';
        }
        return title;
    }

    var hoursLabel = (reportData.i18n && reportData.i18n.hoursLabel) ? reportData.i18n.hoursLabel : 'Hours';
    var chartColors = reportData.chartColors || ['rgba(57, 97, 140, 0.7)']; // Default if not provided
    var chartBorderColors = reportData.chartBorderColors || ['rgb(57, 97, 140)'];

    // --- Chart 1: Hours Per Client ---
    var ctxHoursPerClient = document.getElementById('wcslHoursPerClientChart');
    var hoursPerClientData = reportData.hoursPerClient || { labels: [], data: [], error: 'Data not initialized.' };

    if (ctxHoursPerClient) {
        if (hoursPerClientData.error && hoursPerClientData.error !== '') {
            $(ctxHoursPerClient).parent().html('<p class="wcsl-chart-error">' + hoursPerClientData.error + '</p>');
        } else if (hoursPerClientData.labels && hoursPerClientData.labels.length > 0) {
            new Chart(ctxHoursPerClient, {
                type: 'bar',
                data: {
                    labels: hoursPerClientData.labels,
                    datasets: [{
                        label: hoursLabel,
                        data: hoursPerClientData.data,
                        backgroundColor: chartColors,
                        borderColor: chartBorderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    scales: { x: { beginAtZero: true, title: { display: true, text: hoursLabel } } },
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: formatChartTitle('hoursSpentByClientTitle', 'Hours Spent by Client') },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let currentLabel = context.dataset.label || hoursLabel;
                                    if (currentLabel) currentLabel += ': ';
                                    if (context.parsed.x !== null) currentLabel += context.parsed.x.toFixed(2) + ' ' + hoursLabel.toLowerCase();
                                    return currentLabel;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            $(ctxHoursPerClient).parent().html('<p class="wcsl-chart-nodata">' + (hoursPerClientData.error || 'No client hour data to display.') + '</p>');
        }
    }

    // --- Chart 2: Billable Hours PER CLIENT ---
    var ctxBillablePerClient = document.getElementById('wcslBillableHoursPerClientChart'); // Updated ID
    var billableClientData = reportData.billablePerClient || { labels: [], data: [], error: 'Data not initialized.' }; // Updated data key

    if (ctxBillablePerClient) {
        if (billableClientData.error && billableClientData.error !== '') {
            $(ctxBillablePerClient).parent().html('<p class="wcsl-chart-error">' + billableClientData.error + '</p>');
        } else if (billableClientData.labels && billableClientData.labels.length > 0) {
            new Chart(ctxBillablePerClient, {
                type: 'bar',
                data: {
                    labels: billableClientData.labels, // Client Names
                    datasets: [{
                        label: hoursLabel, // Or "Billable Hours"
                        data: billableClientData.data, // Billable hours for each client
                        backgroundColor: chartColors,
                        borderColor: chartBorderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    scales: { x: { beginAtZero: true, title: { display: true, text: hoursLabel } } },
                    plugins: {
                        legend: { display: false },
                        title: {
                            display: true,
                            text: formatChartTitle('billableHoursByClientTitle', 'Total Billable Hours by Client') // Updated title key
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let currentLabel = context.dataset.label || hoursLabel;
                                    if (currentLabel) currentLabel += ': ';
                                    if (context.parsed.x !== null) currentLabel += context.parsed.x.toFixed(2) + ' ' + hoursLabel.toLowerCase();
                                    return currentLabel;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            $(ctxBillablePerClient).parent().html('<p class="wcsl-chart-nodata">' + (billableClientData.error || 'No clients with billable hours to display.') + '</p>');
        }
    }

    // --- Chart 3: Hours by Employee (Doughnut Chart) ---
    var ctxHoursByEmployee = document.getElementById('wcslHoursByEmployeeChart');
    var employeeHoursData = reportData.hoursByEmployee || { labels: [], data: [], error: 'Data not initialized.' };

    if (ctxHoursByEmployee) {
        if (employeeHoursData.error && employeeHoursData.error !== '') {
            $(ctxHoursByEmployee).parent().html('<p class="wcsl-chart-error">' + employeeHoursData.error + '</p>');
        } else if (employeeHoursData.labels && employeeHoursData.labels.length > 0) {
            new Chart(ctxHoursByEmployee, {
                type: 'doughnut',
                data: {
                    labels: employeeHoursData.labels,
                    datasets: [{
                        label: hoursLabel,
                        data: employeeHoursData.data,
                        backgroundColor: chartColors,
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right' },
                        title: {
                            display: true,
                            text: formatChartTitle('hoursByEmployeeTitle', 'Hours Logged by Employee')
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    let currentLabel = context.label || '';
                                    if (currentLabel) currentLabel += ': ';
                                    if (context.parsed !== null) currentLabel += context.parsed.toFixed(2) + ' ' + hoursLabel.toLowerCase();
                                    return currentLabel;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            $(ctxHoursByEmployee).parent().html('<p class="wcsl-chart-nodata">' + (employeeHoursData.error || 'No employee hour data to display.') + '</p>');
        }
    }
});