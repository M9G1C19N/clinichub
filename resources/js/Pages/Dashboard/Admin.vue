<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Users, Activity, DollarSign, Clock, FlaskConical, TrendingUp,
    TrendingDown, AlertCircle, CheckCircle2, UserPlus, BarChart3,
    ArrowRight, RefreshCw, Stethoscope, Scan, ShieldCheck,
    CalendarDays, Pill, Receipt, Building2, HeartPulse,
} from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String, stats: Object,
    visitTrend: Array, revenueTrend: Array, visitTypeBreakdown: Object,
    pendingInvoices: Array, topServices: Array, recentActivity: Array,
    roomActivity: Object, recentAppointments: Array,
    totalPatients: Number, newPatientsToday: Number,
})

const peso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const visitTypeLabel = { opd:'OPD', pre_employment:'Pre-Employment', annual_pe:'Annual PE', exit_pe:'Exit PE', follow_up:'Follow-Up', lab_only:'Lab Only' }
const visitTypeColor = { opd:'#4338CA', pre_employment:'#059669', annual_pe:'#16A34A', exit_pe:'#C2410C', follow_up:'#0369A1', lab_only:'#7C3AED' }
const roomLabel = { laboratory:'Laboratory', xray_utz:'X-Ray & UTZ', drug_test:'Drug Test', interview_room:'Interview Room' }
const roomIcon  = { laboratory: FlaskConical, xray_utz: Scan, drug_test: ShieldCheck, interview_room: HeartPulse }
const statusClass = { unpaid:'bg-red-100 text-red-700', partial:'bg-amber-100 text-amber-700' }

const maxVisit   = computed(() => Math.max(...(props.visitTrend  ?? []).map(v => v.count), 1))
const maxRevenue = computed(() => Math.max(...(props.revenueTrend ?? []).map(r => r.total), 1))
const maxService = computed(() => Math.max(...(props.topServices  ?? []).map(s => s.count), 1))
const visitTypeTotal = computed(() => Object.values(props.visitTypeBreakdown ?? {}).reduce((s, v) => s + Number(v), 0))

let timer = null
const refreshing = ref(false)
const lastRefresh = ref(new Date())
onMounted(() => { timer = setInterval(refresh, 30000) })
onUnmounted(() => clearInterval(timer))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats','roomActivity','recentAppointments'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Admin Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Admin Dashboard</h1>
            <p class="text-sm text-slate-400 mt-0.5">Welcome back, {{ user.name }} · {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}</p>
        </div>
        <button @click="refresh" class="flex items-center gap-2 text-xs text-slate-500 border border-slate-200 rounded-xl px-3 py-1.5 hover:border-slate-300 transition-colors">
            <RefreshCw class="w-3.5 h-3.5" :class="refreshing && 'animate-spin'"/>
            {{ lastRefresh.toLocaleTimeString('en-PH',{hour:'2-digit',minute:'2-digit'}) }}
        </button>
    </div>

    <!-- KPI Row 1 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Total Patients</span>
                <Users class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ totalPatients?.toLocaleString() }}</div>
            <div class="text-xs opacity-70 mt-1 flex items-center gap-1">
                <UserPlus class="w-3 h-3"/> +{{ newPatientsToday }} new today
            </div>
        </div>
        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Today's Visits</span>
                <Activity class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.today_visits }}</div>
            <div class="text-xs opacity-70 mt-1 flex items-center gap-1">
                <component :is="stats.visits_trend >= 0 ? TrendingUp : TrendingDown" class="w-3 h-3"/>
                {{ Math.abs(stats.visits_trend) }}% vs yesterday
            </div>
        </div>
        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#7C3AED,#A855F7)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Today's Revenue</span>
                <DollarSign class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-2xl font-black">{{ peso(stats.today_revenue) }}</div>
            <div class="text-xs opacity-70 mt-1 flex items-center gap-1">
                <component :is="stats.revenue_trend >= 0 ? TrendingUp : TrendingDown" class="w-3 h-3"/>
                {{ Math.abs(stats.revenue_trend) }}% vs yesterday
            </div>
        </div>
        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#DC2626,#EF4444)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Pending Payment</span>
                <Receipt class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.pending_payment }}</div>
            <div class="text-xs opacity-70 mt-1">unpaid / partial invoices</div>
        </div>
    </div>

    <!-- KPI Row 2: New Modules -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <Link :href="route('appointments.index')" class="bg-white rounded-2xl border border-slate-200 p-4 hover:border-blue-300 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-2">
                <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center">
                    <CalendarDays class="w-5 h-5 text-amber-600"/>
                </div>
                <span class="text-xs text-amber-600 font-bold bg-amber-50 px-2 py-0.5 rounded-lg">{{ stats.appointments_pending }} pending</span>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ stats.appointments_today }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Appointments Today</div>
            <div class="text-xs text-blue-600 mt-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                Manage <ArrowRight class="w-3 h-3"/>
            </div>
        </Link>
        <Link :href="route('prescriptions.index')" class="bg-white rounded-2xl border border-slate-200 p-4 hover:border-purple-300 hover:shadow-md transition-all group">
            <div class="flex items-center justify-between mb-2">
                <div class="w-9 h-9 rounded-xl bg-purple-100 flex items-center justify-center">
                    <Pill class="w-5 h-5 text-purple-600"/>
                </div>
                <span class="text-xs text-purple-600 font-bold bg-purple-50 px-2 py-0.5 rounded-lg">today</span>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ stats.prescriptions_today }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Prescriptions Written</div>
            <div class="text-xs text-purple-600 mt-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                View All <ArrowRight class="w-3 h-3"/>
            </div>
        </Link>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-9 h-9 rounded-xl bg-blue-100 flex items-center justify-center">
                    <Clock class="w-5 h-5 text-blue-600"/>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ stats.active_queue }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Active Queue Now</div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <div class="flex items-center justify-between mb-2">
                <div class="w-9 h-9 rounded-xl bg-teal-100 flex items-center justify-center">
                    <BarChart3 class="w-5 h-5 text-teal-600"/>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-800">{{ stats.appointments_week }}</div>
            <div class="text-xs text-slate-400 mt-0.5">Appointments This Week</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Visit Trend -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <Activity class="w-4 h-4 text-blue-600"/> Visits — Last 7 Days
            </h3>
            <div class="flex items-end gap-2 h-28">
                <div v-for="d in visitTrend" :key="d.date" class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs font-bold text-slate-700">{{ d.count }}</span>
                    <div class="w-full rounded-t-md transition-all" style="background:#1B4F9B"
                        :style="{ height: ((d.count / maxVisit) * 80) + 'px', minHeight: '4px' }"></div>
                    <span class="text-xs text-slate-400 whitespace-nowrap">{{ d.date }}</span>
                </div>
                <div v-if="!visitTrend?.length" class="w-full text-center text-sm text-slate-400 py-8">No data</div>
            </div>
        </div>
        <!-- Revenue Trend -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <DollarSign class="w-4 h-4 text-emerald-600"/> Revenue — Last 6 Months
            </h3>
            <div class="flex items-end gap-2 h-28">
                <div v-for="r in revenueTrend" :key="r.month" class="flex-1 flex flex-col items-center gap-1">
                    <span class="text-xs font-bold text-slate-700 whitespace-nowrap" style="font-size:10px">
                        {{ peso(r.total).replace('₱','') }}
                    </span>
                    <div class="w-full rounded-t-md" style="background:#059669"
                        :style="{ height: ((r.total / maxRevenue) * 80) + 'px', minHeight: '4px' }"></div>
                    <span class="text-xs text-slate-400">{{ r.month }}</span>
                </div>
                <div v-if="!revenueTrend?.length" class="w-full text-center text-sm text-slate-400 py-8">No data</div>
            </div>
        </div>
    </div>

    <!-- Room Activity + Visit Type -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Room Activity -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <Building2 class="w-4 h-4 text-slate-500"/> Room Activity — Live
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <div v-for="(data, room) in roomActivity" :key="room"
                    class="rounded-xl border border-slate-100 p-3 bg-slate-50">
                    <div class="flex items-center gap-2 mb-2">
                        <component :is="roomIcon[room]" class="w-4 h-4 text-slate-500"/>
                        <span class="text-xs font-bold text-slate-700">{{ roomLabel[room] }}</span>
                    </div>
                    <div class="flex gap-3 text-center">
                        <div class="flex-1">
                            <div class="text-lg font-black text-amber-600">{{ data.waiting }}</div>
                            <div class="text-xs text-slate-400">Waiting</div>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-black text-blue-600">{{ data.serving }}</div>
                            <div class="text-xs text-slate-400">Serving</div>
                        </div>
                        <div class="flex-1">
                            <div class="text-lg font-black text-emerald-600">{{ data.completed }}</div>
                            <div class="text-xs text-slate-400">Done</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visit Type Breakdown -->
        <div class="bg-white rounded-2xl border border-slate-200 p-5">
            <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                <BarChart3 class="w-4 h-4 text-slate-500"/> Visit Types Today
            </h3>
            <div class="space-y-2.5">
                <div v-for="(count, type) in visitTypeBreakdown" :key="type">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-semibold text-slate-700">{{ visitTypeLabel[type] ?? type }}</span>
                        <span class="font-bold">{{ count }}</span>
                    </div>
                    <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all"
                            :style="{ width: visitTypeTotal > 0 ? ((count/visitTypeTotal)*100)+'%' : '0%', background: visitTypeColor[type] ?? '#64748B' }"></div>
                    </div>
                </div>
                <div v-if="!Object.keys(visitTypeBreakdown ?? {}).length" class="text-sm text-slate-400 text-center py-4">No visits today</div>
            </div>
        </div>
    </div>

    <!-- Pending Invoices + Appointments + Top Services -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Pending Invoices -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <AlertCircle class="w-4 h-4 text-red-500"/> Unpaid Invoices
                </h3>
                <Link :href="route('billing.index')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                    View All <ArrowRight class="w-3 h-3"/>
                </Link>
            </div>
            <div class="divide-y divide-slate-100">
                <div v-for="inv in pendingInvoices?.slice(0,6)" :key="inv.id"
                    class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 transition-colors">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ inv.patient_name }}</p>
                        <p class="text-xs text-slate-400 font-mono">{{ inv.invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-800">{{ peso(inv.balance) }}</p>
                        <span class="text-xs px-1.5 py-0.5 rounded-md font-semibold" :class="statusClass[inv.status]">{{ inv.status }}</span>
                    </div>
                    <Link :href="route('billing.show', inv.id)" class="text-slate-300 hover:text-blue-500">
                        <ArrowRight class="w-4 h-4"/>
                    </Link>
                </div>
                <div v-if="!pendingInvoices?.length" class="px-5 py-6 text-center text-sm text-slate-400">
                    <CheckCircle2 class="w-8 h-8 text-emerald-400 mx-auto mb-2"/> All invoices settled!
                </div>
            </div>
        </div>

        <!-- Pending Appointments -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
            <div class="px-5 py-4 border-b flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <CalendarDays class="w-4 h-4 text-amber-500"/> Appointments
                    <span v-if="stats.appointments_pending > 0" class="text-xs bg-amber-500 text-white rounded-full px-1.5 py-0.5 font-bold">{{ stats.appointments_pending }}</span>
                </h3>
                <Link :href="route('appointments.index')" class="text-xs text-blue-600 hover:underline flex items-center gap-1">
                    Manage <ArrowRight class="w-3 h-3"/>
                </Link>
            </div>
            <div class="divide-y divide-slate-100">
                <div v-for="a in recentAppointments" :key="a.id"
                    class="px-5 py-3 hover:bg-slate-50 transition-colors">
                    <p class="text-sm font-semibold text-slate-800 truncate">{{ a.patient_name }}</p>
                    <p class="text-xs text-slate-500">{{ a.service_label }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-xs text-slate-400">{{ a.preferred_date }}</span>
                        <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded font-semibold">Pending</span>
                    </div>
                </div>
                <div v-if="!recentAppointments?.length" class="px-5 py-6 text-center text-sm text-slate-400">
                    No pending appointments
                </div>
            </div>
        </div>
    </div>

    <!-- Top Services -->
    <div class="bg-white rounded-2xl border border-slate-200 p-5">
        <h3 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
            <BarChart3 class="w-4 h-4 text-slate-500"/> Top Services Today
        </h3>
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
            <div v-for="svc in topServices" :key="svc.service_name" class="text-center">
                <div class="text-2xl font-black text-[#1B4F9B]">{{ svc.count }}</div>
                <div class="text-xs text-slate-600 font-semibold truncate">{{ svc.service_name }}</div>
                <div class="text-xs text-slate-400">{{ peso(svc.revenue) }}</div>
            </div>
            <div v-if="!topServices?.length" class="col-span-5 text-center text-sm text-slate-400 py-4">No services yet today</div>
        </div>
    </div>

</div>
</AppLayout>
</template>
