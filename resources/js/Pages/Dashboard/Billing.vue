<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Receipt, TrendingUp, AlertTriangle, Clock, DollarSign,
    Banknote, CreditCard, Smartphone, RefreshCw, ChevronRight,
    CheckCircle2, BarChart3,
} from 'lucide-vue-next'

const props = defineProps({
    user:            Object,
    role:            String,
    stats:           Object,
    revenueTrend:    Array,
    byMethod:        Object,
    pendingInvoices: Array,
    recentPayments:  Array,
})

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const methodIcon = {
    cash:       Banknote,
    gcash:      Smartphone,
    maya:       Smartphone,
    card:       CreditCard,
    philhealth: Receipt,
    other:      DollarSign,
}
const methodColor = {
    cash:       'text-emerald-600',
    gcash:      'text-blue-600',
    maya:       'text-purple-600',
    card:       'text-slate-600',
    philhealth: 'text-teal-600',
    other:      'text-slate-500',
}
const methodBg = {
    cash:       'bg-emerald-100',
    gcash:      'bg-blue-100',
    maya:       'bg-purple-100',
    card:       'bg-slate-100',
    philhealth: 'bg-teal-100',
    other:      'bg-slate-100',
}

const statusConfig = {
    unpaid:  { label: 'Unpaid',  class: 'bg-red-100 text-red-700'     },
    partial: { label: 'Partial', class: 'bg-amber-100 text-amber-700' },
}

// Bar chart: max value for scaling
const maxRevenue = computed(() =>
    Math.max(...(props.revenueTrend ?? []).map(r => r.total), 1)
)

// Auto-refresh
let timer = null
const lastRefresh = ref(new Date())
function refresh() {
    router.reload({ preserveScroll: true })
    lastRefresh.value = new Date()
}
onMounted(() => { timer = setInterval(refresh, 60000) })
onUnmounted(() => clearInterval(timer))
</script>

<template>
    <AppLayout title="Billing Dashboard">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Billing Dashboard</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Welcome back, {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) }}
                    </p>
                </div>
                <button @click="refresh"
                    class="flex items-center gap-1.5 text-xs text-slate-500 hover:text-blue-600 transition-colors border border-slate-200 rounded-xl px-3 py-1.5 hover:border-blue-300">
                    <RefreshCw class="w-3.5 h-3.5"/>
                    Refresh
                </button>
            </div>
        </template>

        <!-- ── KPI Cards ────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-5">

            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-100 flex items-center justify-center flex-shrink-0">
                    <TrendingUp class="w-5 h-5 text-emerald-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Collected Today</p>
                    <p class="text-2xl font-black text-emerald-700">{{ peso(stats.collected_today) }}</p>
                    <p class="text-xs text-slate-400">{{ stats.paid_today }} invoices paid</p>
                </div>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0">
                    <Receipt class="w-5 h-5 text-blue-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Billed Today</p>
                    <p class="text-2xl font-black text-slate-800">{{ peso(stats.billed_today) }}</p>
                </div>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="router.visit(route('billing.index') + '?status=unpaid')">
                <div class="w-11 h-11 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0">
                    <AlertTriangle class="w-5 h-5 text-red-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Unpaid</p>
                    <p class="text-2xl font-black text-red-600">{{ peso(stats.unpaid_balance) }}</p>
                    <p class="text-xs text-slate-400">{{ stats.unpaid_count }} invoices</p>
                </div>
            </div>

            <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center gap-3 cursor-pointer hover:shadow-md transition-shadow"
                @click="router.visit(route('billing.index') + '?status=partial')">
                <div class="w-11 h-11 rounded-xl bg-amber-100 flex items-center justify-center flex-shrink-0">
                    <Clock class="w-5 h-5 text-amber-600"/>
                </div>
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-widest">Partial</p>
                    <p class="text-2xl font-black text-amber-600">{{ peso(stats.partial_balance) }}</p>
                    <p class="text-xs text-slate-400">{{ stats.partial_count }} invoices</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">

            <!-- ── Revenue Trend (7 days) ─────────── -->
            <div class="bg-card rounded-xl border shadow-sm p-5 lg:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <BarChart3 class="w-4 h-4 text-slate-400"/>
                    <p class="text-sm font-bold text-slate-700">Revenue — Last 7 Days</p>
                </div>
                <div class="flex items-end gap-2 h-28">
                    <template v-if="revenueTrend?.length">
                        <div v-for="r in revenueTrend" :key="r.date"
                            class="flex-1 flex flex-col items-center gap-1">
                            <p class="text-xs font-bold text-slate-600">
                                {{ r.total > 0 ? '₱' + Number(r.total / 1000).toFixed(1) + 'k' : '' }}
                            </p>
                            <div class="w-full rounded-t-lg transition-all"
                                style="background:#1B4F9B; opacity:0.85"
                                :style="{ height: Math.max((r.total / maxRevenue) * 80, r.total > 0 ? 4 : 2) + 'px' }"/>
                            <p class="text-xs text-slate-400">{{ r.date }}</p>
                        </div>
                    </template>
                    <div v-else class="w-full flex items-center justify-center text-slate-300 text-sm">
                        No revenue data
                    </div>
                </div>
            </div>

            <!-- ── By Payment Method ──────────────── -->
            <div class="bg-card rounded-xl border shadow-sm p-5">
                <p class="text-sm font-bold text-slate-700 mb-4">Today by Method</p>
                <div v-if="Object.keys(byMethod ?? {}).length" class="space-y-3">
                    <div v-for="(total, m) in byMethod" :key="m"
                        class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="methodBg[m] ?? 'bg-slate-100'">
                            <component :is="methodIcon[m] ?? DollarSign"
                                class="w-4 h-4" :class="methodColor[m] ?? 'text-slate-500'"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold capitalize text-slate-700">{{ m }}</p>
                            <p class="text-sm font-black" :class="methodColor[m] ?? 'text-slate-700'">
                                {{ peso(total) }}
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-6 text-slate-300 text-sm">
                    No payments today
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

            <!-- ── Pending Invoices ───────────────── -->
            <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 border-b">
                    <p class="text-sm font-bold text-slate-700">Pending Invoices</p>
                    <Link :href="route('billing.index')" class="text-xs text-blue-600 hover:underline">
                        View all
                    </Link>
                </div>
                <div v-if="!pendingInvoices?.length" class="py-10 text-center text-slate-300 text-sm">
                    No pending invoices
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <Link v-for="inv in pendingInvoices" :key="inv.id"
                        :href="route('billing.show', inv.id)"
                        class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors group">
                        <div class="min-w-0">
                            <p class="font-mono font-bold text-blue-700 text-xs">{{ inv.invoice_number }}</p>
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ inv.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ inv.patient_code }}</p>
                        </div>
                        <div class="text-right flex-shrink-0 ml-3">
                            <p class="text-sm font-black text-red-600">{{ peso(inv.balance) }}</p>
                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full', statusConfig[inv.status]?.class]">
                                {{ statusConfig[inv.status]?.label }}
                            </span>
                        </div>
                        <ChevronRight class="w-4 h-4 text-slate-300 ml-2 group-hover:text-blue-500 transition-colors flex-shrink-0"/>
                    </Link>
                </div>
            </div>

            <!-- ── Recent Payments ────────────────── -->
            <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-5 py-3.5 border-b">
                    <p class="text-sm font-bold text-slate-700">Recent Payments Today</p>
                    <CheckCircle2 class="w-4 h-4 text-emerald-500"/>
                </div>
                <div v-if="!recentPayments?.length" class="py-10 text-center text-slate-300 text-sm">
                    No payments today yet
                </div>
                <div v-else class="divide-y divide-slate-100">
                    <div v-for="p in recentPayments" :key="p.id"
                        class="flex items-center gap-3 px-5 py-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                            :class="methodBg[p.method] ?? 'bg-slate-100'">
                            <component :is="methodIcon[p.method] ?? DollarSign"
                                class="w-4 h-4" :class="methodColor[p.method] ?? 'text-slate-500'"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ p.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ p.invoice_number }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-black text-emerald-700">{{ peso(p.amount) }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ p.method }} &bull; {{ p.created_at }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
