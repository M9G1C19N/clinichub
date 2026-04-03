<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Receipt, TrendingUp, AlertTriangle, Clock, DollarSign,
    Banknote, CreditCard, Smartphone, RefreshCw, ChevronRight,
    CheckCircle2, BarChart3, Activity, Wifi,
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
    cash:       '#059669', gcash:'#2563EB', maya:'#7C3AED',
    card:       '#475569', philhealth:'#0D9488', other:'#94A3B8',
}
const methodBg = {
    cash:       '#D1FAE5', gcash:'#DBEAFE', maya:'#EDE9FE',
    card:       '#F1F5F9', philhealth:'#CCFBF1', other:'#F1F5F9',
}
const statusConfig = {
    unpaid:  { label: 'Unpaid',  bg: '#FEE2E2', text: '#DC2626' },
    partial: { label: 'Partial', bg: '#FEF3C7', text: '#D97706' },
}

const maxRevenue = computed(() => Math.max(...(props.revenueTrend ?? []).map(r => r.total), 1))
const collectionRate = computed(() => {
    const billed = props.stats?.billed_today ?? 0
    const collected = props.stats?.collected_today ?? 0
    return billed > 0 ? Math.round((collected / billed) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let timer = null
onMounted(() => { timer = setInterval(refresh, 60000) })
onUnmounted(() => clearInterval(timer))
async function refresh() {
    refreshing.value = true
    await router.reload({ preserveScroll: true })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Billing Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Billing Dashboard</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live — 1min
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-slate-50 rounded-lg transition-colors" title="Refresh">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <Link :href="route('billing.index')" class="flex items-center gap-2 text-sm font-semibold border border-[#1B4F9B] text-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                <Receipt class="w-4 h-4"/> All Invoices
            </Link>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Collected Today</span>
                <TrendingUp class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-2xl font-black">{{ peso(stats.collected_today) }}</div>
            <div class="text-xs opacity-70 mt-1">{{ stats.paid_today }} invoices paid</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Billed Today</span>
                <Receipt class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-2xl font-black">{{ peso(stats.billed_today) }}</div>
            <div class="text-xs opacity-70 mt-1">{{ collectionRate }}% collection rate</div>
        </div>

        <div class="rounded-2xl p-5 cursor-pointer text-white transition-opacity hover:opacity-90"
            :style="(stats.unpaid_count ?? 0) > 0 ? 'background:linear-gradient(135deg,#DC2626,#ef4444)' : 'background:linear-gradient(135deg,#94A3B8,#cbd5e1)'"
            @click="router.visit(route('billing.index') + '?status=unpaid')">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Unpaid Balance</span>
                <AlertTriangle class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-2xl font-black">{{ peso(stats.unpaid_balance) }}</div>
            <div class="text-xs opacity-70 mt-1">{{ stats.unpaid_count }} unpaid invoices</div>
        </div>

        <div class="rounded-2xl p-5 cursor-pointer text-white transition-opacity hover:opacity-90"
            :style="(stats.partial_count ?? 0) > 0 ? 'background:linear-gradient(135deg,#D97706,#F59E0B)' : 'background:linear-gradient(135deg,#94A3B8,#cbd5e1)'"
            @click="router.visit(route('billing.index') + '?status=partial')">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Partial Balance</span>
                <Clock class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-2xl font-black">{{ peso(stats.partial_balance) }}</div>
            <div class="text-xs opacity-70 mt-1">{{ stats.partial_count }} partial invoices</div>
        </div>
    </div>

    <!-- Collection rate progress -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <Activity class="w-4 h-4 text-emerald-500"/>
                <span class="text-sm font-bold text-slate-700">Collection Rate Today</span>
            </div>
            <span class="text-sm font-black text-emerald-600">{{ collectionRate }}% collected</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                :class="collectionRate >= 80 ? 'bg-emerald-500' : collectionRate >= 50 ? 'bg-blue-500' : 'bg-amber-500'"
                :style="{ width: collectionRate + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>Collected: {{ peso(stats.collected_today) }}</span>
            <span>Billed: {{ peso(stats.billed_today) }}</span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        <!-- Revenue Trend (7 days) -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 lg:col-span-2">
            <div class="flex items-center gap-2 mb-4">
                <BarChart3 class="w-4 h-4 text-slate-400"/>
                <p class="text-sm font-bold text-slate-700">Revenue — Last 7 Days</p>
            </div>
            <div class="flex items-end gap-2 h-28">
                <template v-if="revenueTrend?.length">
                    <div v-for="r in revenueTrend" :key="r.date" class="flex-1 flex flex-col items-center gap-1">
                        <p class="text-xs font-bold text-slate-600">
                            {{ r.total > 0 ? '₱' + Number(r.total / 1000).toFixed(1) + 'k' : '' }}
                        </p>
                        <div class="w-full rounded-t-lg transition-all"
                            style="background:linear-gradient(to top,#0F2044,#1B4F9B)"
                            :style="{ height: Math.max((r.total / maxRevenue) * 88, r.total > 0 ? 4 : 2) + 'px' }"/>
                        <p class="text-xs text-slate-400">{{ r.date }}</p>
                    </div>
                </template>
                <div v-else class="w-full flex items-center justify-center text-slate-300 text-sm">
                    No revenue data yet
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
            <p class="text-sm font-bold text-slate-700 mb-4">Today by Method</p>
            <div v-if="Object.keys(byMethod ?? {}).length" class="space-y-3">
                <div v-for="(total, m) in byMethod" :key="m" class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                        :style="{ background: methodBg[m] ?? '#F1F5F9' }">
                        <component :is="methodIcon[m] ?? DollarSign" class="w-4 h-4"
                            :style="{ color: methodColor[m] ?? '#94A3B8' }"/>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold capitalize text-slate-700">{{ m }}</p>
                        <p class="text-sm font-black" :style="{ color: methodColor[m] ?? '#0F2044' }">{{ peso(total) }}</p>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-6 text-slate-300 text-sm">No payments today</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        <!-- Pending Invoices -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-red-500"/>
                    <p class="text-sm font-bold text-slate-700">Pending Invoices</p>
                </div>
                <Link :href="route('billing.index')" class="text-xs text-blue-600 hover:underline font-semibold">
                    View all
                </Link>
            </div>
            <div v-if="!pendingInvoices?.length" class="py-12 text-center text-slate-300 text-sm">
                No pending invoices — great!
            </div>
            <div v-else class="divide-y divide-slate-100">
                <Link v-for="inv in pendingInvoices" :key="inv.id" :href="route('billing.show', inv.id)"
                    class="flex items-center justify-between px-5 py-3 hover:bg-slate-50 transition-colors group">
                    <div class="min-w-0">
                        <p class="font-mono font-bold text-blue-700 text-xs">{{ inv.invoice_number }}</p>
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ inv.patient_name }}</p>
                        <p class="text-xs text-slate-400 font-mono">{{ inv.patient_code }}</p>
                    </div>
                    <div class="text-right flex-shrink-0 ml-3">
                        <p class="text-sm font-black text-red-600">{{ peso(inv.balance) }}</p>
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                            :style="{ background: statusConfig[inv.status]?.bg, color: statusConfig[inv.status]?.text }">
                            {{ statusConfig[inv.status]?.label }}
                        </span>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-300 ml-2 group-hover:text-blue-500 transition-colors flex-shrink-0"/>
                </Link>
            </div>
        </div>

        <!-- Recent Payments -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-5 py-3.5 border-b">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"/>
                    <p class="text-sm font-bold text-slate-700">Recent Payments Today</p>
                </div>
                <CheckCircle2 class="w-4 h-4 text-emerald-500"/>
            </div>
            <div v-if="!recentPayments?.length" class="py-12 text-center text-slate-300 text-sm">
                No payments recorded today yet
            </div>
            <div v-else class="divide-y divide-slate-100">
                <div v-for="p in recentPayments" :key="p.id" class="flex items-center gap-3 px-5 py-3">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                        :style="{ background: methodBg[p.method] ?? '#F1F5F9' }">
                        <component :is="methodIcon[p.method] ?? DollarSign" class="w-4 h-4"
                            :style="{ color: methodColor[p.method] ?? '#94A3B8' }"/>
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
</div>
</AppLayout>
</template>
