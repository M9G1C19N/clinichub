<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import StatCard from '@/Components/Dashboard/StatCard.vue'
import QueueBadge from '@/Components/Dashboard/QueueBadge.vue'
import VisitTypeBadge from '@/Components/Dashboard/VisitTypeBadge.vue'
import {
    Stethoscope, AlertCircle, CheckCircle2, Activity,
    FlaskConical, Scan, ShieldCheck, Clock, AlertTriangle,
    RefreshCw, Wifi, ChevronRight, ArrowRight
} from 'lucide-vue-next'

const props = defineProps({
    user: Object,
    role: String,
    stats: Object,
    pending: Array,
    completedToday: Array,
    classificationSummary: Object,
})

const classConfig = {
    A: { label: 'Class A', desc: 'Physically fit', color: '#059669', bg: '#D1FAE5' },
    B: { label: 'Class B', desc: 'Fit with minor ailment', color: '#0369A1', bg: '#DBEAFE' },
    C: { label: 'Class C', desc: 'Conditional', color: '#D97706', bg: '#FEF3C7' },
    D: { label: 'Class D', desc: 'Not fit', color: '#DC2626', bg: '#FEE2E2' },
    E: { label: 'Class E', desc: 'Needs evaluation', color: '#7C3AED', bg: '#EDE9FE' },
}

const readyPatients = computed(() => props.pending?.filter(p => p.all_results_in) ?? [])
const pendingResults = computed(() => props.pending?.filter(p => !p.all_results_in) ?? [])

const formatWait = (mins) => {
    if (mins < 60) return `${mins}m ago`
    return `${Math.floor(mins / 60)}h ${mins % 60}m ago`
}

const serviceIcons = {
    CBC: 'blood', UA: 'urine', CXRPA: 'xray', DRUGTEST: 'drug', DRUGTEST5: 'drug',
    OPD: 'consult', PE_CONSULT: 'consult', ANNUAL_PE: 'consult',
}

let timer = null
onMounted(() => {
    timer = setInterval(() => {
        router.reload({ only: ['stats', 'pending', 'completedToday'] })
    }, 20000)
})
onUnmounted(() => clearInterval(timer))

const refresh = () => router.reload({ only: ['stats', 'pending', 'completedToday', 'classificationSummary'] })

const resultBadge = (status) => ({
    released:  { icon: '✓', class: 'text-emerald-600 bg-emerald-50', label: '✓' },
    processing:{ icon: '◷', class: 'text-blue-500 bg-blue-50', label: '◷' },
    pending:   { icon: '○', class: 'text-amber-500 bg-amber-50', label: '○' },
    none:      { icon: '—', class: 'text-slate-300 bg-slate-50', label: '—' },
})[status] ?? { icon: '—', class: 'text-slate-300 bg-slate-50', label: '—' }

const totalPE = computed(() => Object.values(props.classificationSummary ?? {}).reduce((a, b) => a + b, 0))
</script>

<template>
    <AppLayout title="Doctor Dashboard">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-black text-[#0F2044]">Doctor's Workspace</h1>
                <p class="text-sm text-slate-400 mt-0.5">Consultations and PE classifications</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 text-xs text-emerald-600 bg-emerald-50 px-3 py-1.5 rounded-full">
                    <Wifi class="w-3 h-3" /> <span class="font-semibold">Live</span>
                </div>
                <button @click="refresh" class="p-2 text-slate-400 hover:text-[#1B4F9B] hover:bg-slate-50 rounded-lg transition-colors">
                    <RefreshCw class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <StatCard label="Ready for Review" :value="stats.ready_for_review" :icon="Stethoscope" color="#1B4F9B" :warning="stats.ready_for_review > 0" sub="All results in" />
            <StatCard label="Pending Total" :value="stats.pending_total" :icon="Clock" color="#D97706" sub="All pending consultations" />
            <StatCard label="Completed Today" :value="stats.completed_today" :icon="CheckCircle2" color="#059669" :success="stats.completed_today > 0" />
            <StatCard label="Abnormal Results" :value="stats.abnormal_today" :icon="AlertTriangle" color="#DC2626" :danger="stats.abnormal_today > 0" sub="Flagged lab results today" />
        </div>

        <div class="grid grid-cols-3 gap-5">
            <!-- Main panel: Pending patients -->
            <div class="col-span-3 lg:col-span-2 space-y-5">

                <!-- Ready for Review -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse" />
                        <h3 class="font-bold text-[#0F2044] flex-1">Ready for Review <span class="text-emerald-600">({{ readyPatients.length }})</span></h3>
                        <p class="text-xs text-slate-400">All results released</p>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div
                            v-for="p in readyPatients" :key="p.id"
                            class="flex items-center gap-4 px-5 py-4 hover:bg-emerald-50/30 transition-colors"
                        >
                            <!-- Patient -->
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="font-bold text-[#0F2044]">{{ p.patient_name }}</p>
                                    <span v-if="p.abnormal_count > 0" class="flex items-center gap-1 text-xs font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full">
                                        <AlertTriangle class="w-3 h-3" /> {{ p.abnormal_count }} abnormal
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <p class="text-xs text-slate-400 font-mono">{{ p.patient_code }}</p>
                                    <span class="text-slate-300">·</span>
                                    <p class="text-xs text-slate-400">{{ p.age_sex }}</p>
                                    <span v-if="p.employer" class="text-slate-300">·</span>
                                    <p v-if="p.employer" class="text-xs text-slate-400 truncate max-w-32">{{ p.employer }}</p>
                                </div>
                            </div>

                            <!-- Type + Results -->
                            <div class="flex-shrink-0 flex items-center gap-2">
                                <VisitTypeBadge :type="p.visit_type" size="xs" />
                                <!-- Result icons -->
                                <div class="flex gap-1">
                                    <span
                                        v-if="p.lab_status !== 'none'"
                                        class="text-xs px-1.5 py-0.5 rounded font-mono font-bold"
                                        :class="resultBadge(p.lab_status).class"
                                        title="Lab"
                                    >L</span>
                                    <span
                                        v-if="p.xray_status !== 'none'"
                                        class="text-xs px-1.5 py-0.5 rounded font-mono font-bold"
                                        :class="resultBadge(p.xray_status).class"
                                        title="X-Ray"
                                    >X</span>
                                    <span
                                        v-if="p.drug_status !== 'none'"
                                        class="text-xs px-1.5 py-0.5 rounded font-mono font-bold"
                                        :class="resultBadge(p.drug_status).class"
                                        title="Drug"
                                    >D</span>
                                </div>
                            </div>

                            <!-- Wait time -->
                            <div class="flex-shrink-0 text-right w-20">
                                <p class="text-xs text-slate-400">Registered</p>
                                <p class="text-xs font-semibold text-slate-600">{{ p.registered_at }}</p>
                            </div>

                            <!-- CTA -->
                            <Link
                                :href="route('doctor.consult', p.id)"
                                class="flex-shrink-0 bg-[#1B4F9B] text-white text-xs font-bold px-4 py-2 rounded-xl hover:bg-[#0F2044] transition-colors"
                            >
                                {{ p.has_draft ? 'Continue' : 'Consult' }}
                            </Link>
                        </div>

                        <div v-if="!readyPatients.length" class="py-8 text-center text-slate-300">
                            <CheckCircle2 class="w-10 h-10 mx-auto mb-2" />
                            <p class="text-sm">No patients ready for review yet</p>
                        </div>
                    </div>
                </div>

                <!-- Waiting on Results -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50">
                        <h3 class="font-bold text-[#0F2044]">Waiting for Results <span class="text-amber-600">({{ pendingResults.length }})</span></h3>
                    </div>
                    <div class="divide-y divide-slate-50 max-h-72 overflow-y-auto">
                        <div
                            v-for="p in pendingResults" :key="p.id"
                            class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-[#0F2044]">{{ p.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ p.patient_code }} · {{ p.age_sex }}</p>
                            </div>
                            <VisitTypeBadge :type="p.visit_type" size="xs" />
                            <div class="flex gap-1">
                                <span v-if="p.lab_status !== 'none'" class="text-xs px-1.5 py-0.5 rounded font-mono font-bold" :class="resultBadge(p.lab_status).class">L</span>
                                <span v-if="p.xray_status !== 'none'" class="text-xs px-1.5 py-0.5 rounded font-mono font-bold" :class="resultBadge(p.xray_status).class">X</span>
                                <span v-if="p.drug_status !== 'none'" class="text-xs px-1.5 py-0.5 rounded font-mono font-bold" :class="resultBadge(p.drug_status).class">D</span>
                            </div>
                            <p class="text-xs text-slate-400 flex-shrink-0">{{ p.registered_at }}</p>
                        </div>
                        <div v-if="!pendingResults.length" class="py-6 text-center text-slate-300 text-sm">
                            All pending patients have complete results
                        </div>
                    </div>
                </div>

                <!-- Completed Today -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm">
                    <div class="p-5 border-b border-slate-50">
                        <h3 class="font-bold text-[#0F2044]">Finalized Today <span class="text-slate-400">({{ completedToday?.length ?? 0 }})</span></h3>
                    </div>
                    <div class="divide-y divide-slate-50 max-h-64 overflow-y-auto">
                        <div v-for="c in completedToday" :key="c.id" class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50">
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-sm text-[#0F2044]">{{ c.patient_name }}</p>
                                <p class="text-xs text-slate-400">{{ c.icd10_description ?? 'PE Classification' }}</p>
                            </div>
                            <VisitTypeBadge :type="c.visit_type" size="xs" />
                            <span v-if="c.pe_classification" class="text-xs font-black px-2 py-1 rounded-lg" :style="{ background: classConfig[c.pe_classification]?.bg, color: classConfig[c.pe_classification]?.color }">
                                Class {{ c.pe_classification }}
                            </span>
                            <p class="text-xs text-slate-400 flex-shrink-0">{{ c.finalized_at }}</p>
                            <Link :href="route('doctor.consult', c.visit_id)" class="text-slate-400 hover:text-[#1B4F9B]">
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                        </div>
                        <div v-if="!completedToday?.length" class="py-6 text-center text-slate-300 text-sm">
                            No consultations finalized yet today
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-span-3 lg:col-span-1 space-y-4">
                <!-- PE Classification Summary -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                    <h3 class="font-bold text-[#0F2044] mb-1">PE Classifications Today</h3>
                    <p class="text-xs text-slate-400 mb-4">{{ stats.pe_done_today }} finalized</p>
                    <div class="space-y-3">
                        <div v-for="(config, cls) in classConfig" :key="cls" class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0"
                                :style="{ background: config.bg, color: config.color }"
                            >
                                {{ cls }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-slate-700">{{ config.label }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ config.desc }}</p>
                            </div>
                            <p class="font-black text-[#0F2044] text-lg tabular-nums">{{ classificationSummary?.[cls] ?? 0 }}</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between">
                        <span class="text-xs text-slate-400 font-semibold">TOTAL</span>
                        <span class="text-xl font-black text-[#1B4F9B]">{{ totalPE }}</span>
                    </div>
                </div>

                <!-- Result status legend -->
                <div class="bg-slate-50 rounded-2xl p-5">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Result Status</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded font-mono font-bold text-emerald-600 bg-emerald-50">✓</span>
                            <span class="text-slate-600">Released</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded font-mono font-bold text-blue-500 bg-blue-50">◷</span>
                            <span class="text-slate-600">In Progress</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded font-mono font-bold text-amber-500 bg-amber-50">○</span>
                            <span class="text-slate-600">Pending Entry</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs px-2 py-0.5 rounded font-mono font-bold text-slate-300 bg-slate-50">—</span>
                            <span class="text-slate-600">Not Ordered</span>
                        </div>
                        <div class="mt-3 pt-3 border-t border-slate-200 text-xs text-slate-500">
                            <span class="font-bold text-slate-400">L</span> = Lab &nbsp;
                            <span class="font-bold text-slate-400">X</span> = X-Ray &nbsp;
                            <span class="font-bold text-slate-400">D</span> = Drug Test
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
