<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Users, Receipt, Activity, Clock, CheckCircle2,
    AlertCircle, FlaskConical, ScanLine, ShieldCheck, Stethoscope,
    RefreshCw, ChevronRight, Calendar, Wifi,
    UserPlus, Search, HeartPulse, DollarSign, TrendingUp,
} from 'lucide-vue-next'

const props = defineProps({
    user: Object, role: String,
    stats: Object,
    todayVisits: Array,
    queueStats: Object,
    fieldVisitsPending: Number,
    recentPayments: Array,
    pendingAppointments: Array,
})

const fmt = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const search = ref('')

const filtered = computed(() => {
    const q = search.value.toLowerCase()
    if (!q) return props.todayVisits ?? []
    return (props.todayVisits ?? []).filter(v =>
        v.patient_name.toLowerCase().includes(q) ||
        (v.patient_code ?? '').toLowerCase().includes(q) ||
        (v.case_number ?? '').toLowerCase().includes(q)
    )
})

const visitBadge = {
    opd:            { bg: '#EFF6FF', text: '#1D4ED8', label: 'OPD' },
    pre_employment: { bg: '#FAF5FF', text: '#7C3AED', label: 'Pre-Emp' },
    annual_pe:      { bg: '#F0FDF4', text: '#15803D', label: 'Annual PE' },
    exit_pe:        { bg: '#FFF7ED', text: '#C2410C', label: 'Exit PE' },
    follow_up:      { bg: '#F0F9FF', text: '#0369A1', label: 'Follow-up' },
    lab_only:       { bg: '#FDF4FF', text: '#9333EA', label: 'Lab Only' },
}

const statusDot = {
    none:        '#cbd5e1',
    pending:     '#f59e0b',
    processing:  '#3b82f6',
    released:    '#22c55e',
    in_progress: '#f59e0b',
    finalized:   '#22c55e',
    paid:        '#22c55e',
    unpaid:      '#ef4444',
    partial:     '#f59e0b',
}

const roomConfig = {
    laboratory:     { label: 'Laboratory',      color: '#3b82f6', bg: '#EFF6FF', icon: FlaskConical },
    xray_utz:       { label: 'X-Ray & UTZ',     color: '#8b5cf6', bg: '#F5F3FF', icon: ScanLine },
    drug_test:      { label: 'Drug Test',        color: '#f43f5e', bg: '#FFF1F2', icon: ShieldCheck },
    interview_room: { label: 'Interview Room',   color: '#10b981', bg: '#F0FDF4', icon: HeartPulse },
}

const totalQueue = computed(() => Object.values(props.queueStats ?? {}).reduce((s, v) => s + v, 0))
const paidPct    = computed(() => {
    const total = props.stats?.today_visits ?? 0
    return total > 0 ? Math.round(((props.stats?.completed ?? 0) / total) * 100) : 0
})

const refreshing = ref(false)
const lastRefresh = ref(new Date())
let timer = null
onMounted(() => { timer = setInterval(refresh, 30000) })
onUnmounted(() => clearInterval(timer))
async function refresh() {
    refreshing.value = true
    await router.reload({ only: ['stats','todayVisits','queueStats','recentPayments','pendingAppointments'] })
    lastRefresh.value = new Date()
    refreshing.value = false
}
</script>

<template>
<AppLayout title="Reception Dashboard">
<div class="space-y-5">

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#0F2044]">Reception</h1>
            <p class="text-sm text-slate-400 mt-0.5">
                {{ user.name }} &mdash; {{ new Date().toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'}) }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <span class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full font-semibold">
                <Wifi class="w-3 h-3"/> Live — 30s
            </span>
            <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                <RefreshCw class="w-4 h-4" :class="refreshing && 'animate-spin'"/>
            </button>
            <a :href="route('reception.index')"
                class="flex items-center gap-2 text-sm font-semibold border border-[#1B4F9B] text-[#1B4F9B] px-3 py-2 rounded-xl hover:bg-[#1B4F9B] hover:text-white transition-colors">
                <UserPlus class="w-4 h-4"/> New Visit
            </a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Today's Visits</span>
                <Users class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.today_visits }}</div>
            <div class="text-xs opacity-70 mt-1">{{ stats.active_queue }} active in queue</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.pending_payment ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.pending_payment ?? 0) > 0 ? 'background:linear-gradient(135deg,#DC2626,#ef4444)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.pending_payment ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Pending Payment</span>
                <AlertCircle class="w-5 h-5" :class="(stats.pending_payment ?? 0) > 0 ? 'opacity-60' : 'text-red-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.pending_payment ?? 0) > 0 ? '' : 'text-red-600'">{{ stats.pending_payment }}</div>
            <div class="text-xs mt-1" :class="(stats.pending_payment ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">{{ fmt(stats.pending_balance) }} balance</div>
        </div>

        <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#059669,#10B981)">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-70">Fully Paid</span>
                <CheckCircle2 class="w-5 h-5 opacity-60"/>
            </div>
            <div class="text-3xl font-black">{{ stats.completed }}</div>
            <div class="text-xs opacity-70 mt-1">{{ paidPct }}% collection rate</div>
        </div>

        <div class="rounded-2xl p-5" :class="(stats.appointments_pending ?? 0) > 0 ? 'text-white' : 'bg-white border border-slate-100'"
            :style="(stats.appointments_pending ?? 0) > 0 ? 'background:linear-gradient(135deg,#C2410C,#f97316)' : ''">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider" :class="(stats.appointments_pending ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Appointments</span>
                <Calendar class="w-5 h-5" :class="(stats.appointments_pending ?? 0) > 0 ? 'opacity-60' : 'text-orange-500'"/>
            </div>
            <div class="text-3xl font-black" :class="(stats.appointments_pending ?? 0) > 0 ? '' : 'text-orange-600'">{{ stats.appointments_pending }}</div>
            <div class="text-xs mt-1" :class="(stats.appointments_pending ?? 0) > 0 ? 'opacity-70' : 'text-slate-400'">Today: {{ stats.appointments_today }}</div>
        </div>
    </div>

    <!-- Payment collection progress -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <TrendingUp class="w-4 h-4 text-emerald-500"/>
                <span class="text-sm font-bold text-slate-700">Payment Collection Progress</span>
            </div>
            <span class="text-sm font-black text-emerald-600">{{ stats.completed }} / {{ stats.today_visits }} visits paid</span>
        </div>
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full transition-all duration-500"
                :class="paidPct >= 80 ? 'bg-emerald-500' : paidPct >= 50 ? 'bg-blue-500' : 'bg-amber-500'"
                :style="{ width: paidPct + '%' }"/>
        </div>
        <div class="flex justify-between text-xs text-slate-400 mt-1">
            <span>{{ paidPct }}% collection rate</span>
            <span v-if="(stats.pending_payment ?? 0) > 0" class="text-red-500 font-semibold">{{ fmt(stats.pending_balance) }} outstanding</span>
        </div>
    </div>

    <!-- Queue Room Status -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div v-for="(count, room) in queueStats" :key="room"
            class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                :style="{ background: roomConfig[room]?.bg ?? '#F1F5F9' }">
                <component :is="roomConfig[room]?.icon ?? Activity" class="w-5 h-5"
                    :style="{ color: roomConfig[room]?.color ?? '#64748b' }"/>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xl font-black text-slate-800">{{ count }}</p>
                <p class="text-xs text-slate-400 truncate">{{ roomConfig[room]?.label ?? room }}</p>
            </div>
            <span v-if="count > 0"
                class="w-2.5 h-2.5 rounded-full flex-shrink-0 animate-pulse"
                :style="{ background: roomConfig[room]?.color ?? '#94a3b8' }"/>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-5">

        <!-- Today's Visits Table -->
        <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Today's Visits</h3>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-bold">{{ filtered.length }}</span>
                </div>
                <div class="relative">
                    <Search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-300"/>
                    <input v-model="search" placeholder="Search patient..."
                        class="pl-8 pr-3 h-7 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 w-48 bg-slate-50"/>
                </div>
            </div>
            <div class="overflow-y-auto" style="max-height:440px;">
                <div v-if="filtered.length === 0" class="py-14 text-center">
                    <Users class="w-10 h-10 text-slate-200 mx-auto mb-2"/>
                    <p class="text-slate-400 text-sm">No visits found</p>
                </div>
                <div v-else class="divide-y divide-slate-50">
                    <div v-for="v in filtered" :key="v.id"
                        class="px-4 py-3 hover:bg-slate-50/60 transition-colors flex items-center gap-3">
                        <!-- Visit type badge -->
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                            :style="{ background: visitBadge[v.visit_type]?.bg, color: visitBadge[v.visit_type]?.text }">
                            {{ visitBadge[v.visit_type]?.label ?? v.visit_type }}
                        </span>
                        <!-- Patient info -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ v.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">
                                {{ v.patient_code }}
                                <span v-if="v.employer" class="ml-1">&bull; {{ v.employer }}</span>
                            </p>
                        </div>
                        <!-- Module status dots -->
                        <div class="flex items-center gap-1.5 flex-shrink-0">
                            <span title="Laboratory"     class="w-2.5 h-2.5 rounded-full" :style="{ background: statusDot[v.lab_status]    ?? '#cbd5e1' }"/>
                            <span title="X-Ray"         class="w-2.5 h-2.5 rounded-full" :style="{ background: statusDot[v.xray_status]   ?? '#cbd5e1' }"/>
                            <span title="Drug Test"     class="w-2.5 h-2.5 rounded-full" :style="{ background: statusDot[v.drug_status]   ?? '#cbd5e1' }"/>
                            <span title="Doctor"        class="w-2.5 h-2.5 rounded-full" :style="{ background: statusDot[v.doctor_status] ?? '#cbd5e1' }"/>
                        </div>
                        <!-- Invoice status -->
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                            :class="{
                                'bg-red-100 text-red-700':           v.invoice_status === 'unpaid',
                                'bg-amber-100 text-amber-700':       v.invoice_status === 'partial',
                                'bg-emerald-100 text-emerald-700':   v.invoice_status === 'paid',
                                'bg-slate-100 text-slate-400':       v.invoice_status === 'none',
                            }">
                            {{ v.invoice_status === 'none' ? 'No Invoice' : v.invoice_status }}
                        </span>
                        <!-- Time -->
                        <span class="text-xs text-slate-400 flex-shrink-0 w-14 text-right">{{ v.registered_at }}</span>
                        <!-- Arrow -->
                        <a v-if="v.invoice_id" :href="route('reception.show', v.invoice_id)"
                            class="text-slate-300 hover:text-blue-500 flex-shrink-0 transition-colors">
                            <ChevronRight class="w-4 h-4"/>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Legend -->
            <div class="px-5 py-2 border-t bg-slate-50/50 flex items-center gap-4 text-xs text-slate-400">
                <span class="font-semibold">Module Status:</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-300 inline-block"/> None</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"/> Pending</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500 inline-block"/> Processing</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"/> Done</span>
                <span class="ml-auto text-slate-300">Lab · X-Ray · Drug · Doctor</span>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="space-y-4">

            <!-- Recent Payments -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"/>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recent Payments</h3>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-if="!recentPayments?.length" class="py-6 text-center text-xs text-slate-400">No payments today</div>
                    <div v-for="p in recentPayments" :key="p.time + p.patient_name"
                        class="px-4 py-2.5 flex items-center gap-3 hover:bg-slate-50/50 transition-colors">
                        <div class="w-8 h-8 rounded-xl flex items-center justify-center flex-shrink-0 bg-emerald-50">
                            <DollarSign class="w-4 h-4 text-emerald-600"/>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ p.patient_name }}</p>
                            <p class="text-xs text-slate-400 capitalize">{{ p.method }} &bull; {{ p.time }}</p>
                        </div>
                        <span class="text-sm font-black text-emerald-600 flex-shrink-0">{{ fmt(p.amount) }}</span>
                    </div>
                </div>
            </div>

            <!-- Pending Appointments -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-orange-400"/>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pending Appts</h3>
                        <span v-if="(stats.appointments_pending ?? 0) > 0"
                            class="text-xs bg-orange-100 text-orange-700 font-bold px-2 py-0.5 rounded-full">
                            {{ stats.appointments_pending }}
                        </span>
                    </div>
                    <a :href="route('appointments.index')" class="text-xs text-blue-600 hover:underline font-semibold">View all</a>
                </div>
                <div class="divide-y divide-slate-50">
                    <div v-if="!pendingAppointments?.length" class="py-6 text-center text-xs text-slate-400">No pending appointments</div>
                    <div v-for="a in pendingAppointments?.slice(0, 5)" :key="a.id"
                        class="px-4 py-2.5 hover:bg-orange-50/40 transition-colors">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ a.patient_name }}</p>
                            <span class="text-xs bg-orange-100 text-orange-700 font-bold px-1.5 py-0.5 rounded-full flex-shrink-0">Pending</span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">{{ a.service_label }}</p>
                        <p class="text-xs text-orange-600 font-semibold mt-0.5">{{ a.preferred_date }} &bull; {{ a.preferred_time }}</p>
                    </div>
                </div>
            </div>

            <!-- Field Visits Alert -->
            <div v-if="(fieldVisitsPending ?? 0) > 0"
                class="rounded-2xl p-4 border border-blue-200 bg-blue-50 flex items-start gap-3">
                <Activity class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5"/>
                <div>
                    <p class="text-sm font-bold text-blue-800">Field Visits Pending</p>
                    <p class="text-xs text-blue-700 mt-0.5">{{ fieldVisitsPending }} field visit(s) awaiting case number assignment.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</AppLayout>
</template>
