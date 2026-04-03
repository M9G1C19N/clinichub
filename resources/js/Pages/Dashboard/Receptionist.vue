<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
    Users, Receipt, Activity, Clock, CreditCard, CheckCircle2,
    AlertCircle, FlaskConical, ScanLine, TestTube, Stethoscope,
    RefreshCw, ChevronRight, Calendar, Building2, Wifi, WifiOff,
    UserPlus, Search,
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

const fmt  = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })
const search = ref('')
const online = ref(true)

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

const roomLabel = {
    laboratory:     'Lab',
    xray_utz:       'X-Ray',
    drug_test:      'Drug',
    interview_room: 'Doctor',
}
const roomColor = {
    laboratory:     '#3b82f6',
    xray_utz:       '#8b5cf6',
    drug_test:      '#f43f5e',
    interview_room: '#10b981',
}

let timer = null
onMounted(() => {
    timer = setInterval(() => router.reload({ only: ['stats','todayVisits','queueStats','recentPayments','pendingAppointments'] }), 30000)
})
onUnmounted(() => clearInterval(timer))
</script>

<template>
<AppLayout title="Reception Dashboard">
    <template #header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-800">Reception</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) }}</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="flex items-center gap-1 text-xs text-emerald-600 font-semibold">
                    <Wifi class="w-3.5 h-3.5"/> Live
                </span>
                <Link :href="route('reception.create', { patient_id: 0 })" v-if="false"/>
                <a :href="route('reception.index')"
                    class="flex items-center gap-1.5 text-xs font-bold px-3 py-1.5 rounded-lg text-white"
                    style="background:#1B4F9B">
                    <UserPlus class="w-3.5 h-3.5"/> New Visit
                </a>
            </div>
        </div>
    </template>

    <div class="space-y-5">

        <!-- KPI Row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#EFF6FF">
                    <Users class="w-5 h-5" style="color:#1D4ED8"/>
                </div>
                <div>
                    <p class="text-2xl font-black text-slate-800">{{ stats.today_visits }}</p>
                    <p class="text-xs text-slate-400">Today's Visits</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FEF2F2">
                    <AlertCircle class="w-5 h-5" style="color:#DC2626"/>
                </div>
                <div>
                    <p class="text-2xl font-black" style="color:#DC2626">{{ stats.pending_payment }}</p>
                    <p class="text-xs text-slate-400">Pending Payment</p>
                    <p class="text-xs font-semibold text-red-500">{{ fmt(stats.pending_balance) }}</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#F0FDF4">
                    <CheckCircle2 class="w-5 h-5" style="color:#16A34A"/>
                </div>
                <div>
                    <p class="text-2xl font-black" style="color:#16A34A">{{ stats.completed }}</p>
                    <p class="text-xs text-slate-400">Fully Paid</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:#FFF7ED">
                    <Calendar class="w-5 h-5" style="color:#C2410C"/>
                </div>
                <div>
                    <p class="text-2xl font-black" style="color:#C2410C">{{ stats.appointments_pending }}</p>
                    <p class="text-xs text-slate-400">Pending Appts</p>
                    <p class="text-xs text-slate-400">Today: <strong>{{ stats.appointments_today }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Queue Room Status -->
        <div class="grid grid-cols-4 gap-3">
            <div v-for="(count, room) in queueStats" :key="room"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-3 flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                    :style="{ background: roomColor[room] + '20' }">
                    <Activity class="w-4 h-4" :style="{ color: roomColor[room] }"/>
                </div>
                <div>
                    <p class="text-lg font-black text-slate-800">{{ count }}</p>
                    <p class="text-xs text-slate-400">{{ roomLabel[room] }} Queue</p>
                </div>
                <div class="ml-auto">
                    <span v-if="count > 0"
                        class="w-2.5 h-2.5 rounded-full block animate-pulse"
                        :style="{ background: roomColor[room] }"/>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-5">

            <!-- Today's Visits Table -->
            <div class="col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Today's Visits</h3>
                        <span class="text-xs text-slate-400">({{ filtered.length }})</span>
                    </div>
                    <div class="relative">
                        <Search class="w-3.5 h-3.5 absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-300"/>
                        <input v-model="search" placeholder="Search patient..."
                            class="pl-8 pr-3 h-7 text-xs border border-slate-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 w-48"/>
                    </div>
                </div>
                <div class="overflow-y-auto" style="max-height:420px;">
                    <div v-if="filtered.length === 0" class="py-12 text-center text-slate-400 text-sm">No visits yet today</div>
                    <div v-else class="divide-y divide-slate-50">
                        <div v-for="v in filtered" :key="v.id"
                            class="px-4 py-2.5 hover:bg-slate-50/60 transition-colors flex items-center gap-3">
                            <!-- Visit type badge -->
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                :style="{ background: visitBadge[v.visit_type]?.bg, color: visitBadge[v.visit_type]?.text }">
                                {{ visitBadge[v.visit_type]?.label ?? v.visit_type }}
                            </span>
                            <!-- Patient -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-800 truncate">{{ v.patient_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ v.patient_code }}
                                    <span v-if="v.employer" class="ml-1 text-slate-400">· {{ v.employer }}</span>
                                </p>
                            </div>
                            <!-- Module dots -->
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <span title="Lab" class="w-2 h-2 rounded-full" :style="{ background: statusDot[v.lab_status] ?? '#cbd5e1' }"/>
                                <span title="X-Ray" class="w-2 h-2 rounded-full" :style="{ background: statusDot[v.xray_status] ?? '#cbd5e1' }"/>
                                <span title="Drug" class="w-2 h-2 rounded-full" :style="{ background: statusDot[v.drug_status] ?? '#cbd5e1' }"/>
                                <span title="Doctor" class="w-2 h-2 rounded-full" :style="{ background: statusDot[v.doctor_status] ?? '#cbd5e1' }"/>
                            </div>
                            <!-- Invoice status -->
                            <span class="text-xs font-bold px-2 py-0.5 rounded-full flex-shrink-0"
                                :class="{
                                    'bg-red-100 text-red-700':    v.invoice_status === 'unpaid',
                                    'bg-amber-100 text-amber-700':v.invoice_status === 'partial',
                                    'bg-emerald-100 text-emerald-700': v.invoice_status === 'paid',
                                    'bg-slate-100 text-slate-500': v.invoice_status === 'none',
                                }">
                                {{ v.invoice_status === 'none' ? '—' : v.invoice_status }}
                            </span>
                            <!-- Link -->
                            <a v-if="v.invoice_id" :href="route('reception.show', v.invoice_id)"
                                class="text-slate-300 hover:text-blue-500 flex-shrink-0">
                                <ChevronRight class="w-4 h-4"/>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- Legend -->
                <div class="px-4 py-2 border-t bg-slate-50/50 flex items-center gap-4 text-xs text-slate-400">
                    <span>Dots: <span class="font-semibold">Lab · X-Ray · Drug · Doctor</span></span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-slate-300 inline-block"></span>None</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>Pending</span>
                    <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>Done</span>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="space-y-4">

                <!-- Recent Payments -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                        <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Recent Payments</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div v-if="!recentPayments?.length" class="py-6 text-center text-xs text-slate-400">No payments today</div>
                        <div v-for="p in recentPayments" :key="p.time + p.patient_name"
                            class="px-4 py-2.5 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-800 truncate max-w-[130px]">{{ p.patient_name }}</p>
                                <p class="text-xs text-slate-400 capitalize">{{ p.method }} · {{ p.time }}</p>
                            </div>
                            <span class="text-sm font-black text-emerald-600">{{ fmt(p.amount) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Pending Appointments -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-orange-400"></span>
                            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pending Appts</h3>
                        </div>
                        <a :href="route('appointments.index')" class="text-xs text-blue-500 hover:underline">View all</a>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div v-if="!pendingAppointments?.length" class="py-6 text-center text-xs text-slate-400">No pending appointments</div>
                        <div v-for="a in pendingAppointments?.slice(0,5)" :key="a.id"
                            class="px-4 py-2.5">
                            <p class="text-xs font-semibold text-slate-800 truncate">{{ a.patient_name }}</p>
                            <p class="text-xs text-slate-400">{{ a.service_label }}</p>
                            <p class="text-xs text-orange-500 font-semibold">{{ a.preferred_date }} · {{ a.preferred_time }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</AppLayout>
</template>
