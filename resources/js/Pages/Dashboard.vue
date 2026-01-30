<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, nextTick, watch } from 'vue';

const props = defineProps({
    stats: Object,
    charts: Object,
    recentActivity: Object,
    profitData: Object, // Permission-gated profit data
});

const page = usePage();
const user = computed(() => page.props.auth?.user || page.props.user);

// Chart instances for cleanup
let salesChart = null;
let customersChart = null;
let statusChart = null;
let movementChart = null;
let profitTrendChart = null;

// Track if charts are ready
const chartsReady = ref(false);

// Format currency with Indonesian locale
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount || 0);
};

// Format compact currency for KPI cards
const formatCompactCurrency = (amount) => {
    const num = amount || 0;
    if (num >= 1000000000) {
        return 'Rp ' + (num / 1000000000).toFixed(1) + 'B';
    } else if (num >= 1000000) {
        return 'Rp ' + (num / 1000000).toFixed(1) + 'M';
    } else if (num >= 1000) {
        return 'Rp ' + (num / 1000).toFixed(1) + 'K';
    }
    return formatCurrency(num);
};

// Get status badge class for orders
const getOrderStatusClass = (status) => {
    const classes = {
        'draft': 'bg-gray-100 text-gray-600 border-gray-300',
        'confirmed': 'bg-blue-100 text-blue-600 border-blue-300',
        'processing': 'bg-yellow-100 text-yellow-600 border-yellow-300',
        'shipped': 'bg-purple-100 text-purple-600 border-purple-300',
        'delivered': 'bg-green-100 text-green-600 border-green-300',
        'received': 'bg-green-100 text-green-600 border-green-300',
        'partial': 'bg-yellow-100 text-yellow-600 border-yellow-300',
        'cancelled': 'bg-red-100 text-red-600 border-red-300',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-600 border-gray-300';
};

// Get payment status badge class
const getPaymentStatusClass = (status) => {
    const classes = {
        'unpaid': 'bg-red-100 text-red-600 border-red-300',
        'partial': 'bg-yellow-100 text-yellow-600 border-yellow-300',
        'paid': 'bg-green-100 text-green-600 border-green-300',
    };
    return classes[status?.toLowerCase()] || 'bg-gray-100 text-gray-600 border-gray-300';
};

// Check permission
const hasPermission = (permission) => {
    const userPermissions = page.props.user?.permissions || [];
    return userPermissions.includes(permission);
};

// Get greeting from server (uses server timezone)
const greeting = computed(() => props.stats?.greeting || 'Hello');

// Maximum retry attempts for chart initialization
let initRetryCount = 0;
const MAX_INIT_RETRIES = 10;

// Initialize charts with robust loading
const initCharts = () => {
    // Check if ApexCharts is available
    if (typeof ApexCharts === 'undefined') {
        if (initRetryCount < MAX_INIT_RETRIES) {
            initRetryCount++;
            console.warn(`ApexCharts not loaded yet, retry ${initRetryCount}/${MAX_INIT_RETRIES}...`);
            setTimeout(initCharts, 300);
        }
        return;
    }

    // Check if DOM elements exist
    const salesTrendEl = document.querySelector('#sales-trend-chart');
    const customersEl = document.querySelector('#top-customers-chart');

    if (!salesTrendEl && !customersEl && initRetryCount < MAX_INIT_RETRIES) {
        initRetryCount++;
        console.warn(`DOM not ready yet, retry ${initRetryCount}/${MAX_INIT_RETRIES}...`);
        setTimeout(initCharts, 200);
        return;
    }

    // Reset retry count on successful init
    initRetryCount = 0;

    // Destroy existing charts if any
    destroyCharts();

    // Wait for DOM elements to be ready
    nextTick(() => {
        // 1. Sales Trend Area Chart
        const salesTrendEl = document.querySelector('#sales-trend-chart');
        if (salesTrendEl && props.charts?.sales_trend) {
            const salesOptions = {
                series: [{
                    name: 'Sales',
                    data: props.charts.sales_trend.data || []
                }],
                chart: {
                    type: 'area',
                    height: 300,
                    fontFamily: 'inherit',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#22C55E'],
                xaxis: {
                    categories: props.charts.sales_trend.labels || [],
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            if (val >= 1000000000) return (val / 1000000000).toFixed(1) + 'B';
                            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val;
                        }
                    }
                },
                grid: {
                    borderColor: '#E5E7EB',
                    strokeDashArray: 4
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(val);
                        }
                    }
                }
            };
            salesChart = new ApexCharts(salesTrendEl, salesOptions);
            salesChart.render();
        }

        // 2. Top Customers Horizontal Bar Chart
        const customersEl = document.querySelector('#top-customers-chart');
        if (customersEl && props.charts?.top_customers) {
            const customersOptions = {
                series: [{
                    name: 'Revenue',
                    data: props.charts.top_customers.data || []
                }],
                chart: {
                    type: 'bar',
                    height: 300,
                    fontFamily: 'inherit',
                    toolbar: {
                        show: false
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '60%',
                        distributed: true
                    }
                },
                colors: ['#3B82F6', '#22C55E', '#F59E0B', '#EF4444', '#8B5CF6'],
                dataLabels: {
                    enabled: false
                },
                legend: {
                    show: false
                },
                xaxis: {
                    categories: props.charts.top_customers.labels || [],
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            if (val >= 1000000000) return (val / 1000000000).toFixed(1) + 'B';
                            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val;
                        }
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    }
                },
                grid: {
                    borderColor: '#E5E7EB',
                    strokeDashArray: 4
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(val);
                        }
                    }
                }
            };
            customersChart = new ApexCharts(customersEl, customersOptions);
            customersChart.render();
        }

        // 3. Order Status Donut Chart
        const statusEl = document.querySelector('#order-status-chart');
        if (statusEl && props.charts?.order_status) {
            const statusOptions = {
                series: props.charts.order_status.data || [],
                chart: {
                    type: 'donut',
                    height: 300,
                    fontFamily: 'inherit'
                },
                labels: props.charts.order_status.labels || [],
                colors: props.charts.order_status.colors || ['#22C55E', '#3B82F6', '#F59E0B', '#EF4444', '#6B7280'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#374151'
                                },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontWeight: 700,
                                    color: '#111827',
                                    formatter: function(val) {
                                        return val;
                                    }
                                },
                                total: {
                                    show: true,
                                    label: 'Total Orders',
                                    fontSize: '12px',
                                    fontWeight: 500,
                                    color: '#6B7280',
                                    formatter: function(w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    }
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    fontSize: '13px',
                    fontWeight: 500,
                    labels: {
                        colors: '#6B7280'
                    },
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 3
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 0
                }
            };
            statusChart = new ApexCharts(statusEl, statusOptions);
            statusChart.render();
        }

        // 4. Stock Movement Area Chart with 2 series
        const movementEl = document.querySelector('#stock-movement-chart');
        if (movementEl && props.charts?.stock_movement) {
            const movementOptions = {
                series: [
                    {
                        name: 'Stock In',
                        data: props.charts.stock_movement.stock_in || []
                    },
                    {
                        name: 'Stock Out',
                        data: props.charts.stock_movement.stock_out || []
                    }
                ],
                chart: {
                    type: 'area',
                    height: 300,
                    fontFamily: 'inherit',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#22C55E', '#EF4444'],
                xaxis: {
                    categories: props.charts.stock_movement.labels || [],
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '12px'
                        },
                        formatter: function(val) {
                            return Math.round(val);
                        }
                    }
                },
                grid: {
                    borderColor: '#E5E7EB',
                    strokeDashArray: 4
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '13px',
                    fontWeight: 500,
                    labels: {
                        colors: '#6B7280'
                    },
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 3
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false
                }
            };
            movementChart = new ApexCharts(movementEl, movementOptions);
            movementChart.render();
        }

        // 5. Profit Trend Line Chart (Permission-gated)
        const profitTrendEl = document.querySelector('#profit-trend-chart');
        if (profitTrendEl && props.profitData?.trend) {
            const profitTrendOptions = {
                series: [
                    {
                        name: 'Revenue',
                        data: props.profitData.trend.map(d => d.revenue)
                    },
                    {
                        name: 'COGS',
                        data: props.profitData.trend.map(d => d.cogs)
                    },
                    {
                        name: 'Gross Profit',
                        data: props.profitData.trend.map(d => d.profit)
                    }
                ],
                chart: {
                    type: 'area',
                    height: 280,
                    fontFamily: 'inherit',
                    toolbar: {
                        show: false
                    },
                    zoom: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                colors: ['#3B82F6', '#EF4444', '#22C55E'],
                xaxis: {
                    categories: props.profitData.trend.map(d => d.date_label),
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '11px'
                        },
                        rotate: -45,
                        rotateAlways: false,
                        hideOverlappingLabels: true,
                        showDuplicates: false,
                        trim: true
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    },
                    tickAmount: 10
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#6B7280',
                            fontSize: '11px'
                        },
                        formatter: function(val) {
                            if (val >= 1000000000) return (val / 1000000000).toFixed(1) + 'B';
                            if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                            if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                            return val;
                        }
                    }
                },
                grid: {
                    borderColor: '#E5E7EB',
                    strokeDashArray: 4
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'right',
                    fontSize: '12px',
                    fontWeight: 500,
                    labels: {
                        colors: '#6B7280'
                    },
                    markers: {
                        width: 10,
                        height: 10,
                        radius: 3
                    }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(val) {
                            return new Intl.NumberFormat('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                minimumFractionDigits: 0
                            }).format(val);
                        }
                    }
                }
            };
            profitTrendChart = new ApexCharts(profitTrendEl, profitTrendOptions);
            profitTrendChart.render();
        }

        chartsReady.value = true;
    });
};

// Destroy charts for cleanup
const destroyCharts = () => {
    if (salesChart) {
        salesChart.destroy();
        salesChart = null;
    }
    if (customersChart) {
        customersChart.destroy();
        customersChart = null;
    }
    if (statusChart) {
        statusChart.destroy();
        statusChart = null;
    }
    if (movementChart) {
        movementChart.destroy();
        movementChart = null;
    }
    if (profitTrendChart) {
        profitTrendChart.destroy();
        profitTrendChart = null;
    }
};

// Handle Inertia navigation finish
let removeFinishListener = null;

// Lifecycle hooks
onMounted(() => {
    // Reset retry count on mount
    initRetryCount = 0;

    // Use nextTick to ensure DOM is rendered, then start chart initialization
    nextTick(() => {
        // Small delay to allow ApexCharts to load from AppLayout
        setTimeout(initCharts, 100);
    });

    // Also listen for Inertia finish events (handles back/forward navigation)
    removeFinishListener = router.on('finish', () => {
        // Only reinitialize if we're on the dashboard and charts aren't ready
        if (!chartsReady.value) {
            initRetryCount = 0;
            nextTick(() => {
                setTimeout(initCharts, 100);
            });
        }
    });
});

onUnmounted(() => {
    destroyCharts();
    // Remove Inertia event listener
    if (removeFinishListener) {
        removeFinishListener();
    }
});

// Watch for prop changes to update charts
watch(() => props.charts, () => {
    if (chartsReady.value) {
        initRetryCount = 0;
        initCharts();
    }
}, { deep: true });

// Watch for profitData changes to update profit chart
watch(() => props.profitData, () => {
    if (chartsReady.value && props.profitData) {
        // Re-initialize profit chart when data changes
        initRetryCount = 0;
        nextTick(() => {
            if (profitTrendChart) {
                profitTrendChart.destroy();
                profitTrendChart = null;
            }
            initCharts();
        });
    }
}, { deep: true });
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="container-fluid">
            <div class="py-5">
                <!-- Welcome Section -->
                <div class="mb-5">
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">
                        {{ greeting }}, {{ user?.name?.split(' ')[0] }}!
                    </h1>
                    <p class="text-gray-500">Here's what's happening with your business today.</p>
                </div>

                <!-- KPI Cards - 4 columns on desktop -->
                <div class="grid grid-cols-2 xl:grid-cols-4 2xl:grid-cols-4 gap-3 mb-5">
                    <!-- Revenue MTD -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-chart-line-up text-green-600 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-green-600 truncate" :title="formatCurrency(stats?.revenue_mtd)">
                                        {{ formatCompactCurrency(stats?.revenue_mtd) }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">Revenue MTD</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expenses MTD -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-chart-line-down text-red-600 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-red-600 truncate" :title="formatCurrency(stats?.expenses_mtd)">
                                        {{ formatCompactCurrency(stats?.expenses_mtd) }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">Expenses MTD</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Receivables -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-entrance-right text-orange-600 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-orange-600 truncate" :title="formatCurrency(stats?.outstanding_receivables)">
                                        {{ formatCompactCurrency(stats?.outstanding_receivables) }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">Receivables</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Outstanding Payables -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-exit-left text-blue-600 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-blue-600 truncate" :title="formatCurrency(stats?.outstanding_payables)">
                                        {{ formatCompactCurrency(stats?.outstanding_payables) }}
                                    </div>
                                    <div class="text-xs text-gray-500 truncate">Payables</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Low Stock Count -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-yellow-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-notification-bing text-yellow-600 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-yellow-600">{{ stats?.low_stock_count || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Low Stock</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Out of Stock Count -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-cross-circle text-red-600 text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-red-600">{{ stats?.out_of_stock_count || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Out of Stock</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Users -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-people text-primary text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-gray-900">{{ stats?.total_users || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Users</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customers -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-user text-info text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-gray-900">{{ stats?.total_customers || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Customers</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sales Orders -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-handcart text-success text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-gray-900">{{ stats?.total_sales_orders || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Sales Orders</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-handcart text-warning text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-gray-900">{{ stats?.total_products || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Products</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Brands -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-shop text-danger text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-gray-900">{{ stats?.total_brands || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Brands</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-secondary/10 flex items-center justify-center flex-shrink-0">
                                    <i class="ki-filled ki-folder text-secondary text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-xl font-bold text-gray-900">{{ stats?.total_categories || 0 }}</div>
                                    <div class="text-xs text-gray-500 truncate">Categories</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Financial Overview Section (Permission-gated) -->
                <div v-if="profitData" class="mb-6">
                    <!-- Section Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <i class="ki-filled ki-chart-pie-4 text-emerald-600 text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Financial Overview</h2>
                                <p class="text-xs text-gray-500">
                                    {{ profitData.stats?.period?.start }} - {{ profitData.stats?.period?.end }}
                                </p>
                            </div>
                        </div>
                        <span class="badge badge-sm badge-success">
                            <i class="ki-filled ki-shield-tick text-xs mr-1"></i>
                            Authorized View
                        </span>
                    </div>

                    <!-- Profit Stats Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
                        <!-- Revenue -->
                        <div class="card bg-gradient-to-br from-blue-50 to-blue-100 border-blue-200">
                            <div class="card-body p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-blue-600 mb-1">Revenue</div>
                                        <div class="text-lg font-bold text-blue-900" :title="formatCurrency(profitData.stats?.revenue)">
                                            {{ formatCompactCurrency(profitData.stats?.revenue) }}
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span
                                                v-if="profitData.stats?.revenue_trend !== 0"
                                                class="text-xs font-medium"
                                                :class="profitData.stats?.revenue_trend >= 0 ? 'text-green-600' : 'text-red-600'"
                                            >
                                                <i
                                                    class="ki-filled text-[10px]"
                                                    :class="profitData.stats?.revenue_trend >= 0 ? 'ki-arrow-up' : 'ki-arrow-down'"
                                                ></i>
                                                {{ Math.abs(profitData.stats?.revenue_trend || 0) }}%
                                            </span>
                                            <span class="text-[10px] text-blue-500">vs prev</span>
                                        </div>
                                    </div>
                                    <div class="w-9 h-9 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                        <i class="ki-filled ki-chart-line-up text-blue-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COGS -->
                        <div class="card bg-gradient-to-br from-red-50 to-red-100 border-red-200">
                            <div class="card-body p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-red-600 mb-1">Cost of Goods</div>
                                        <div class="text-lg font-bold text-red-900" :title="formatCurrency(profitData.stats?.cogs)">
                                            {{ formatCompactCurrency(profitData.stats?.cogs) }}
                                        </div>
                                        <div class="text-[10px] text-red-500 mt-1">
                                            {{ profitData.stats?.order_count || 0 }} orders
                                        </div>
                                    </div>
                                    <div class="w-9 h-9 rounded-lg bg-red-500/20 flex items-center justify-center">
                                        <i class="ki-filled ki-chart-line-down text-red-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gross Profit -->
                        <div class="card bg-gradient-to-br from-emerald-50 to-emerald-100 border-emerald-200">
                            <div class="card-body p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-emerald-600 mb-1">Gross Profit</div>
                                        <div
                                            class="text-lg font-bold"
                                            :class="(profitData.stats?.gross_profit || 0) >= 0 ? 'text-emerald-900' : 'text-red-600'"
                                            :title="formatCurrency(profitData.stats?.gross_profit)"
                                        >
                                            {{ formatCompactCurrency(profitData.stats?.gross_profit) }}
                                        </div>
                                        <div class="flex items-center gap-1 mt-1">
                                            <span
                                                v-if="profitData.stats?.profit_trend !== 0"
                                                class="text-xs font-medium"
                                                :class="profitData.stats?.profit_trend >= 0 ? 'text-green-600' : 'text-red-600'"
                                            >
                                                <i
                                                    class="ki-filled text-[10px]"
                                                    :class="profitData.stats?.profit_trend >= 0 ? 'ki-arrow-up' : 'ki-arrow-down'"
                                                ></i>
                                                {{ Math.abs(profitData.stats?.profit_trend || 0) }}%
                                            </span>
                                            <span class="text-[10px] text-emerald-500">vs prev</span>
                                        </div>
                                    </div>
                                    <div class="w-9 h-9 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                        <i class="ki-filled ki-dollar text-emerald-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Margin % -->
                        <div class="card bg-gradient-to-br from-purple-50 to-purple-100 border-purple-200">
                            <div class="card-body p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="text-xs font-medium text-purple-600 mb-1">Gross Margin</div>
                                        <div
                                            class="text-lg font-bold"
                                            :class="{
                                                'text-green-600': (profitData.stats?.margin_percent || 0) >= 30,
                                                'text-yellow-600': (profitData.stats?.margin_percent || 0) >= 15 && (profitData.stats?.margin_percent || 0) < 30,
                                                'text-red-600': (profitData.stats?.margin_percent || 0) < 15
                                            }"
                                        >
                                            {{ profitData.stats?.margin_percent || 0 }}%
                                        </div>
                                        <div class="text-[10px] text-purple-500 mt-1">
                                            <span v-if="(profitData.stats?.margin_percent || 0) >= 30">Healthy</span>
                                            <span v-else-if="(profitData.stats?.margin_percent || 0) >= 15">Moderate</span>
                                            <span v-else>Needs Attention</span>
                                        </div>
                                    </div>
                                    <div class="w-9 h-9 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                        <i class="ki-filled ki-percentage text-purple-600"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profit Chart and Top Products Row -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                        <!-- Profit Trend Chart - 2/3 width -->
                        <div class="lg:col-span-2">
                            <div class="card h-full">
                                <div class="card-header border-b border-gray-200 px-5 py-4">
                                    <h3 class="text-base font-semibold text-gray-900">Profit Trend</h3>
                                    <span class="text-xs text-gray-500">Revenue, COGS & Gross Profit (Last 30 days)</span>
                                </div>
                                <div class="card-body p-5">
                                    <div id="profit-trend-chart"></div>
                                    <div v-if="!profitData.trend?.length" class="flex items-center justify-center h-[280px] text-gray-400">
                                        <div class="text-center">
                                            <i class="ki-filled ki-chart-line text-4xl mb-2"></i>
                                            <p class="text-sm">No profit data available</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Profitable Products - 1/3 width -->
                        <div class="lg:col-span-1">
                            <div class="card h-full">
                                <div class="card-header border-b border-gray-200 px-5 py-4">
                                    <h3 class="text-base font-semibold text-gray-900">Top Profitable Products</h3>
                                    <span class="text-xs text-gray-500">This month</span>
                                </div>
                                <div class="card-body p-0">
                                    <div v-if="profitData.top_products?.length" class="divide-y divide-gray-100">
                                        <div
                                            v-for="(product, index) in profitData.top_products"
                                            :key="product.id"
                                            class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors"
                                        >
                                            <div
                                                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white flex-shrink-0"
                                                :class="{
                                                    'bg-yellow-500': index === 0,
                                                    'bg-gray-400': index === 1,
                                                    'bg-amber-600': index === 2,
                                                    'bg-gray-300': index > 2
                                                }"
                                            >
                                                {{ index + 1 }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate" :title="product.name">
                                                    {{ product.name }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ product.units_sold }} units sold
                                                </div>
                                            </div>
                                            <div class="text-right flex-shrink-0">
                                                <div class="text-sm font-semibold text-emerald-600">
                                                    {{ formatCompactCurrency(product.profit) }}
                                                </div>
                                                <div
                                                    class="text-xs"
                                                    :class="{
                                                        'text-green-600': product.margin_percent >= 30,
                                                        'text-yellow-600': product.margin_percent >= 15 && product.margin_percent < 30,
                                                        'text-red-600': product.margin_percent < 15
                                                    }"
                                                >
                                                    {{ product.margin_percent }}% margin
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                                        <i class="ki-filled ki-chart-simple-3 text-4xl mb-3"></i>
                                        <p class="text-sm">No product profit data</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
                    <!-- Sales Trend Chart - 2/3 width -->
                    <div class="lg:col-span-2">
                        <div class="card h-full">
                            <div class="card-header border-b border-gray-200 px-5 py-4">
                                <h3 class="text-base font-semibold text-gray-900">Sales Trend</h3>
                                <span class="text-xs text-gray-500">Monthly sales performance</span>
                            </div>
                            <div class="card-body p-5">
                                <div id="sales-trend-chart"></div>
                                <div v-if="!charts?.sales_trend" class="flex items-center justify-center h-[300px] text-gray-400">
                                    <div class="text-center">
                                        <i class="ki-filled ki-chart-line text-4xl mb-2"></i>
                                        <p class="text-sm">No sales data available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Customers Chart - 1/3 width -->
                    <div class="lg:col-span-1">
                        <div class="card h-full">
                            <div class="card-header border-b border-gray-200 px-5 py-4">
                                <h3 class="text-base font-semibold text-gray-900">Top 5 Customers</h3>
                                <span class="text-xs text-gray-500">By revenue</span>
                            </div>
                            <div class="card-body p-5">
                                <div id="top-customers-chart"></div>
                                <div v-if="!charts?.top_customers" class="flex items-center justify-center h-[300px] text-gray-400">
                                    <div class="text-center">
                                        <i class="ki-filled ki-profile-user text-4xl mb-2"></i>
                                        <p class="text-sm">No customer data available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Row of Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-6">
                    <!-- Order Status Donut Chart - 1/3 width -->
                    <div class="lg:col-span-1">
                        <div class="card h-full">
                            <div class="card-header border-b border-gray-200 px-5 py-4">
                                <h3 class="text-base font-semibold text-gray-900">Order Status</h3>
                                <span class="text-xs text-gray-500">Distribution by status</span>
                            </div>
                            <div class="card-body p-5">
                                <div id="order-status-chart"></div>
                                <div v-if="!charts?.order_status" class="flex items-center justify-center h-[300px] text-gray-400">
                                    <div class="text-center">
                                        <i class="ki-filled ki-chart-pie-simple text-4xl mb-2"></i>
                                        <p class="text-sm">No order data available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Movement Chart - 2/3 width -->
                    <div class="lg:col-span-2">
                        <div class="card h-full">
                            <div class="card-header border-b border-gray-200 px-5 py-4">
                                <h3 class="text-base font-semibold text-gray-900">Stock Movement</h3>
                                <span class="text-xs text-gray-500">Stock in vs stock out</span>
                            </div>
                            <div class="card-body p-5">
                                <div id="stock-movement-chart"></div>
                                <div v-if="!charts?.stock_movement" class="flex items-center justify-center h-[300px] text-gray-400">
                                    <div class="text-center">
                                        <i class="ki-filled ki-graph-up text-4xl mb-2"></i>
                                        <p class="text-sm">No stock movement data available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section - Sales Orders & Purchase Orders Side by Side -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mb-6">
                    <!-- Recent Sales Orders -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200 px-5 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Recent Sales Orders</h3>
                                    <span class="text-xs text-gray-500">Latest customer orders</span>
                                </div>
                                <Link
                                    v-if="hasPermission('sales-orders.view')"
                                    :href="route('sales-orders.list')"
                                    class="btn btn-sm btn-light"
                                >
                                    View All
                                </Link>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="recentActivity?.recent_sales_orders?.length" class="overflow-x-auto">
                                <table class="table-auto w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase">SO Number</th>
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Customer</th>
                                            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="so in recentActivity.recent_sales_orders" :key="so.id" class="hover:bg-gray-50">
                                            <td class="px-5 py-3">
                                                <Link
                                                    :href="`/sales-orders/${so.id}`"
                                                    class="font-medium text-primary hover:text-primary-dark text-sm"
                                                >
                                                    {{ so.so_number }}
                                                </Link>
                                                <div class="text-xs text-gray-500">{{ so.order_date }}</div>
                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-700">
                                                <span class="truncate block max-w-[150px]" :title="so.customer_name">
                                                    {{ so.customer_name }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-900 text-right font-medium">
                                                {{ formatCurrency(so.grand_total) }}
                                            </td>
                                            <td class="px-5 py-3 text-center">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span
                                                        class="text-xs rounded-lg px-2 py-0.5 border capitalize"
                                                        :class="getOrderStatusClass(so.status)"
                                                    >
                                                        {{ so.status }}
                                                    </span>
                                                    <span
                                                        class="text-xs rounded-lg px-2 py-0.5 border capitalize"
                                                        :class="getPaymentStatusClass(so.payment_status)"
                                                    >
                                                        {{ so.payment_status }}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="ki-filled ki-basket text-4xl mb-3"></i>
                                <p class="text-sm">No recent sales orders</p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Purchase Orders -->
                    <div class="card">
                        <div class="card-header border-b border-gray-200 px-5 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">Recent Purchase Orders</h3>
                                    <span class="text-xs text-gray-500">Latest supplier orders</span>
                                </div>
                                <Link
                                    v-if="hasPermission('purchase-orders.view')"
                                    :href="route('purchase-orders.list')"
                                    class="btn btn-sm btn-light"
                                >
                                    View All
                                </Link>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div v-if="recentActivity?.recent_purchase_orders?.length" class="overflow-x-auto">
                                <table class="table-auto w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase">PO Number</th>
                                            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Supplier</th>
                                            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Amount</th>
                                            <th class="text-center px-5 py-3 text-xs font-semibold text-gray-600 uppercase">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="po in recentActivity.recent_purchase_orders" :key="po.id" class="hover:bg-gray-50">
                                            <td class="px-5 py-3">
                                                <Link
                                                    :href="`/purchase-orders/${po.id}`"
                                                    class="font-medium text-primary hover:text-primary-dark text-sm"
                                                >
                                                    {{ po.po_number }}
                                                </Link>
                                                <div class="text-xs text-gray-500">{{ po.order_date }}</div>
                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-700">
                                                <span class="truncate block max-w-[150px]" :title="po.supplier_name">
                                                    {{ po.supplier_name }}
                                                </span>
                                            </td>
                                            <td class="px-5 py-3 text-sm text-gray-900 text-right font-medium">
                                                {{ formatCurrency(po.grand_total) }}
                                            </td>
                                            <td class="px-5 py-3 text-center">
                                                <div class="flex flex-col items-center gap-1">
                                                    <span
                                                        class="text-xs rounded-lg px-2 py-0.5 border capitalize"
                                                        :class="getOrderStatusClass(po.status)"
                                                    >
                                                        {{ po.status }}
                                                    </span>
                                                    <span
                                                        class="text-xs rounded-lg px-2 py-0.5 border capitalize"
                                                        :class="getPaymentStatusClass(po.payment_status)"
                                                    >
                                                        {{ po.payment_status }}
                                                    </span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="flex flex-col items-center justify-center py-12 text-gray-400">
                                <i class="ki-filled ki-delivery text-4xl mb-3"></i>
                                <p class="text-sm">No recent purchase orders</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access Section -->
                <div class="card">
                    <div class="card-header border-b border-gray-200 px-4 py-3">
                        <h3 class="text-sm font-semibold text-gray-900">Quick Access</h3>
                    </div>
                    <div class="card-body p-3">
                        <div class="grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-8 xl:grid-cols-8 gap-2">
                            <!-- Access Management -->
                            <Link
                                v-if="hasPermission('users.view') || hasPermission('roles.view')"
                                :href="route('access-management.index')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-primary hover:bg-primary/5 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center group-hover:bg-primary/20 transition-colors">
                                    <i class="ki-filled ki-setting-2 text-primary text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Access</span>
                            </Link>

                            <!-- Sales Orders -->
                            <Link
                                v-if="hasPermission('sales-orders.view')"
                                :href="route('sales-orders.list')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-success hover:bg-success/5 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-success/10 flex items-center justify-center group-hover:bg-success/20 transition-colors">
                                    <i class="ki-filled ki-basket text-success text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Sales</span>
                            </Link>

                            <!-- Purchase Orders -->
                            <Link
                                v-if="hasPermission('purchase-orders.view')"
                                :href="route('purchase-orders.list')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-blue-500 hover:bg-blue-50 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                    <i class="ki-filled ki-delivery text-blue-600 text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Purchase</span>
                            </Link>

                            <!-- Customers -->
                            <Link
                                v-if="hasPermission('customers.view')"
                                :href="route('customer.list')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-info hover:bg-info/5 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-info/10 flex items-center justify-center group-hover:bg-info/20 transition-colors">
                                    <i class="ki-filled ki-user text-info text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Customers</span>
                            </Link>

                            <!-- Products -->
                            <Link
                                v-if="hasPermission('products.view')"
                                :href="route('product.list')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-warning hover:bg-warning/5 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-warning/10 flex items-center justify-center group-hover:bg-warning/20 transition-colors">
                                    <i class="ki-filled ki-handcart text-warning text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Products</span>
                            </Link>

                            <!-- Brands -->
                            <Link
                                v-if="hasPermission('brands.view')"
                                :href="route('brand.list')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-danger hover:bg-danger/5 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-danger/10 flex items-center justify-center group-hover:bg-danger/20 transition-colors">
                                    <i class="ki-filled ki-shop text-danger text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Brands</span>
                            </Link>

                            <!-- System Logs -->
                            <Link
                                v-if="hasPermission('logs.view')"
                                :href="route('logs.index')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border border-gray-200 hover:border-gray-400 hover:bg-gray-50 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition-colors">
                                    <i class="ki-filled ki-message-text text-gray-600 text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-700 text-center">Logs</span>
                            </Link>

                            <!-- Create Sales Order -->
                            <Link
                                v-if="hasPermission('sales-orders.create')"
                                :href="route('sales-orders.create')"
                                class="flex flex-col items-center gap-1.5 p-2.5 rounded-lg border-2 border-dashed border-gray-300 hover:border-primary hover:bg-primary/5 transition-colors group"
                            >
                                <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                                    <i class="ki-filled ki-plus-squared text-gray-500 group-hover:text-primary text-xl"></i>
                                </div>
                                <span class="text-[11px] font-medium text-gray-500 group-hover:text-primary text-center">New SO</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
