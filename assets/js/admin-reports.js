jQuery(document).ready(function($) {
    // console.log('WCSL Admin Reports JS Loaded.');
    
    var reportData = window.wcsl_report_data_obj || {}; 
    // console.log('WCSL Localized Data for Reports Page:', reportData);

    // --- JavaScript Helper to Format Decimal Hours into "Xh Ym" string ---
    function formatDecimalHours(decimalHours) {
        if (isNaN(decimalHours) || decimalHours <= 0) {
            return '0m';
        }
        var totalMinutes = Math.round(decimalHours * 60);
        
        var hours = Math.floor(totalMinutes / 60);
        var minutes = totalMinutes % 60;
        var output = '';

        if (hours > 0) {
            output += hours + 'h';
        }
        if (minutes > 0) {
            if (hours > 0) {
                output += ' ';
            }
            output += minutes + 'm';
        }
        return output || '0m';
    }

    // --- Helper function to format chart titles with the localized date range ---
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
                title = defaultTitleBase; // Fallback
            }
        }
        return title;
    }
    
    var hoursLabel = (reportData.i18n && reportData.i18n.hoursLabel) ? reportData.i18n.hoursLabel : 'Hours';
    var tasksLabel = (reportData.i18n && reportData.i18n.tasksLabel) ? reportData.i18n.tasksLabel : 'Number of Tasks';
    var chartColors = reportData.chartColors || ['rgba(57, 97, 140, 0.7)'];
    var chartBorderColors = reportData.chartBorderColors || ['rgb(57, 97, 140)'];

    // --- Chart 1: Hours Per Client ---
    var ctxHoursPerClient = document.getElementById('wcslHoursPerClientChart');
    var hoursPerClientData = reportData.hoursPerClient || { labels: [], data: [], error: 'Data not initialized.'};

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
                    scales: { 
                        x: { 
                            beginAtZero: true, 
                            title: { display: true, text: hoursLabel },
                            ticks: {
                                callback: function(value, index, ticks) {
                                    return formatDecimalHours(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: formatChartTitle('hoursSpentByClientTitle', 'Hours Spent by Client') },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let currentLabel = context.dataset.label || hoursLabel;
                                    if (currentLabel) currentLabel += ': ';
                                    if (context.parsed.x !== null) currentLabel += formatDecimalHours(context.parsed.x);
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
    var ctxBillablePerClient = document.getElementById('wcslBillableHoursPerClientChart');
    var billableClientData = reportData.billablePerClient || { labels: [], data: [], error: 'Data not initialized.'};

    if (ctxBillablePerClient) {
        if (billableClientData.error && billableClientData.error !== '') {
            $(ctxBillablePerClient).parent().html('<p class="wcsl-chart-error">' + billableClientData.error + '</p>');
        } else if (billableClientData.labels && billableClientData.labels.length > 0) {
            new Chart(ctxBillablePerClient, {
                type: 'bar',
                data: {
                    labels: billableClientData.labels,
                    datasets: [{
                        label: hoursLabel,
                        data: billableClientData.data,
                        backgroundColor: chartColors,
                        borderColor: chartBorderColors,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                    scales: { 
                        x: { 
                            beginAtZero: true, 
                            title: { display: true, text: hoursLabel },
                            ticks: {
                                stepSize: 1, 
                                callback: function(value, index, ticks) {
                                    if (Math.floor(value) === value) { 
                                        return formatDecimalHours(value);
                                    }
                                    return '';
                                }
                            }
                        } 
                    },
                    plugins: {
                        legend: { display: false },
                        title: { 
                            display: true, 
                            text: formatChartTitle('billableHoursByClientTitle', 'Total Billable Hours by Client')
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let currentLabel = context.dataset.label || hoursLabel;
                                    if (currentLabel) currentLabel += ': ';
                                    if (context.parsed.x !== null) {
                                        currentLabel += formatDecimalHours(context.parsed.x);
                                    }
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
                                label: function(context) {
                                    let currentLabel = context.label || '';
                                    if (currentLabel) currentLabel += ': ';
                                    if (context.parsed !== null) {
                                        currentLabel += formatDecimalHours(context.parsed);
                                    }
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

    // --- Billable Hours Trend (Line Chart) ---
    var ctxBillableTrend = document.getElementById('wcslBillableTrendChart');
    var billableTrendData = reportData.billableTrend || { labels: [], data: [], error: 'Data not initialized.' };
    if (ctxBillableTrend) {
        if (billableTrendData.error && billableTrendData.error !== '') {
            $(ctxBillableTrend).parent().html('<p class="wcsl-chart-error">' + billableTrendData.error + '</p>');
        } else if (billableTrendData.labels && billableTrendData.labels.length > 0) {
            const colorUp = 'rgb(75, 192, 192)';
            const colorDown = 'rgb(255, 99, 132)';
            const colorFlat = 'rgb(150, 150, 150)';
            const colorUpFill = 'rgba(75, 192, 192, 0.2)';
            const colorDownFill = 'rgba(255, 99, 132, 0.2)';
            new Chart(ctxBillableTrend, {
                type: 'line',
                data: {
                    labels: billableTrendData.labels,
                    datasets: [{
                        label: hoursLabel, data: billableTrendData.data, borderWidth: 2, tension: 0.1, fill: true,
                        backgroundColor: (context) => {
                            const chart = context.chart; const {ctx, chartArea} = chart;
                            if (!chartArea) { return null; }
                            const gradient = ctx.createLinearGradient(chartArea.left, 0, chartArea.right, 0);
                            const totalPoints = context.dataset.data.length - 1;
                            if (totalPoints <= 0) return colorUpFill;
                            for (let i = 0; i <= totalPoints; i++) {
                                const stopPosition = i / totalPoints;
                                const previousValue = i > 0 ? context.dataset.data[i - 1] : context.dataset.data[i];
                                const currentValue = context.dataset.data[i];
                                if (currentValue >= previousValue) { gradient.addColorStop(stopPosition, colorUpFill); } else { gradient.addColorStop(stopPosition, colorDownFill); }
                            }
                            return gradient;
                        },
                        segment: { borderColor: ctx => { if (ctx.p0DataIndex === 0) return colorFlat; if (ctx.p1.raw > ctx.p0.raw) return colorUp; if (ctx.p1.raw < ctx.p0.raw) return colorDown; return colorFlat; } },
                        pointBackgroundColor: (context) => {
                            const index = context.dataIndex; if (index === 0) return colorFlat;
                            const currentValue = context.dataset.data[index]; const previousValue = context.dataset.data[index - 1];
                            if (currentValue > previousValue) return colorUp; if (currentValue < previousValue) return colorDown; return colorFlat;
                        }
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, title: { display: true, text: hoursLabel } } },
                    plugins: {
                        legend: { display: false }, title: { display: false },
                        tooltip: { callbacks: { label: function(context) { let label = context.dataset.label || hoursLabel; if (label) { label += ': '; } if (context.parsed.y !== null) { label += formatDecimalHours(context.parsed.y); } return label; } } }
                    }
                }
            });
        } else { $(ctxBillableTrend).parent().html('<p class="wcsl-chart-nodata">' + (billableTrendData.error || 'No billable trend data to display.') + '</p>'); }
    }

    // --- NEW: Task Analysis Charts (Support & Fixing) ---
    var ctxSupportTasks = document.getElementById('wcslSupportTasksChart');
    var supportTaskData = reportData.supportTaskAnalysis || { labels: [], data: [] };
    if (ctxSupportTasks) {
        if (supportTaskData.labels.length === 0) {
            $('#supportTasksChartContainer').html('<p class="wcsl-chart-nodata">No support task data to display for this period.</p>');
        } else {
            new Chart(ctxSupportTasks, {
                type: 'bar',
                data: {
                    labels: supportTaskData.labels,
                    datasets: [{
                        label: tasksLabel, data: supportTaskData.data,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgb(54, 162, 235)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: tasksLabel }, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
    }

    var ctxFixingTasks = document.getElementById('wcslFixingTasksChart');
    var fixingTaskData = reportData.fixingTaskAnalysis || { labels: [], data: [] };
    if (ctxFixingTasks) {
        if (fixingTaskData.labels.length === 0) {
            $('#fixingTasksChartContainer').html('<p class="wcsl-chart-nodata">No fixing task data to display for this period.</p>');
        } else {
            new Chart(ctxFixingTasks, {
                type: 'bar',
                data: {
                    labels: fixingTaskData.labels,
                    datasets: [{
                        label: tasksLabel, data: fixingTaskData.data,
                        backgroundColor: 'rgba(75, 192, 192, 0.6)',
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 1
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, title: { display: true, text: tasksLabel }, ticks: { stepSize: 1 } } }, plugins: { legend: { display: false } } }
            });
        }
    }

    // --- NEW: Tab switching logic ---
    $('.wcsl-tab-link').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        var targetId = $this.data('target');
        $('.wcsl-tab-link').removeClass('active');
        $this.addClass('active');
        $('.wcsl-tab-content').hide();
        $('#' + targetId).show();
    });
});