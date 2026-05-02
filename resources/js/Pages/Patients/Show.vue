<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    User, Activity, FlaskConical, ScanLine, TestTube,
    Stethoscope, FileText, Calendar, Edit, Phone,
    MapPin, Heart, AlertTriangle, CheckCircle2,
    ChevronDown, ChevronRight, Printer, Plus,
    ClipboardList, Building2, Clock, Pill,
    ReceiptText, Search,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE, VISIT_TYPE_LABEL } from '@/config/visitTypes.js'

const props = defineProps({
    patient:       Object,
    visitHistory:  Array,
    stats:         Object,
    prescriptions: { type: Array, default: () => [] },
})

const activeTab      = ref('overview')
const expandedVisits = ref(new Set())
const historySearch  = ref('')
const labFilter      = ref('all')

function toggleVisit(id) {
    if (expandedVisits.value.has(id)) expandedVisits.value.delete(id)
    else expandedVisits.value.add(id)
}

const filteredHistory = computed(() => {
    const s = historySearch.value.toLowerCase().trim()
    if (!s) return props.visitHistory ?? []
    return (props.visitHistory ?? []).filter(v =>
        v.visit_date.toLowerCase().includes(s) ||
        VISIT_TYPE_LABEL[v.visit_type]?.toLowerCase().includes(s) ||
        v.employer_company?.toLowerCase().includes(s) ||
        v.case_number?.toLowerCase().includes(s)
    )
})

// Flat lab results for total count
const allLabResults = computed(() => {
    const results = []
    props.visitHistory.forEach(v => {
        if (!v.lab?.results) return
        v.lab.results.forEach(r => {
            results.push({ ...r, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id, case_number: v.case_number })
        })
    })
    return results
})

const catOrder = { hematology: 1, chemistry: 2, urinalysis: 3, stool: 4, serology: 5 }
const catLabel  = { hematology: 'Hematology', chemistry: 'Chemistry', urinalysis: 'Urinalysis', stool: 'Stool Exam', serology: 'Serology' }

const labCategories = computed(() => [...new Set(allLabResults.value.map(r => r.category).filter(Boolean))])

// Lab results grouped by visit then filtered, used in Labs tab
const labVisitGroups = computed(() => {
    const visits = props.visitHistory.filter(v => v.lab?.results?.length > 0)
    return visits.map(v => {
        let results = v.lab.results
        if (labFilter.value === 'abnormal') results = results.filter(r => r.is_abnormal)
        else if (labFilter.value !== 'all') results = results.filter(r => r.category === labFilter.value)
        if (!results.length) return null

        const byCategory = {}
        results.forEach(r => {
            const cat = r.category ?? 'other'
            if (!byCategory[cat]) byCategory[cat] = []
            byCategory[cat].push(r)
        })
        return {
            visit_id:       v.id,
            visit_date:     v.visit_date,
            visit_type:     v.visit_type,
            case_number:    v.case_number,
            employer:       v.employer_company,
            lab_status:     v.lab.status,
            has_abnormal:   v.lab.has_abnormal,
            abnormal_count: v.lab.abnormal_count,
            result_date:    v.lab.result_date,
            categories: Object.entries(byCategory)
                .sort(([a], [b]) => (catOrder[a] ?? 9) - (catOrder[b] ?? 9))
                .map(([cat, results]) => ({ cat, results })),
        }
    }).filter(Boolean)
})

// Helper to group lab results by category inside a visit (used in Visits tab expanded body)
function groupLabByCategory(results) {
    const byCategory = {}
    results.forEach(r => {
        const cat = r.category ?? 'other'
        if (!byCategory[cat]) byCategory[cat] = []
        byCategory[cat].push(r)
    })
    return Object.entries(byCategory)
        .sort(([a], [b]) => (catOrder[a] ?? 9) - (catOrder[b] ?? 9))
        .map(([cat, rows]) => ({ cat, rows }))
}

const allImaging = computed(() =>
    props.visitHistory.filter(v => v.imaging).map(v => ({
        ...v.imaging, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id, case_number: v.case_number,
    }))
)
const allDrugTests = computed(() =>
    props.visitHistory.filter(v => v.drug_test).map(v => ({
        ...v.drug_test, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id,
        employer: v.employer_company, case_number: v.case_number,
    }))
)
const allConsultations = computed(() =>
    props.visitHistory.filter(v => v.consultation?.is_finalized).map(v => ({
        ...v.consultation, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id,
        employer: v.employer_company, case_number: v.case_number,
    }))
)

const latestVitalsVisit = computed(() => props.visitHistory.find(v => v.vitals) ?? null)
const latestVitals      = computed(() => latestVitalsVisit.value?.vitals ?? null)

const peClassConfig = {
    A: { color: '#15803d', bg: '#f0fdf4', border: '#bbf7d0', label: 'Fit for Work' },
    B: { color: '#1d4ed8', bg: '#eff6ff', border: '#bfdbfe', label: 'With Minor Findings' },
    C: { color: '#b45309', bg: '#fffbeb', border: '#fde68a', label: 'With Findings — Treated' },
    D: { color: '#c2410c', bg: '#fff7ed', border: '#fed7aa', label: 'With Findings — Untreated' },
    E: { color: '#b91c1c', bg: '#fef2f2', border: '#fecaca', label: 'Unfit' },
}

const drugResultColor = {
    negative:    'bg-emerald-100 text-emerald-700',
    positive:    'bg-red-100 text-red-700',
    cancelled:   'bg-slate-100 text-slate-600',
    diluted:     'bg-amber-100 text-amber-700',
    substituted: 'bg-purple-100 text-purple-700',
    adulterated: 'bg-rose-100 text-rose-700',
}

function bmiColor(cat) {
    if (!cat) return '#64748b'
    if (cat === 'Normal') return '#16a34a'
    if (cat?.toLowerCase().includes('obese') || cat === 'Underweight') return '#dc2626'
    return '#d97706'
}

function archivePatient() {
    if (!confirm(`Archive patient ${props.patient.full_name}?`)) return
    router.delete(route('patients.destroy', props.patient.id))
}
</script>

<template>
    <AppLayout :title="patient.full_name">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('patients.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <ChevronRight class="w-4 h-4 rotate-180"/>
                        </Button>
                    </Link>
                    <div class="w-10 h-10 rounded-xl overflow-hidden border-2 border-slate-200 flex-shrink-0">
                        <img v-if="patient.photo_path" :src="patient.photo_path" class="w-full h-full object-cover"/>
                        <div v-else class="w-full h-full flex items-center justify-center text-white font-bold text-sm" style="background:#1B4F9B">
                            {{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">{{ patient.full_name }}</h1>
                            <span class="text-xs font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">{{ patient.patient_code }}</span>
                            <span v-if="!patient.is_active" class="text-xs font-bold px-2 py-0.5 rounded bg-red-100 text-red-600">ARCHIVED</span>
                        </div>
                        <p class="text-slate-400 text-xs mt-0.5">{{ patient.age_sex }} · {{ patient.date_of_birth }} · {{ patient.civil_status }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link :href="route('reception.create') + '?patient_id=' + patient.id">
                        <Button size="sm" class="gap-2 text-white" style="background:#1B4F9B;">
                            <Plus class="w-4 h-4"/> New Visit
                        </Button>
                    </Link>
                    <Link :href="route('patients.edit', patient.id)">
                        <Button variant="outline" size="sm" class="gap-2">
                            <Edit class="w-4 h-4"/> Edit
                        </Button>
                    </Link>
                </div>
            </div>
        </template>

        <!-- ── TAB NAVIGATION ──────────────────────────────── -->
        <div class="flex items-center gap-0.5 mb-5 border-b border-slate-200 overflow-x-auto">
            <button v-for="tab in [
                { key: 'overview',      label: 'Overview',      icon: User,         count: null                  },
                { key: 'visits',        label: 'Visits',        icon: Calendar,     count: stats.total_visits    },
                { key: 'labs',          label: 'Lab Results',   icon: FlaskConical, count: allLabResults.length  },
                { key: 'imaging',       label: 'Imaging',       icon: ScanLine,     count: allImaging.length     },
                { key: 'drug_tests',    label: 'Drug Tests',    icon: TestTube,     count: allDrugTests.length   },
                { key: 'consultations', label: 'Consultations', icon: Stethoscope,  count: allConsultations.length},
                { key: 'prescriptions', label: 'Prescriptions', icon: Pill,         count: prescriptions.length  },
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                class="flex items-center gap-1.5 px-4 py-3 text-sm font-semibold border-b-2 transition-all -mb-px whitespace-nowrap flex-shrink-0"
                :class="activeTab === tab.key ? 'border-blue-600 text-blue-700' : 'border-transparent text-slate-500 hover:text-slate-700'">
                <component :is="tab.icon" class="w-3.5 h-3.5"/>
                {{ tab.label }}
                <span v-if="tab.count !== null"
                    class="text-xs font-black px-1.5 py-0.5 rounded-md min-w-[20px] text-center"
                    :class="activeTab === tab.key ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'">
                    {{ tab.count }}
                </span>
            </button>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 1 · OVERVIEW                                   -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-3 gap-5">

            <!-- LEFT COLUMN: Patient profile -->
            <div class="space-y-4">

                <!-- Profile card -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="h-1.5" style="background:linear-gradient(90deg,#0F2044,#1B4F9B,#2E75B6)"/>
                    <div class="p-5 flex flex-col items-center text-center">
                        <div class="w-24 h-24 rounded-2xl overflow-hidden border-4 border-white shadow-lg mb-3 flex-shrink-0">
                            <img v-if="patient.photo_path" :src="patient.photo_path" class="w-full h-full object-cover"/>
                            <div v-else class="w-full h-full flex items-center justify-center text-white text-3xl font-black" style="background:#1B4F9B">
                                {{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}
                            </div>
                        </div>
                        <h2 class="text-base font-black text-slate-800 leading-tight">{{ patient.full_name }}</h2>
                        <p class="text-xs text-slate-500 mt-0.5">{{ patient.age_sex }} · {{ patient.civil_status }}</p>
                        <span v-if="patient.occupation" class="text-xs text-slate-400 mt-0.5">{{ patient.occupation }}</span>
                        <span class="mt-2 text-xs font-mono font-black text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                            {{ patient.patient_code }}
                        </span>

                        <!-- Stats strip -->
                        <div class="w-full border-t border-slate-100 mt-4 pt-4 grid grid-cols-3 gap-1">
                            <div class="text-center p-2 rounded-xl bg-slate-50">
                                <p class="text-xl font-black text-slate-800">{{ stats.total_visits }}</p>
                                <p class="text-xs text-slate-400">Visits</p>
                            </div>
                            <div class="text-center p-2 rounded-xl bg-slate-50">
                                <p class="text-xl font-black" :class="stats.lab_abnormals > 0 ? 'text-red-600' : 'text-emerald-600'">{{ stats.lab_abnormals }}</p>
                                <p class="text-xs text-slate-400">Abnormals</p>
                            </div>
                            <div class="text-center p-2 rounded-xl bg-slate-50">
                                <p class="text-xl font-black text-purple-600">{{ stats.pe_count }}</p>
                                <p class="text-xs text-slate-400">PE Exams</p>
                            </div>
                        </div>

                        <!-- Badges -->
                        <div class="flex items-center gap-2 flex-wrap justify-center mt-3">
                            <span v-if="patient.blood_type" class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-50 text-red-700 border border-red-200 flex items-center gap-1">
                                <Heart class="w-3 h-3"/> {{ patient.blood_type }}
                            </span>
                            <span v-if="patient.philhealth_number" class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                                PhilHealth
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contact -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-2.5">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Contact</h3>
                    <div v-if="patient.contact_number" class="flex items-center gap-2.5 text-sm">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <Phone class="w-3.5 h-3.5 text-slate-400"/>
                        </div>
                        <span class="text-slate-700">{{ patient.contact_number }}</span>
                    </div>
                    <div v-if="patient.email" class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span class="text-slate-700 text-xs truncate">{{ patient.email }}</span>
                    </div>
                    <div v-if="patient.address" class="flex items-start gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                            <MapPin class="w-3.5 h-3.5 text-slate-400"/>
                        </div>
                        <span class="text-slate-600 text-xs leading-relaxed">{{ patient.address }}</span>
                    </div>
                    <div v-if="patient.emergency_contact_name" class="border-t border-slate-100 pt-2.5 space-y-0.5">
                        <p class="text-xs text-slate-400">Emergency Contact</p>
                        <p class="text-sm font-semibold text-slate-700">{{ patient.emergency_contact_name }}</p>
                        <p class="text-xs text-slate-500">{{ patient.emergency_contact_number }}</p>
                    </div>
                </div>

                <!-- Companies -->
                <div v-if="stats.companies?.length > 0" class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-2">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Companies</h3>
                    <div v-for="c in stats.companies" :key="c" class="flex items-center gap-2">
                        <Building2 class="w-3.5 h-3.5 text-purple-400 flex-shrink-0"/>
                        <span class="text-sm font-semibold text-purple-700">{{ c }}</span>
                    </div>
                </div>
            </div>

            <!-- CENTER + RIGHT: Activity -->
            <div class="col-span-2 space-y-4">

                <!-- Latest visit -->
                <div v-if="visitHistory.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-700 flex items-center gap-2 text-sm">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"/>
                            Latest Visit
                        </h3>
                        <span class="text-xs text-slate-400">{{ visitHistory[0].visit_date }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-5">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                    :style="{ background: VISIT_TYPE_BADGE[visitHistory[0].visit_type]?.bg, color: VISIT_TYPE_BADGE[visitHistory[0].visit_type]?.color }">
                                    {{ VISIT_TYPE_BADGE[visitHistory[0].visit_type]?.label }}
                                </span>
                                <span v-if="visitHistory[0].case_number" class="text-xs font-mono text-slate-400">CN: {{ visitHistory[0].case_number }}</span>
                            </div>
                            <p v-if="visitHistory[0].employer_company" class="text-sm font-bold text-purple-700 mb-2">{{ visitHistory[0].employer_company }}</p>
                            <div class="flex flex-wrap gap-1">
                                <span v-for="svc in visitHistory[0].services?.slice(0,5)" :key="svc.code"
                                    class="text-xs font-mono bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                    {{ svc.code }}
                                </span>
                                <span v-if="visitHistory[0].services?.length > 5" class="text-xs text-slate-400">+{{ visitHistory[0].services.length - 5 }}</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div v-if="visitHistory[0].consultation?.pe_classification" class="flex items-center gap-2">
                                <span class="text-xs font-black px-2.5 py-1 rounded-lg"
                                    :style="{ background: peClassConfig[visitHistory[0].consultation.pe_classification]?.bg, color: peClassConfig[visitHistory[0].consultation.pe_classification]?.color }">
                                    CLASS {{ visitHistory[0].consultation.pe_classification }}
                                </span>
                                <span class="text-xs text-slate-500">{{ peClassConfig[visitHistory[0].consultation.pe_classification]?.label }}</span>
                            </div>
                            <div v-if="visitHistory[0].lab" class="flex items-center gap-1.5 text-xs">
                                <FlaskConical class="w-3.5 h-3.5 text-slate-400"/>
                                <span class="capitalize font-semibold" :class="visitHistory[0].lab.status === 'released' ? 'text-emerald-600' : 'text-amber-600'">
                                    Lab {{ visitHistory[0].lab.status }}
                                </span>
                                <span v-if="visitHistory[0].lab.has_abnormal" class="font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">
                                    {{ visitHistory[0].lab.abnormal_count }} Abnormal
                                </span>
                            </div>
                            <div v-if="visitHistory[0].drug_test?.result" class="flex items-center gap-1.5 text-xs">
                                <TestTube class="w-3.5 h-3.5 text-slate-400"/>
                                <span :class="['font-bold px-2 py-0.5 rounded uppercase', drugResultColor[visitHistory[0].drug_test.result]]">
                                    {{ visitHistory[0].drug_test.result }}
                                </span>
                            </div>
                            <div v-if="visitHistory[0].total_amount > 0" class="flex items-center gap-1.5 text-xs">
                                <ReceiptText class="w-3.5 h-3.5 text-slate-400"/>
                                <span class="font-bold text-slate-700">₱{{ Number(visitHistory[0].total_amount).toLocaleString() }}</span>
                                <span :class="['px-1.5 py-0.5 rounded font-semibold capitalize', visitHistory[0].invoice_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : visitHistory[0].invoice_status === 'partial' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700']">
                                    {{ visitHistory[0].invoice_status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit timeline -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
                    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <Clock class="w-3.5 h-3.5"/> Visit Timeline
                        </h3>
                        <Button variant="outline" size="sm" class="text-xs h-7" @click="activeTab = 'visits'">View all</Button>
                    </div>
                    <div class="p-4">
                        <div v-if="!visitHistory.length" class="text-center py-8 text-slate-400 text-sm">No visits yet</div>
                        <div class="relative">
                            <div class="absolute left-3.5 top-2 bottom-2 w-px bg-slate-200"/>
                            <div v-for="v in visitHistory.slice(0, 6)" :key="v.id" class="relative flex items-start gap-4 mb-4 last:mb-0 pl-9">
                                <div class="absolute left-2 w-3 h-3 rounded-full border-2 border-white mt-1 shadow-sm"
                                    :style="{ background: VISIT_TYPE_BADGE[v.visit_type]?.color ?? '#1B4F9B' }"/>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                            :style="{ background: VISIT_TYPE_BADGE[v.visit_type]?.bg, color: VISIT_TYPE_BADGE[v.visit_type]?.color }">
                                            {{ VISIT_TYPE_BADGE[v.visit_type]?.label }}
                                        </span>
                                        <span v-if="v.case_number" class="text-xs font-mono text-slate-400">CN: {{ v.case_number }}</span>
                                        <span v-if="v.consultation?.pe_classification" class="text-xs font-black px-1.5 py-0.5 rounded"
                                            :style="{ background: peClassConfig[v.consultation.pe_classification]?.bg, color: peClassConfig[v.consultation.pe_classification]?.color }">
                                            Class {{ v.consultation.pe_classification }}
                                        </span>
                                        <span v-if="v.lab?.has_abnormal" class="text-xs font-bold text-red-600 flex items-center gap-0.5">
                                            <AlertTriangle class="w-3 h-3"/> {{ v.lab.abnormal_count }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ v.visit_date }}
                                        <span v-if="v.employer_company" class="text-purple-600 font-semibold"> · {{ v.employer_company }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest vitals -->
                <div v-if="latestVitals" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <Activity class="w-3.5 h-3.5"/> Latest Vitals
                        </h3>
                        <span class="text-xs text-slate-400">{{ latestVitalsVisit?.visit_date }}</span>
                    </div>
                    <div class="grid grid-cols-5 gap-3">
                        <div v-for="vt in [
                            { label: 'Weight', value: latestVitals.weight_kg ? latestVitals.weight_kg + ' kg' : '—' },
                            { label: 'Height', value: latestVitals.height_cm ? (latestVitals.height_cm/100).toFixed(2) + ' m' : '—' },
                            { label: 'BMI',    value: latestVitals.bmi ?? '—', sub: latestVitals.bmi_category, bmi: true },
                            { label: 'BP',     value: latestVitals.bp ?? '—' },
                            { label: 'Pulse',  value: latestVitals.pulse_rate ? latestVitals.pulse_rate + ' bpm' : '—' },
                        ]" :key="vt.label" class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-sm font-black" :style="vt.bmi ? { color: bmiColor(vt.sub) } : { color: '#1e293b' }">{{ vt.value }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ vt.label }}</p>
                            <p v-if="vt.sub" class="text-xs font-semibold mt-0.5" :style="{ color: bmiColor(vt.sub) }">{{ vt.sub }}</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 2 · VISITS — accordion cards by case number    -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'visits'" class="space-y-3">

            <div class="flex items-center gap-3 mb-4">
                <div class="relative max-w-sm flex-1">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                    <input v-model="historySearch" placeholder="Search by date, type, company, or case no..."
                        class="w-full h-9 pl-9 pr-4 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"/>
                </div>
                <span class="text-xs text-slate-400">{{ filteredHistory.length }} visit{{ filteredHistory.length !== 1 ? 's' : '' }}</span>
            </div>

            <div v-if="!filteredHistory.length" class="text-center py-16 text-slate-400">
                <Calendar class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No visits found</p>
            </div>

            <div v-for="v in filteredHistory" :key="v.id"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                <!-- ── Card header ── -->
                <div class="flex items-stretch cursor-pointer hover:bg-slate-50/70 transition-colors"
                    @click="toggleVisit(v.id)">

                    <!-- Date strip -->
                    <div class="w-16 flex-shrink-0 flex flex-col items-center justify-center py-4 px-2 border-r border-slate-100 bg-slate-50/80">
                        <div class="text-xs text-slate-400 leading-none">{{ v.visit_date.split(' ')[0] }}</div>
                        <div class="text-2xl font-black text-slate-800 leading-tight">{{ v.visit_date.split(' ')[1]?.replace(',','') }}</div>
                        <div class="text-xs text-slate-500">{{ v.visit_date.split(' ')[2] }}</div>
                    </div>

                    <!-- Info section -->
                    <div class="flex-1 px-4 py-3 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1.5">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                :style="{ background: VISIT_TYPE_BADGE[v.visit_type]?.bg, color: VISIT_TYPE_BADGE[v.visit_type]?.color }">
                                {{ VISIT_TYPE_BADGE[v.visit_type]?.label }}
                            </span>
                            <span v-if="v.case_number" class="text-xs font-mono font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-lg">
                                CN: {{ v.case_number }}
                            </span>
                            <span v-if="v.is_field_visit" class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                Field Visit
                            </span>
                        </div>
                        <p v-if="v.employer_company" class="text-sm font-bold text-purple-700 mb-1">{{ v.employer_company }}</p>
                        <div class="flex flex-wrap gap-1">
                            <span v-for="svc in v.services?.slice(0,5)" :key="svc.code"
                                class="text-xs font-mono bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">
                                {{ svc.code }}
                            </span>
                            <span v-if="v.services?.length > 5" class="text-xs text-slate-400">+{{ v.services.length - 5 }} more</span>
                        </div>
                    </div>

                    <!-- Status indicators -->
                    <div class="flex items-center gap-2 px-4 flex-shrink-0">
                        <span v-if="v.consultation?.pe_classification" class="text-xs font-black px-2.5 py-1 rounded-lg"
                            :style="{ background: peClassConfig[v.consultation.pe_classification]?.bg, color: peClassConfig[v.consultation.pe_classification]?.color }">
                            CLASS {{ v.consultation.pe_classification }}
                        </span>
                        <span v-if="v.lab?.has_abnormal" class="text-xs font-bold text-red-600 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded flex items-center gap-1">
                            <AlertTriangle class="w-3 h-3"/> {{ v.lab.abnormal_count }}
                        </span>
                        <span v-if="v.drug_test?.result" :class="['text-xs font-bold px-2 py-0.5 rounded uppercase', drugResultColor[v.drug_test.result]]">
                            {{ v.drug_test.result }}
                        </span>
                        <span v-if="v.total_amount > 0" :class="['text-xs font-semibold px-2 py-0.5 rounded-full',
                            v.invoice_status === 'paid'    ? 'bg-emerald-100 text-emerald-700' :
                            v.invoice_status === 'partial' ? 'bg-amber-100 text-amber-700'    : 'bg-red-100 text-red-700']">
                            ₱{{ Number(v.total_amount).toLocaleString() }}
                        </span>
                        <component :is="expandedVisits.has(v.id) ? ChevronDown : ChevronRight"
                            class="w-4 h-4 text-slate-400 flex-shrink-0"/>
                    </div>
                </div>

                <!-- ── Expanded body ── -->
                <div v-if="expandedVisits.has(v.id)" class="border-t border-slate-100">

                    <!-- Vitals -->
                    <div v-if="v.vitals" class="p-4 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5 mb-3">
                            <Activity class="w-3.5 h-3.5"/> Vitals
                        </p>
                        <div class="grid grid-cols-6 gap-2">
                            <div v-for="vt in [
                                { l: 'Weight',  val: v.vitals.weight_kg ? v.vitals.weight_kg + ' kg' : null },
                                { l: 'Height',  val: v.vitals.height_cm ? (v.vitals.height_cm/100).toFixed(2) + ' m' : null },
                                { l: 'BMI',     val: v.vitals.bmi, sub: v.vitals.bmi_category, bmi: true },
                                { l: 'BP',      val: v.vitals.bp },
                                { l: 'Pulse',   val: v.vitals.pulse_rate ? v.vitals.pulse_rate + ' bpm' : null },
                                { l: 'Temp',    val: v.vitals.temperature ? v.vitals.temperature + '°C' : null },
                            ]" :key="vt.l"
                                class="text-center p-2.5 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-xs font-black" :style="vt.bmi ? { color: bmiColor(vt.sub) } : { color: '#334155' }">{{ vt.val || '—' }}</p>
                                <p class="text-xs text-slate-400 mt-0.5">{{ vt.l }}</p>
                                <p v-if="vt.sub" class="text-xs font-semibold mt-0.5" :style="{ color: bmiColor(vt.sub) }">{{ vt.sub }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Results -->
                    <div v-if="v.lab?.results?.length > 0" class="p-4 border-b border-slate-100">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                <FlaskConical class="w-3.5 h-3.5"/> Laboratory Results
                            </p>
                            <div class="flex items-center gap-2">
                                <span v-if="v.lab.has_abnormal"
                                    class="text-xs font-bold text-red-600 bg-red-50 border border-red-100 px-2 py-0.5 rounded-full flex items-center gap-1">
                                    <AlertTriangle class="w-3 h-3"/> {{ v.lab.abnormal_count }} Abnormal
                                </span>
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full capitalize',
                                    v.lab.status === 'released' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                    {{ v.lab.status }}
                                </span>
                            </div>
                        </div>

                        <!-- Results grouped by category -->
                        <div class="space-y-3">
                            <div v-for="grp in groupLabByCategory(v.lab.results)" :key="grp.cat">
                                <!-- Category header -->
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="flex-1 border-t border-slate-200"/>
                                    <span class="text-xs font-black text-slate-400 uppercase tracking-wider px-2">
                                        {{ catLabel[grp.cat] ?? grp.cat }}
                                    </span>
                                    <span class="flex-1 border-t border-slate-200"/>
                                </div>
                                <!-- Result rows -->
                                <div class="rounded-xl overflow-hidden border border-slate-200">
                                    <table class="w-full text-xs">
                                        <tbody>
                                            <tr v-for="r in grp.rows" :key="r.test_code"
                                                :class="['border-b border-slate-100 last:border-0', r.is_abnormal ? 'bg-red-50/50' : 'bg-white hover:bg-slate-50']">
                                                <td class="px-3 py-2 font-medium text-slate-700" style="width:210px">{{ r.test_name }}</td>
                                                <td class="px-3 py-2 font-black" :class="r.is_abnormal ? 'text-red-600' : 'text-slate-800'">
                                                    {{ r.result_value }}
                                                    <span v-if="r.flag" class="ml-1 text-xs font-black text-red-600 bg-red-100 px-1 py-0.5 rounded">{{ r.flag }}</span>
                                                </td>
                                                <td class="px-3 py-2 text-slate-400">{{ r.unit }}</td>
                                                <td class="px-3 py-2 text-slate-400">{{ r.normal_range }}</td>
                                                <td class="px-3 py-2">
                                                    <span v-if="r.is_abnormal" class="text-red-600 font-semibold flex items-center gap-1">
                                                        <AlertTriangle class="w-3 h-3"/> Abnormal
                                                    </span>
                                                    <span v-else class="text-emerald-600 flex items-center gap-1">
                                                        <CheckCircle2 class="w-3 h-3"/> Normal
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Imaging + Drug Test side by side -->
                    <div v-if="v.imaging || v.drug_test"
                        class="p-4 border-b border-slate-100"
                        :class="v.imaging && v.drug_test ? 'grid grid-cols-2 gap-4' : ''">

                        <!-- Imaging -->
                        <div v-if="v.imaging" class="space-y-2">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                <ScanLine class="w-3.5 h-3.5"/> Imaging
                            </p>
                            <div class="bg-slate-50 rounded-xl border border-slate-200 p-3.5 space-y-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-sm font-black text-slate-800">{{ v.imaging.imaging_type }}</span>
                                    <span v-if="v.imaging.is_provisional" class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-semibold">Provisional</span>
                                    <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full capitalize',
                                        v.imaging.status === 'released' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                        {{ v.imaging.status }}
                                    </span>
                                </div>
                                <div v-if="v.imaging.impression" class="bg-slate-900 text-white rounded-lg px-3 py-2 font-black text-sm uppercase tracking-wide">
                                    {{ v.imaging.impression }}
                                </div>
                                <a :href="route('xray.print', v.id)" target="_blank"
                                    class="text-blue-600 text-xs font-semibold flex items-center gap-1 hover:underline">
                                    <Printer class="w-3 h-3"/> Print Report
                                </a>
                            </div>
                        </div>

                        <!-- Drug Test -->
                        <div v-if="v.drug_test" class="space-y-2">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5">
                                <TestTube class="w-3.5 h-3.5"/> Drug Test
                            </p>
                            <div class="bg-slate-50 rounded-xl border border-slate-200 p-3.5 space-y-2">
                                <p v-if="v.drug_test.drugs_label" class="text-xs text-slate-500">{{ v.drug_test.drugs_label }}</p>
                                <span v-if="v.drug_test.result"
                                    :class="['text-sm font-black px-3 py-1.5 rounded-lg uppercase inline-block', drugResultColor[v.drug_test.result]]">
                                    {{ v.drug_test.result }}
                                </span>
                                <p v-if="v.drug_test.code_number" class="text-xs font-mono text-slate-400">Code: {{ v.drug_test.code_number }}</p>
                                <a :href="route('drug-test.print', v.id)" target="_blank"
                                    class="text-blue-600 text-xs font-semibold flex items-center gap-1 hover:underline">
                                    <Printer class="w-3 h-3"/> Print Report
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Consultation -->
                    <div v-if="v.consultation" class="p-4 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5 mb-3">
                            <Stethoscope class="w-3.5 h-3.5"/> Doctor's Assessment
                        </p>
                        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <div class="flex items-start gap-4">
                                <!-- PE Class block -->
                                <div v-if="v.consultation.pe_classification" class="flex-shrink-0 text-center">
                                    <div class="w-14 h-14 rounded-xl flex flex-col items-center justify-center font-black border"
                                        :style="{ background: peClassConfig[v.consultation.pe_classification]?.bg, color: peClassConfig[v.consultation.pe_classification]?.color, borderColor: peClassConfig[v.consultation.pe_classification]?.border }">
                                        <span class="text-xs leading-none">CLASS</span>
                                        <span class="text-2xl leading-tight">{{ v.consultation.pe_classification }}</span>
                                    </div>
                                    <p class="text-xs font-semibold mt-1 leading-tight" :style="{ color: peClassConfig[v.consultation.pe_classification]?.color }">
                                        {{ peClassConfig[v.consultation.pe_classification]?.label }}
                                    </p>
                                </div>
                                <div class="flex-1 space-y-2 min-w-0">
                                    <p v-if="v.consultation.essentially_normal" class="text-sm font-bold text-emerald-700">✓ Essentially Normal Findings</p>
                                    <p v-if="v.consultation.pe_findings" class="text-xs text-slate-600 whitespace-pre-line leading-relaxed">{{ v.consultation.pe_findings }}</p>
                                    <div v-if="v.consultation.icd10_code" class="flex items-center gap-2 text-xs">
                                        <span class="font-mono font-bold text-slate-700 bg-white border border-slate-200 px-2 py-0.5 rounded">{{ v.consultation.icd10_code }}</span>
                                        <span class="text-slate-500">{{ v.consultation.icd10_description }}</span>
                                    </div>
                                    <p v-if="v.consultation.finalized_at" class="text-xs text-slate-400">Finalized: {{ v.consultation.finalized_at }}</p>
                                </div>
                                <a :href="route('doctor.print', v.id)" target="_blank" class="flex-shrink-0">
                                    <Button variant="outline" size="sm" class="gap-1.5 text-xs whitespace-nowrap">
                                        <Printer class="w-3 h-3"/> Print MER
                                    </Button>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Billing / Services -->
                    <div v-if="v.services?.length > 0" class="p-4 border-b border-slate-100">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1.5 mb-3">
                            <ReceiptText class="w-3.5 h-3.5"/> Services & Billing
                        </p>
                        <div class="rounded-xl border border-slate-200 overflow-hidden">
                            <div v-for="svc in v.services" :key="svc.code"
                                class="flex items-center justify-between px-4 py-2 border-b border-slate-100 last:border-0 text-xs bg-white hover:bg-slate-50">
                                <span class="font-mono text-slate-400 w-20 flex-shrink-0">{{ svc.code }}</span>
                                <span class="flex-1 text-slate-700 font-medium">{{ svc.name }}</span>
                                <span class="font-bold text-slate-800 flex-shrink-0">₱{{ Number(svc.price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div class="flex items-center justify-between px-4 py-2.5 bg-slate-50 border-t border-slate-200">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-slate-600">Total</span>
                                    <span :class="['text-xs font-bold px-2 py-0.5 rounded-full capitalize',
                                        v.invoice_status === 'paid'    ? 'bg-emerald-100 text-emerald-700' :
                                        v.invoice_status === 'partial' ? 'bg-amber-100 text-amber-700'    : 'bg-red-100 text-red-700']">
                                        {{ v.invoice_status }}
                                    </span>
                                </div>
                                <span class="text-sm font-black text-slate-800">₱{{ Number(v.total_amount).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action buttons row -->
                    <div class="px-4 py-3 bg-slate-50 flex items-center gap-2 flex-wrap">
                        <a v-if="v.consultation" :href="route('doctor.print', v.id)" target="_blank">
                            <Button variant="outline" size="sm" class="text-xs gap-1.5 h-7">
                                <Printer class="w-3 h-3"/> Med Exam Report
                            </Button>
                        </a>
                        <a v-if="v.imaging" :href="route('xray.print', v.id)" target="_blank">
                            <Button variant="outline" size="sm" class="text-xs gap-1.5 h-7">
                                <Printer class="w-3 h-3"/> X-Ray Report
                            </Button>
                        </a>
                        <a v-if="v.drug_test" :href="route('drug-test.print', v.id)" target="_blank">
                            <Button variant="outline" size="sm" class="text-xs gap-1.5 h-7">
                                <Printer class="w-3 h-3"/> Drug Test
                            </Button>
                        </a>
                        <span class="ml-auto text-xs text-slate-400">{{ v.visit_date_full ?? v.visit_date }}</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 3 · LAB RESULTS — grouped by visit / case      -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'labs'">
            <!-- Filter pills -->
            <div class="flex items-center gap-2 mb-5 flex-wrap">
                <button v-for="f in [
                    { val: 'all',      label: 'All Results'     },
                    { val: 'abnormal', label: '⚠ Abnormal Only' },
                    ...labCategories.map(c => ({ val: c, label: catLabel[c] ?? c }))
                ]" :key="f.val"
                    @click="labFilter = f.val"
                    class="px-3 py-1.5 text-xs font-semibold rounded-full border transition-all capitalize"
                    :style="labFilter === f.val
                        ? 'background:#1B4F9B;color:white;border-color:#1B4F9B;'
                        : 'border-color:#e2e8f0;color:#64748b;background:white;'">
                    {{ f.label }}
                </button>
                <span class="ml-auto text-xs text-slate-400">{{ allLabResults.length }} total results</span>
            </div>

            <div v-if="!labVisitGroups.length" class="text-center py-16 text-slate-400">
                <FlaskConical class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No lab results found</p>
            </div>

            <div class="space-y-4">
                <div v-for="grp in labVisitGroups" :key="grp.visit_id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

                    <!-- Visit header -->
                    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                :style="{ background: VISIT_TYPE_BADGE[grp.visit_type]?.bg, color: VISIT_TYPE_BADGE[grp.visit_type]?.color }">
                                {{ VISIT_TYPE_BADGE[grp.visit_type]?.label }}
                            </span>
                            <span class="text-sm font-semibold text-slate-700">{{ grp.visit_date }}</span>
                            <span v-if="grp.case_number" class="text-xs font-mono text-slate-400 bg-white border border-slate-200 px-2 py-0.5 rounded">
                                CN: {{ grp.case_number }}
                            </span>
                            <span v-if="grp.employer" class="text-xs font-semibold text-purple-700">{{ grp.employer }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="grp.has_abnormal"
                                class="text-xs font-bold text-red-600 bg-red-50 border border-red-200 px-2 py-0.5 rounded-full flex items-center gap-1">
                                <AlertTriangle class="w-3 h-3"/> {{ grp.abnormal_count }} Abnormal
                            </span>
                            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full capitalize',
                                grp.lab_status === 'released' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                {{ grp.lab_status }}
                            </span>
                            <span v-if="grp.result_date" class="text-xs text-slate-400">Released: {{ grp.result_date }}</span>
                        </div>
                    </div>

                    <!-- Category sections -->
                    <div v-for="cat in grp.categories" :key="cat.cat" class="border-b border-slate-100 last:border-0">
                        <div class="px-5 py-2 bg-slate-50/30 flex items-center gap-3">
                            <span class="text-xs font-black text-slate-400 uppercase tracking-wider">{{ catLabel[cat.cat] ?? cat.cat }}</span>
                            <span class="flex-1 border-t border-slate-200"/>
                            <span class="text-xs text-slate-400">{{ cat.results.length }} test{{ cat.results.length !== 1 ? 's' : '' }}</span>
                        </div>
                        <table class="w-full text-xs">
                            <tbody>
                                <tr v-for="r in cat.results" :key="r.test_code"
                                    :class="['border-b border-slate-50 last:border-0 transition-colors', r.is_abnormal ? 'bg-red-50/40' : 'hover:bg-slate-50']">
                                    <td class="px-5 py-2.5 font-medium text-slate-700" style="width:240px">{{ r.test_name }}</td>
                                    <td class="px-4 py-2.5" style="width:160px">
                                        <div class="flex items-center gap-1.5">
                                            <span :class="['font-black text-sm', r.is_abnormal ? 'text-red-600' : 'text-slate-800']">{{ r.result_value }}</span>
                                            <span v-if="r.unit" class="text-slate-400">{{ r.unit }}</span>
                                            <span v-if="r.flag" class="font-black text-red-600 bg-red-100 px-1.5 py-0.5 rounded">{{ r.flag }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ r.normal_range }}</td>
                                    <td class="px-4 py-2.5">
                                        <span v-if="r.is_abnormal" class="text-red-600 font-semibold flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3"/> Abnormal
                                        </span>
                                        <span v-else class="text-emerald-600 flex items-center gap-1">
                                            <CheckCircle2 class="w-3 h-3"/> Normal
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 4 · IMAGING                                    -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'imaging'">
            <div v-if="!allImaging.length" class="text-center py-16 text-slate-400">
                <ScanLine class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No imaging records found</p>
            </div>
            <div v-else class="space-y-3">
                <div v-for="img in allImaging" :key="img.visit_id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 flex items-start gap-5">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-2">
                                <span class="text-base font-black text-slate-800">{{ img.imaging_type }}</span>
                                <span v-if="img.is_provisional"
                                    class="text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">
                                    Provisional
                                </span>
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full capitalize',
                                    img.status === 'released' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                    {{ img.status }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs mb-3 flex-wrap">
                                <span class="font-bold px-2.5 py-0.5 rounded-full"
                                    :style="{ background: VISIT_TYPE_BADGE[img.visit_type]?.bg, color: VISIT_TYPE_BADGE[img.visit_type]?.color }">
                                    {{ VISIT_TYPE_BADGE[img.visit_type]?.label }}
                                </span>
                                <span class="text-slate-400">{{ img.visit_date }}</span>
                                <span v-if="img.case_number" class="font-mono text-slate-400 bg-slate-100 px-2 py-0.5 rounded">CN: {{ img.case_number }}</span>
                            </div>
                            <div v-if="img.impression"
                                class="bg-slate-900 text-white rounded-xl px-4 py-3 font-black text-sm uppercase tracking-wide">
                                {{ img.impression }}
                            </div>
                            <div v-else class="bg-slate-50 rounded-xl px-4 py-3 text-sm text-slate-400 italic border border-slate-200">
                                No impression recorded
                            </div>
                        </div>
                        <a :href="route('xray.print', img.visit_id)" target="_blank" class="flex-shrink-0">
                            <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                <Printer class="w-3 h-3"/> Print Report
                            </Button>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 5 · DRUG TESTS                                 -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'drug_tests'">
            <div v-if="!allDrugTests.length" class="text-center py-16 text-slate-400">
                <TestTube class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No drug test records found</p>
            </div>
            <div v-else class="space-y-3">
                <div v-for="dt in allDrugTests" :key="dt.visit_id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 flex items-start gap-4">
                        <!-- Large result badge -->
                        <div v-if="dt.result" class="flex-shrink-0">
                            <span :class="['text-sm font-black px-4 py-2.5 rounded-xl uppercase block text-center min-w-[90px]', drugResultColor[dt.result]]">
                                {{ dt.result }}
                            </span>
                        </div>
                        <div v-if="dt.result" class="w-px bg-slate-200 self-stretch flex-shrink-0"/>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                    :style="{ background: VISIT_TYPE_BADGE[dt.visit_type]?.bg, color: VISIT_TYPE_BADGE[dt.visit_type]?.color }">
                                    {{ VISIT_TYPE_BADGE[dt.visit_type]?.label }}
                                </span>
                                <span class="text-xs text-slate-500">{{ dt.visit_date }}</span>
                                <span v-if="dt.case_number" class="text-xs font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">CN: {{ dt.case_number }}</span>
                                <span v-if="dt.employer" class="text-xs font-semibold text-purple-700">{{ dt.employer }}</span>
                            </div>
                            <p v-if="dt.drugs_label" class="text-sm text-slate-600 mb-2">{{ dt.drugs_label }}</p>
                            <div class="flex items-center gap-4 text-xs text-slate-400">
                                <span v-if="dt.code_number" class="font-mono">
                                    Code: <span class="font-black text-slate-600">{{ dt.code_number }}</span>
                                </span>
                                <span v-if="dt.specimen_date">Specimen: {{ dt.specimen_date }}</span>
                            </div>
                        </div>

                        <a :href="route('drug-test.print', dt.visit_id)" target="_blank" class="flex-shrink-0">
                            <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                <Printer class="w-3 h-3"/> Print
                            </Button>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 6 · CONSULTATIONS                              -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'consultations'">
            <div v-if="!allConsultations.length" class="text-center py-16 text-slate-400">
                <Stethoscope class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No finalized consultations found</p>
            </div>
            <div v-else class="space-y-3">
                <div v-for="c in allConsultations" :key="c.visit_id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5">
                        <div class="flex items-start gap-4 mb-4">
                            <!-- PE Class block -->
                            <div v-if="c.pe_classification" class="flex-shrink-0 text-center">
                                <div class="w-16 h-16 rounded-2xl flex flex-col items-center justify-center font-black border-2"
                                    :style="{ background: peClassConfig[c.pe_classification]?.bg, color: peClassConfig[c.pe_classification]?.color, borderColor: peClassConfig[c.pe_classification]?.border }">
                                    <span class="text-xs leading-none opacity-70">CLASS</span>
                                    <span class="text-3xl leading-none">{{ c.pe_classification }}</span>
                                </div>
                                <p class="text-xs font-bold mt-1.5 leading-tight" :style="{ color: peClassConfig[c.pe_classification]?.color }">
                                    {{ peClassConfig[c.pe_classification]?.label }}
                                </p>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap mb-1">
                                    <span class="text-xs font-bold px-2.5 py-0.5 rounded-full"
                                        :style="{ background: VISIT_TYPE_BADGE[c.visit_type]?.bg, color: VISIT_TYPE_BADGE[c.visit_type]?.color }">
                                        {{ VISIT_TYPE_BADGE[c.visit_type]?.label }}
                                    </span>
                                    <span v-if="c.employer" class="text-xs font-semibold text-purple-700">{{ c.employer }}</span>
                                    <span v-if="c.case_number" class="text-xs font-mono text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">CN: {{ c.case_number }}</span>
                                </div>
                                <p class="text-xs text-slate-400">{{ c.visit_date }}</p>
                                <p v-if="c.finalized_at" class="text-xs text-slate-400">Finalized: {{ c.finalized_at }}</p>
                            </div>

                            <a :href="route('doctor.print', c.visit_id)" target="_blank" class="flex-shrink-0">
                                <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                    <Printer class="w-3 h-3"/> Medical Exam Report
                                </Button>
                            </a>
                        </div>

                        <div class="space-y-2">
                            <div v-if="c.essentially_normal"
                                class="font-bold text-emerald-700 bg-emerald-50 px-4 py-2.5 rounded-xl border border-emerald-200 text-sm flex items-center gap-2">
                                <CheckCircle2 class="w-4 h-4"/> Essentially Normal Findings
                            </div>
                            <div v-if="c.pe_findings"
                                class="text-sm text-slate-700 bg-slate-50 px-4 py-3 rounded-xl border border-slate-200 whitespace-pre-line leading-relaxed">
                                {{ c.pe_findings }}
                            </div>
                            <div v-if="c.icd10_code" class="flex items-center gap-2">
                                <span class="font-mono font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-lg text-sm">{{ c.icd10_code }}</span>
                                <span class="text-slate-500 text-sm">{{ c.icd10_description }}</span>
                            </div>
                            <p v-if="c.doctor_notes" class="text-xs text-slate-500 italic border-l-2 border-slate-200 pl-3">{{ c.doctor_notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- TAB 7 · PRESCRIPTIONS                              -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div v-if="activeTab === 'prescriptions'">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs text-slate-400">{{ prescriptions.length }} prescription{{ prescriptions.length !== 1 ? 's' : '' }} on record</span>
                <a :href="route('prescriptions.create') + '?patient_id=' + patient.id">
                    <Button size="sm" class="gap-2 text-white" style="background:#8B5CF6;">
                        <Pill class="w-3.5 h-3.5"/> Write Prescription
                    </Button>
                </a>
            </div>

            <div v-if="!prescriptions.length" class="text-center py-16 text-slate-400">
                <Pill class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No prescriptions yet</p>
                <p class="text-sm mt-1">Prescriptions from consultations will appear here</p>
            </div>

            <div v-else class="space-y-3">
                <div v-for="rx in prescriptions" :key="rx.id"
                    class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4"
                    :class="rx.is_controlled ? 'border-amber-200' : ''">
                    <div class="flex items-start gap-4">
                        <!-- Rx number -->
                        <div class="flex-shrink-0 text-center min-w-[80px]">
                            <p class="text-xs text-slate-400">Rx No.</p>
                            <p class="text-sm font-black font-mono text-blue-700">{{ rx.rx_number }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ rx.rx_date }}</p>
                            <span v-if="rx.is_controlled"
                                class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 inline-block mt-1">
                                S2
                            </span>
                        </div>

                        <div class="w-px bg-slate-200 self-stretch flex-shrink-0"/>

                        <!-- Medications -->
                        <div class="flex-1 min-w-0">
                            <div v-for="item in rx.items" :key="item.drug" class="flex items-baseline gap-1.5 mb-1 last:mb-0">
                                <span class="text-sm font-bold text-slate-800">{{ item.drug }}</span>
                                <span v-if="item.dosage" class="text-xs text-slate-500">{{ item.dosage }}</span>
                                <span v-if="item.form" class="text-xs text-slate-400">· {{ item.form }}</span>
                            </div>
                            <p class="text-xs text-slate-400 mt-2">Dr. {{ rx.doctor_name }}</p>
                        </div>

                        <a :href="route('prescriptions.print', rx.id)" target="_blank" class="flex-shrink-0">
                            <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                <Printer class="w-3 h-3"/> Print
                            </Button>
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>
