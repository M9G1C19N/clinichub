<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    UserPlus, Clock, DollarSign, CheckCircle2, AlertCircle,
    FlaskConical, Scan, ShieldCheck, Stethoscope, RefreshCw,
    ChevronRight, CreditCard, Users, Activity, Wifi
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    todayVisits: Array,
    queueStats: Object,
    fieldVisitsPending: Number,
    recentPayments: Array,
})

const formatPeso = (v) => '₱' + Number(v ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })

const search = ref('')

const filteredVisits = computed(() => {
    if (!search.value) return props.todayVisits ?? []
    const q = search.value.toLowerCase()
    return (props.todayVisits ?? []).filter(v =>
        v.patient_name?.toLowerCase().includes(q) ||
        v.patient_code?.toLowerCase().includes(q) ||
        v.case_number?.toLowerCase().includes(q)
    )
})

// Journey status helper
const journeyIcon = (status) => ({
    released: '✓', processing: '◷', pending: '○', none: '—', finalized: '✓', in_progress: '◷'
})[status] ?? '—'

const journeyClass = (status) => ({
    released: 'text-emerald-600 font-bold',
    finalized: 'text-emerald-600 font-bold',
    processing: 'text-blue-500',
    in_progress: 'text-blue-500',
    pending: 'text-amber-500',
    none: 'text-slate-300',
})[status] ?? 'text-slate-300'

// Is overdue? (registered >2hrs, not all completed)
const isOverdue = (v) => v.minutes_ago > 120 && v.invoice_status !== 'paid'

const roomLabels = {
    laboratory: 'Lab', xray_utz: 'X-Ray', drug_test: 'Drug', interview_room: 'Doctor'
}

let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'todayVisits', 'queueStats', 'recentPayments'] })
    }, 15000)
})
onUnmounted(() => clearInterval(timer))

const refresh = () => router.reload({ only: ['stats', 'todayVisits', 'queueStats'] })
</script>

<template>
    <AppLayout title="Receptionist Dashboard">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">Reception</h1>
                <p class="text-sm text-slate-400 mt-0.5">{{ new Date().toLocaleDateString('en-PH', { weekday: 'long', month: 'long', day: 'numeric' }) }}</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                    <Wifi class="w-3 h-3" /> <span class="font-semibold">Live — refreshes every 15s</span>
                </div>
                <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                    <RefreshCw class="w-4 h-4" />
                </button>
                <Link :href="route('reception.create')" class="flex items-center gap-2 bg-[#1B4F9B] text-white text-sm font-semibold px-4 py-2.5 rounded-xl hover:bg-[#0F2044] transition-colors shadow-sm">
                    <UserPlus class="w-4 h-4" /> New Visit
                </Link>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <StatCard label="Registered Today" :value="stats.today_visits" :icon="Users" color="#1B4F9B" />
            <StatCard label="Pending Payment" :value="stats.pending_payment" :icon="AlertCircle" color="#D97706" :warning="stats.pending_payment > 0" :sub="formatPeso(stats.pending_balance)" />
            <StatCard label="Paid Today" :value="stats.completed" :icon="CheckCircle2" color="#059669" :success="stats.completed > 0" />
            <StatCard label="Active Queue" :value="stats.active_queue" :icon="Activity" color="#0EA5E9" />
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
                <p class="text-xs font-semibold uppercase tracking-widest text-slate-400 mb-3">Queue by Room</p>
                <div class="grid grid-cols-2 gap-1">
                    <div v-for="(count, room) in queueStats" :key="room" class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full flex-shrink-0" :class="count > 0 ? 'bg-amber-400' : 'bg-slate-200'" />
                        <span class="text-xs text-slate-500 truncate">{{ roomLabels[room] }}</span>
                        <span class="text-xs font-black text-[#0F2044] ml-auto">{{ count }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Field visits alert -->
        <div v-if="fieldVisitsPending > 0" class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-5 flex items-center gap-3">
            <AlertCircle class="w-4 h-4 text-amber-600 flex-shrink-0" />
            <p class="text-sm text-amber-700"><span class="font-bold">{{ fieldVisitsPending }}</span> field visit(s) pending case number assignment.</p>
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Journey Tracker (main panel) -->
            <div class="col-span-3 lg:col-span-2">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50 flex items-center gap-3">
                        <h3 class="font-bold text-[#0F2044] flex-1">Today's Patient Journey</h3>
                        <input
                            v-model="search"
                            placeholder="Search patient..."
                            class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 w-48 focus:outline-none focus:border-[#1B4F9B] focus:ring-1 focus:ring-[#1B4F9B]"
                        />
                    </div>

                    <!-- Column headers -->
                    <div class="grid grid-cols-12 gap-2 px-5 py-2 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-50">
                        <div class="col-span-3">Patient</div>
                        <div class="col-span-2">Type</div>
                        <div class="col-span-1 text-center">Lab</div>
                        <div class="col-span-1 text-center">X-Ray</div>
                        <div class="col-span-1 text-center">Drug</div>
                        <div class="col-span-1 text-center">Doctor</div>
                        <div class="col-span-2 text-right">Invoice</div>
                        <div class="col-span-1 text-center">Time</div>
                    </div>

                    <!-- Rows -->
                    <div class="divide-y divide-slate-50 max-h-96 overflow-y-auto">
                        <div
                            v-for="v in filteredVisits" :key="v.id"
                            class="grid grid-cols-12 gap-2 px-5 py-3 items-center hover:bg-slate-50 transition-colors"
                            :class="{ 'bg-amber-50/40': isOverdue(v) }"
                        >
                            <!-- Patient -->
                            <div class="col-span-3 min-w-0">
                                <Link :href="route('reception.show', v.invoice_id ?? '#')" class="font-semibold text-sm text-[#0F2044] hover:text-[#1B4F9B] truncate block">
                                    {{ v.patient_name }}
                                </Link>
                                <p class="text-xs text-slate-400 font-mono">{{ v.patient_code }}</p>
                            </div>

                            <!-- Visit type -->
                            <div class="col-span-2">
                                <VisitTypeBadge :type="v.visit_type" size="xs" />
                            </div>

                            <!-- Lab -->
                            <div class="col-span-1 text-center">
                                <span class="text-sm font-bold" :class="journeyClass(v.lab_status)">
                                    {{ journeyIcon(v.lab_status) }}
                                </span>
                            </div>

                            <!-- X-Ray -->
                            <div class="col-span-1 text-center">
                                <span class="text-sm font-bold" :class="journeyClass(v.xray_status)">
                                    {{ journeyIcon(v.xray_status) }}
                                </span>
                            </div>

                            <!-- Drug -->
                            <div class="col-span-1 text-center">
                                <span class="text-sm font-bold" :class="journeyClass(v.drug_status)">
                                    {{ journeyIcon(v.drug_status) }}
                                </span>
                            </div>

                            <!-- Doctor -->
                            <div class="col-span-1 text-center">
                                <span class="text-sm font-bold" :class="journeyClass(v.doctor_status)">
                                    {{ journeyIcon(v.doctor_status) }}
                                </span>
                            </div>

                            <!-- Invoice -->
                            <div class="col-span-2 text-right">
                                <p class="text-xs font-black" :class="{
                                    'text-red-600': v.invoice_status === 'unpaid',
                                    'text-amber-600': v.invoice_status === 'partial',
                                    'text-emerald-600': v.invoice_status === 'paid',
                                    'text-slate-400': v.invoice_status === 'none',
                                }">
                                    {{ v.invoice_status !== 'none' ? formatPeso(v.balance) : '—' }}
                                </p>
                                <QueueBadge v-if="v.invoice_status !== 'none'" :status="v.invoice_status" size="xs" />
                            </div>

                            <!-- Time -->
                            <div class="col-span-1 text-center">
                                <p class="text-xs text-slate-400">{{ v.registered_at }}</p>
                                <span v-if="isOverdue(v)" class="text-xs text-amber-500 font-semibold">Late</span>
                            </div>
                        </div>

                        <div v-if="!filteredVisits.length" class="py-12 text-center text-slate-300">
                            <Users class="w-10 h-10 mx-auto mb-2" />
                            <p class="text-sm">{{ search ? 'No matching patients' : 'No visits registered today yet' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- Recent Payments -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-[#0F2044]">Recent Payments</h3>
                        <CreditCard class="w-4 h-4 text-slate-300" />
                    </div>
                    <div class="space-y-3">
                        <div v-for="p in recentPayments" :key="p.time" class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                <DollarSign class="w-4 h-4 text-emerald-600" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ p.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ p.method.toUpperCase() }} · {{ p.time }}</p>
                            </div>
                            <p class="text-sm font-black text-emerald-600">{{ formatPeso(p.amount) }}</p>
                        </div>
                        <div v-if="!recentPayments?.length" class="text-sm text-slate-400 text-center py-4">
                            No payments today
                        </div>
                    </div>
                </div>

                <!-- Legend -->
                <div class="bg-slate-50 rounded-2xl p-5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Journey Legend</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2"><span class="font-bold text-emerald-600">✓</span><span class="text-slate-600">Released / Finalized</span></div>
                        <div class="flex items-center gap-2"><span class="font-bold text-blue-500">◷</span><span class="text-slate-600">In Progress / Draft</span></div>
                        <div class="flex items-center gap-2"><span class="font-bold text-amber-500">○</span><span class="text-slate-600">Pending / Not Started</span></div>
                        <div class="flex items-center gap-2"><span class="font-bold text-slate-300">—</span><span class="text-slate-600">Not Ordered</span></div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-[#0F2044] rounded-2xl p-5 text-white">
                    <h3 class="font-bold mb-3 text-white text-sm">Quick Access</h3>
                    <div class="space-y-2">
                        <Link :href="route('reception.create')" class="flex items-center gap-2 py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <UserPlus class="w-4 h-4" /> Register New Visit
                        </Link>
                        <Link :href="route('patients.create')" class="flex items-center gap-2 py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <Users class="w-4 h-4" /> New Patient
                        </Link>
                        <Link :href="route('queue.index')" class="flex items-center gap-2 py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 transition-colors text-sm font-medium">
                            <Activity class="w-4 h-4" /> Queue Board
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
