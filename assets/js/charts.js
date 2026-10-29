/**
 * Expense Behaviour Analytical Platform (EBAP)
 * Chart.js Visualization Utility Library
 */

const EBAPCharts = {
    // Professional color palette
    palette: [
        '#4f46e5', '#10b981', '#f59e0b', '#ef4444', 
        '#06b6d4', '#8b5cf6', '#ec4899', '#f97316', 
        '#14b8a6', '#84cc16', '#64748b', '#3b82f6'
    ],

    /**
     * Line Chart for Daily Spending Trend
     */
    renderDailyTrend: function (canvasId, labels, dataPoints, currencySymbol = '₹') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Daily Spending (' + currencySymbol + ')',
                    data: dataPoints,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: dataPoints.length > 31 ? 0 : 3,
                    pointHoverRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return currencySymbol + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { maxTicksLimit: 12, font: { size: 11 } }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.6)' },
                        ticks: {
                            callback: function (val) {
                                return currencySymbol + val;
                            },
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    },

    /**
     * Doughnut Chart for Category Breakdown
     */
    renderCategoryDoughnut: function (canvasId, labels, dataPoints, colors, currencySymbol = '₹') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataPoints,
                    backgroundColor: (colors && colors.length) ? colors : EBAPCharts.palette,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            font: { size: 11 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const val = Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                                return ' ' + context.label + ': ' + currencySymbol + val;
                            }
                        }
                    }
                }
            }
        });
    },

    /**
     * Grouped Bar Chart for Monthly Income vs Expense
     */
    renderIncomeVsExpense: function (canvasId, labels, incomeData, expenseData, currencySymbol = '₹') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Income',
                        data: incomeData,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    },
                    {
                        label: 'Expenses',
                        data: expenseData,
                        backgroundColor: '#ef4444',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.dataset.label + ': ' + currencySymbol + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (val) { return currencySymbol + val; }
                        }
                    }
                }
            }
        });
    },

    /**
     * Budget vs Actual Spending Bar Chart
     */
    renderBudgetVsActual: function (canvasId, labels, budgetLimits, actualSpent, currencySymbol = '₹') {
        const ctx = document.getElementById(canvasId);
        if (!ctx) return null;

        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Allocated Budget',
                        data: budgetLimits,
                        backgroundColor: '#cbd5e1',
                        borderRadius: 6
                    },
                    {
                        label: 'Actual Spending',
                        data: actualSpent,
                        backgroundColor: '#4f46e5',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return ' ' + context.dataset.label + ': ' + currencySymbol + Number(context.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (val) { return currencySymbol + val; }
                        }
                    },
                    y: { grid: { display: false } }
                }
            }
        });
    }
};
