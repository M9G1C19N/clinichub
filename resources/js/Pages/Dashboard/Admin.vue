<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    Users, Activity, DollarSign, Clock, FlaskConical,
    TrendingUp, AlertCircle, CheckCircle2, UserPlus,
    BarChart3, Layers, ArrowRight, RefreshCw, Stethoscope,
    TestTube2, Scan, ShieldCheck, ChevronRight
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    visitTrend: Array,
    revenueTrend: Array,
    visitTypeBreakdown: Object,
    pendingInvoices: Array,
    topServices: Array,
    recentActivity: Array,
    roomActivity: Object,
    totalPatients: Number,
    newPatientsToday: Number,
})

const formatPeso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const visitTypeLabels = {
    opd: 'OPD',
    pre_employment: 'Pre-Employment',
    annual_pe: 'Annual PE',
    exit_pe: 'Exit PE',
    follow_up: 'Follow-Up',
    lab_only: 'Lab Only',
}

const visitTypeColors = {
    opd: '#4338CA',
    pre_employment: '#059669',
    annual_pe: '#16A34A',
    exit_pe: '#C2410C',
    follow_up: '#0369A1',
    lab_only: '#7C3AED',
}

const roomLabels = {
    laboratory: 'Laboratory',
    xray_utz: 'X-Ray & UTZ',
    drug_test: 'Drug Test',
    interview_room: 'Interview Room',
}

// Chart: compute bar heights for visit trend
const maxVisits = computed(() => Math.max(...(props.visitTrend?.map(v => v.count) ?? [1])))
const maxRevenue = computed(() => Math.max(...(props.revenueTrend?.map(r => r.total) ?? [1])))

// Auto-refresh every 30s
let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'roomActivity', 'recentActivity', 'pendingInvoices'] })
    }, 30000)
})
onUnmounted(() => clearInterval(timer))

const refresh = () => router.reload({ only: ['stats', 'roomActivity', 'recentActivity'] })

const statusColor = (s) => ({
    unpaid: 'text-red-600', partial: 'text-amber-600', paid: 'text-emerald-600'
})[s] ?? 'text-slate-500'
</script>

<template>
    <AppLayout title="Admin Dashboard">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">Clinic Overview</h1>
                <p class="text-sm text-slate-400 mt-0.5">{{ new Date().toLocaleDateString('en-PH', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) }}</p>
            </div>
            <div class="flex items-center gap-3">
                <button @click="refresh" class="flex items-center gap-2 text-sm text-slate-500 hover:text-[#1B4F9B] transition-colors px-3 py-2 rounded-lg hover:bg-slate-50">
                    <RefreshCw class="w-4 h-4" /> Refresh
                </button>
                <Link :href="route('reception.create')" class="flex items-center gap-2 bg-[#1B4F9B] text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-[#0F2044] transition-colors shadow-sm">
                    <UserPlus class="w-4 h-4" /> New Visit
                </Link>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard
                label="Today's Visits"
                :value="stats.today_visits"
                :icon="Users"
                color="#1B4F9B"
                :trend="stats.visits_trend"
                :sub="`${newPatientsToday} new patients registered`"
            />
            <StatCard
                label="Today's Revenue"
                :value="formatPeso(stats.today_revenue)"
                :icon="DollarSign"
                color="#059669"
                :trend="stats.revenue_trend"
                sub="Total payments received"
            />
            <StatCard
                label="Pending Payment"
                :value="stats.pending_payment"
                :icon="AlertCircle"
                color="#D97706"
                :warning="stats.pending_payment > 0"
                sub="Invoices unpaid / partial"
            />
            <StatCard
                label="Active in Queue"
                :value="stats.active_queue"
                :icon="Activity"
                color="#0EA5E9"
                :sub="`${totalPatients} total patients registered`"
            />
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-3 gap-5">
            <!-- Left: Charts + Room Activity (2 cols) -->
            <div class="col-span-3 lg:col-span-2 space-y-5">

                <!-- Visit Trend Bar Chart -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-[#0F2044]">Visit Trend — Last 7 Days</h3>
                            <p class="text-xs text-slate-400">Daily patient registrations</p>
                        </div>
                        <BarChart3 class="w-4 h-4 text-slate-300" />
                    </div>
                    <div class="flex items-end gap-2 h-32">
                        <div
                            v-for="d in visitTrend" :key="d.date"
                            class="flex-1 flex flex-col items-center gap-1 group"
                        >
                            <span class="text-xs font-bold text-[#1B4F9B] opacity-0 group-hover:opacity-100 transition-opacity">{{ d.count }}</span>
                            <div
                                class="w-full rounded-t-lg bg-[#1B4F9B] transition-all duration-500 min-h-1"
                                :style="{ height: `${Math.max(4, (d.count / maxVisits) * 112)}px` }"
                            />
                            <span class="text-xs text-slate-400">{{ d.date }}</span>
                        </div>
                        <div v-if="!visitTrend?.length" class="flex-1 flex items-center justify-center text-slate-300 text-sm">
                            No data yet
                        </div>
                    </div>
                </div>

                <!-- Revenue Trend -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="font-bold text-[#0F2044]">Revenue Trend — Last 6 Months</h3>
                            <p class="text-xs text-slate-400">Monthly payment collections</p>
                        </div>
                        <TrendingUp class="w-4 h-4 text-slate-300" />
                    </div>
                    <div class="flex items-end gap-2 h-28">
                        <div
                            v-for="r in revenueTrend" :key="r.month"
                            class="flex-1 flex flex-col items-center gap-1 group"
                        >
                            <span class="text-xs font-bold text-emerald-600 opacity-0 group-hover:opacity-100 transition-opacity">{{ formatPeso(r.total) }}</span>
                            <div
                                class="w-full rounded-t-lg bg-emerald-500 transition-all duration-500 min-h-1"
                                :style="{ height: `${Math.max(4, (r.total / maxRevenue) * 96)}px` }"
                            />
                            <span class="text-xs text-slate-400">{{ r.month }}</span>
                        </div>
                        <div v-if="!revenueTrend?.length" class="flex-1 flex items-center justify-center text-slate-300 text-sm">
                            No data yet
                        </div>
                    </div>
                </div>

                <!-- Room Activity -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Room Activity Today</h3>
                    <div class="grid grid-cols-2 gap-3">
                        <div
                            v-for="(data, room) in roomActivity" :key="room"
                            class="bg-slate-50 rounded-xl p-4"
                        >
                            <div class="flex items-center gap-2 mb-3">
                                <div class="w-2 h-2 rounded-full bg-[#1B4F9B]" />
                                <p class="text-sm font-semibold text-slate-700">{{ roomLabels[room] }}</p>
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center">
                                <div>
                                    <p class="text-lg font-black text-amber-600">{{ data.waiting }}</p>
                                    <p class="text-xs text-slate-400">Waiting</p>
                                </div>
                                <div>
                                    <p class="text-lg font-black text-emerald-600">{{ data.serving }}</p>
                                    <p class="text-xs text-slate-400">Serving</p>
                                </div>
                                <div>
                                    <p class="text-lg font-black text-slate-500">{{ data.completed }}</p>
                                    <p class="text-xs text-slate-400">Done</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Invoices Table -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-[#0F2044]">Pending Payments</h3>
                        <Link :href="route('reception.index')" class="text-xs text-[#1B4F9B] font-semibold hover:underline flex items-center gap-1">
                            View All <ChevronRight class="w-3 h-3" />
                        </Link>
                    </div>
                    <div v-if="pendingInvoices?.length" class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100">
                                    <th class="text-left py-2 pr-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Patient</th>
                                    <th class="text-left py-2 pr-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Invoice</th>
                                    <th class="text-right py-2 pr-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Total</th>
                                    <th class="text-right py-2 pr-4 text-xs font-semibold text-slate-400 uppercase tracking-wider">Balance</th>
                                    <th class="text-center py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="inv in pendingInvoices" :key="inv.id" class="border-b border-slate-50 hover:bg-slate-50 transition-colors">
                                    <td class="py-2.5 pr-4">
                                        <p class="font-semibold text-[#0F2044]">{{ inv.patient_name }}</p>
                                        <p class="text-xs text-slate-400">{{ inv.visit_date }}</p>
                                    </td>
                                    <td class="py-2.5 pr-4 text-slate-500 font-mono text-xs">{{ inv.invoice_number }}</td>
                                    <td class="py-2.5 pr-4 text-right font-semibold text-slate-700">{{ formatPeso(inv.total) }}</td>
                                    <td class="py-2.5 pr-4 text-right font-bold" :class="statusColor(inv.status)">{{ formatPeso(inv.balance) }}</td>
                                    <td class="py-2.5 text-center">
                                        <QueueBadge :status="inv.status" size="xs" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="flex items-center justify-center py-8 text-slate-300">
                        <CheckCircle2 class="w-8 h-8 mr-2" />
                        <span class="text-sm">All invoices paid!</span>
                    </div>
                </div>
            </div>

            <!-- Right: Summary Panels (1 col) -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- Visit type breakdown -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Today's Visit Types</h3>
                    <div class="space-y-3">
                        <div
                            v-for="(count, type) in visitTypeBreakdown" :key="type"
                            class="flex items-center gap-3"
                        >
                            <div class="w-3 h-3 rounded-full flex-shrink-0" :style="{ background: visitTypeColors[type] ?? '#94A3B8' }" />
                            <span class="flex-1 text-sm text-slate-600">{{ visitTypeLabels[type] ?? type }}</span>
                            <span class="font-black text-[#0F2044]">{{ count }}</span>
                        </div>
                        <div v-if="!Object.keys(visitTypeBreakdown ?? {}).length" class="text-sm text-slate-400 text-center py-4">
                            No visits today yet
                        </div>
                    </div>
                </div>

                <!-- Top Services -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Top Services Today</h3>
                    <div class="space-y-3">
                        <div v-for="(svc, i) in topServices" :key="i" class="flex items-center gap-3">
                            <span class="w-5 h-5 rounded-full bg-[#D6E8F7] text-[#1B4F9B] text-xs font-black flex items-center justify-center flex-shrink-0">
                                {{ i + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ svc.service_name }}</p>
                                <p class="text-xs text-slate-400">{{ formatPeso(svc.revenue) }}</p>
                            </div>
                            <span class="font-black text-[#0F2044]">×{{ svc.count }}</span>
                        </div>
                        <div v-if="!topServices?.length" class="text-sm text-slate-400 text-center py-4">
                            No services billed yet
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-4">Recent Staff Activity</h3>
                    <div class="space-y-3">
                        <div v-for="(act, i) in recentActivity" :key="i" class="flex items-start gap-3">
                            <div class="w-2 h-2 rounded-full bg-emerald-400 mt-1.5 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-700">
                                    <span class="font-semibold">{{ act.staff }}</span>
                                    <span class="text-slate-400"> — {{ act.action }}</span>
                                </p>
                                <p class="text-xs text-slate-400">{{ act.patient }} · {{ act.time }}</p>
                            </div>
                        </div>
                        <div v-if="!recentActivity?.length" class="text-sm text-slate-400 text-center py-4">
                            No activity logged yet
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-[#0F2044] rounded-2xl p-5 text-white">
                    <h3 class="font-bold mb-4 text-white">Quick Actions</h3>
                    <div class="space-y-2">
                        <Link :href="route('reception.create')" class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <span class="flex items-center gap-2"><UserPlus class="w-4 h-4" /> New Patient Visit</span>
                            <ArrowRight class="w-3.5 h-3.5 opacity-50" />
                        </Link>
                        <Link :href="route('admin.users.index')" class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <span class="flex items-center gap-2"><Users class="w-4 h-4" /> Manage Staff</span>
                            <ArrowRight class="w-3.5 h-3.5 opacity-50" />
                        </Link>
                        <Link :href="route('admin.services.index')" class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <span class="flex items-center gap-2"><Layers class="w-4 h-4" /> Service Catalog</span>
                            <ArrowRight class="w-3.5 h-3.5 opacity-50" />
                        </Link>
                        <Link :href="route('queue.index')" class="flex items-center justify-between py-2.5 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <span class="flex items-center gap-2"><Activity class="w-4 h-4" /> Queue Monitor</span>
                            <ArrowRight class="w-3.5 h-3.5 opacity-50" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
