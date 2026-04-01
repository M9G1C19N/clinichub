<script setup>
import { ref, computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
    User, Activity, FlaskConical, ScanLine, TestTube,
    Stethoscope, FileText, Calendar, Edit, Phone,
    MapPin, Heart, AlertTriangle, CheckCircle2,
    ChevronDown, ChevronRight, Printer, Plus,
    ClipboardList, Building2, Clock, Pill,
} from 'lucide-vue-next'
import { VISIT_TYPE_BADGE, VISIT_TYPE_LABEL, IS_PE_TYPE } from '@/config/visitTypes.js'

const props = defineProps({
    patient:       Object,
    visitHistory:  Array,
    stats:         Object,
    prescriptions: { type: Array, default: () => [] },
})
const activeTab   = ref('overview')
const expandedVisits = ref(new Set())

function toggleVisit(id) {
    if (expandedVisits.value.has(id)) expandedVisits.value.delete(id)
    else expandedVisits.value.add(id)
}

// ── Computed: filter visits by type ──────────────────
const labFilter     = ref('all')
const historySearch = ref('')

const filteredHistory = computed(() => {
    let h = props.visitHistory
    if (historySearch.value) {
        const s = historySearch.value.toLowerCase()
        h = h.filter(v =>
            v.visit_date.toLowerCase().includes(s) ||
            VISIT_TYPE_LABEL[v.visit_type]?.toLowerCase().includes(s) ||
            (v.employer_company?.toLowerCase().includes(s)) ||
            (v.case_number?.toLowerCase().includes(s))
        )
    }
    return h
})

// All lab results across all visits flattened
const allLabResults = computed(() => {
    const results = []
    props.visitHistory.forEach(v => {
        if (!v.lab?.results) return
        v.lab.results.forEach(r => {
            results.push({
                ...r,
                visit_date:   v.visit_date,
                visit_type:   v.visit_type,
                visit_id:     v.id,
                case_number:  v.case_number,
                lab_status:   v.lab.status,
            })
        })
    })
    return results
})

const labCategories = computed(() => [...new Set(allLabResults.value.map(r => r.category))])

const filteredLabResults = computed(() => {
    if (labFilter.value === 'all') return allLabResults.value
    if (labFilter.value === 'abnormal') return allLabResults.value.filter(r => r.is_abnormal)
    return allLabResults.value.filter(r => r.category === labFilter.value)
})

// All imaging
const allImaging = computed(() =>
    props.visitHistory.filter(v => v.imaging).map(v => ({ ...v.imaging, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id, case_number: v.case_number }))
)

// All drug tests
const allDrugTests = computed(() =>
    props.visitHistory.filter(v => v.drug_test).map(v => ({ ...v.drug_test, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id, employer: v.employer_company }))
)

// All consultations
const allConsultations = computed(() =>
    props.visitHistory.filter(v => v.consultation?.is_finalized).map(v => ({ ...v.consultation, visit_date: v.visit_date, visit_type: v.visit_type, visit_id: v.id, employer: v.employer_company }))
)

// Helpers
const resultStatusColor = (status) => ({
    released:   'text-emerald-600',
    processing: 'text-blue-600',
    pending:    'text-amber-600',
    none:       'text-slate-400',
}[status] ?? 'text-slate-400')

const peClassColor = {
    A: 'bg-emerald-100 text-emerald-700',
    B: 'bg-blue-100 text-blue-700',
    C: 'bg-amber-100 text-amber-700',
    D: 'bg-orange-100 text-orange-700',
    E: 'bg-red-100 text-red-700',
}

const drugResultColor = {
    negative:    'bg-emerald-100 text-emerald-700',
    positive:    'bg-red-100 text-red-700',
    cancelled:   'bg-slate-100 text-slate-600',
    diluted:     'bg-amber-100 text-amber-700',
    substituted: 'bg-purple-100 text-purple-700',
    adulterated: 'bg-rose-100 text-rose-700',
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
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div class="flex items-center gap-3">
                        <!-- Photo -->
                        <div class="w-10 h-10 rounded-xl overflow-hidden border-2 border-slate-200 flex-shrink-0">
                            <img v-if="patient.photo_path" :src="patient.photo_path"
                                class="w-full h-full object-cover"/>
                            <div v-else class="w-full h-full flex items-center justify-center text-white font-bold text-sm"
                                style="background:#1B4F9B">
                                {{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-xl font-bold text-slate-800">{{ patient.full_name }}</h1>
                                <span class="text-xs font-mono font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                    {{ patient.patient_code }}
                                </span>
                                <span v-if="!patient.is_active"
                                    class="text-xs font-bold px-2 py-0.5 rounded bg-red-100 text-red-600">
                                    ARCHIVED
                                </span>
                            </div>
                            <p class="text-slate-400 text-xs mt-0.5">
                                {{ patient.age_sex }} · {{ patient.date_of_birth }} · {{ patient.civil_status }}
                            </p>
                        </div>
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

        <!-- ── TAB NAVIGATION ─────────────────────────── -->
        <div class="flex items-center gap-1 mb-5 border-b">
            <button v-for="tab in [
                {key:'overview',      label:'Overview',      icon:User},
                {key:'visits',        label:`Visits (${stats.total_visits})`, icon:Calendar},
                {key:'labs',          label:`Lab Results (${stats.total_visits})`, icon:FlaskConical},
                {key:'imaging',       label:`Imaging (${allImaging.length})`,  icon:ScanLine},
                {key:'drug_tests',    label:`Drug Tests (${allDrugTests.length})`, icon:TestTube},
                {key:'consultations', label:`Consultations (${allConsultations.length})`, icon:Stethoscope},
                {key:'prescriptions', label:`Prescriptions (${prescriptions.length})`, icon:Pill}
            ]" :key="tab.key"
                @click="activeTab = tab.key"
                :class="['flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-all -mb-px',
                    activeTab === tab.key
                        ? 'border-blue-600 text-blue-700'
                        : 'border-transparent text-slate-500 hover:text-slate-700']">
                <component :is="tab.icon" class="w-4 h-4"/>
                {{ tab.label }}
            </button>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 1: OVERVIEW                             -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'overview'" class="grid grid-cols-3 gap-5">

            <!-- LEFT: Patient Info -->
            <div class="space-y-4">

                <!-- Photo + Basic -->
                <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="h-2" style="background:linear-gradient(90deg,#0F2044,#1B4F9B,#2E75B6)"></div>
                    <div class="p-5">
                        <div class="flex flex-col items-center text-center mb-4">
                            <div class="w-20 h-20 rounded-2xl overflow-hidden border-4 border-white shadow-md mb-3">
                                <img v-if="patient.photo_path" :src="patient.photo_path"
                                    class="w-full h-full object-cover"/>
                                <div v-else class="w-full h-full flex items-center justify-center text-white text-2xl font-black"
                                    style="background:#1B4F9B">
                                    {{ patient.first_name?.charAt(0) }}{{ patient.last_name?.charAt(0) }}
                                </div>
                            </div>
                            <h2 class="text-lg font-black text-slate-800">{{ patient.full_name }}</h2>
                            <p class="text-sm text-slate-500">{{ patient.age_sex }} · {{ patient.civil_status }}</p>
                            <span class="mt-2 text-xs font-mono font-bold text-blue-700 bg-blue-50 px-3 py-1 rounded-full border border-blue-200">
                                {{ patient.patient_code }}
                            </span>
                        </div>

                        <!-- Quick stats -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="text-center p-2 bg-slate-50 rounded-xl">
                                <p class="text-lg font-black text-slate-800">{{ stats.total_visits }}</p>
                                <p class="text-xs text-slate-400">Visits</p>
                            </div>
                            <div class="text-center p-2 bg-slate-50 rounded-xl">
                                <p class="text-lg font-black" :class="stats.lab_abnormals > 0 ? 'text-red-600' : 'text-emerald-600'">
                                    {{ stats.lab_abnormals }}
                                </p>
                                <p class="text-xs text-slate-400">Abnormals</p>
                            </div>
                            <div class="text-center p-2 bg-slate-50 rounded-xl">
                                <p class="text-lg font-black text-purple-600">{{ stats.pe_count }}</p>
                                <p class="text-xs text-slate-400">PE Exams</p>
                            </div>
                        </div>

                        <!-- Blood type + philhealth badges -->
                        <div class="flex items-center gap-2 justify-center flex-wrap">
                            <span v-if="patient.blood_type"
                                class="text-xs font-bold px-2.5 py-1 rounded-full bg-red-100 text-red-700 flex items-center gap-1">
                                <Heart class="w-3 h-3"/> {{ patient.blood_type }}
                            </span>
                            <span v-if="patient.philhealth_number"
                                class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-100 text-blue-700">
                                PhilHealth: {{ patient.philhealth_number }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Contact & Personal -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Contact</h3>
                    <div v-if="patient.contact_number" class="flex items-center gap-2 text-sm">
                        <Phone class="w-4 h-4 text-slate-400 flex-shrink-0"/>
                        <span>{{ patient.contact_number }}</span>
                    </div>
                    <div v-if="patient.email" class="flex items-center gap-2 text-sm">
                        <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="truncate">{{ patient.email }}</span>
                    </div>
                    <div v-if="patient.address" class="flex items-start gap-2 text-sm">
                        <MapPin class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5"/>
                        <span class="text-slate-600">{{ patient.address }}</span>
                    </div>
                    <Separator v-if="patient.emergency_contact_name"/>
                    <div v-if="patient.emergency_contact_name" class="text-xs space-y-1">
                        <p class="font-semibold text-slate-500">Emergency Contact</p>
                        <p class="font-bold text-slate-700">{{ patient.emergency_contact_name }}</p>
                        <p class="text-slate-500">{{ patient.emergency_contact_number }}</p>
                    </div>
                </div>

                <!-- Companies -->
                <div v-if="stats.companies?.length > 0" class="bg-card rounded-xl border shadow-sm p-4 space-y-2">
                    <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Companies</h3>
                    <div v-for="c in stats.companies" :key="c"
                        class="flex items-center gap-2 text-sm">
                        <Building2 class="w-3.5 h-3.5 text-slate-400"/>
                        <span class="font-semibold text-purple-700">{{ c }}</span>
                    </div>
                </div>
            </div>

            <!-- CENTER + RIGHT: Activity -->
            <div class="col-span-2 space-y-4">

                <!-- Last visit summary -->
                <div v-if="visitHistory.length > 0" class="bg-card rounded-xl border shadow-sm p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-700">Latest Visit</h3>
                        <span class="text-xs text-slate-400">{{ visitHistory[0].visit_date }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Visit type + services -->
                        <div>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                :style="{
                                    background: VISIT_TYPE_BADGE[visitHistory[0].visit_type]?.bg,
                                    color: VISIT_TYPE_BADGE[visitHistory[0].visit_type]?.color,
                                }">
                                {{ VISIT_TYPE_BADGE[visitHistory[0].visit_type]?.label }}
                            </span>
                            <div v-if="visitHistory[0].employer_company" class="text-sm font-semibold text-purple-700 mt-1.5">
                                {{ visitHistory[0].employer_company }}
                            </div>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="svc in visitHistory[0].services?.slice(0,5)" :key="svc.code"
                                    class="text-xs font-mono bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">
                                    {{ svc.code }}
                                </span>
                            </div>
                        </div>
                        <!-- Results summary -->
                        <div class="space-y-2">
                            <div v-if="visitHistory[0].consultation" class="flex items-center gap-2 text-sm">
                                <Stethoscope class="w-4 h-4 text-slate-400"/>
                                <span v-if="visitHistory[0].consultation.pe_classification"
                                    :class="['text-xs font-bold px-2 py-0.5 rounded', peClassColor[visitHistory[0].consultation.pe_classification]]">
                                    CLASS {{ visitHistory[0].consultation.pe_classification }}
                                </span>
                                <span v-else-if="visitHistory[0].consultation.essentially_normal" class="text-xs text-emerald-600 font-semibold">
                                    Normal Findings
                                </span>
                            </div>
                            <div v-if="visitHistory[0].lab" class="flex items-center gap-2 text-sm">
                                <FlaskConical class="w-4 h-4 text-slate-400"/>
                                <span :class="resultStatusColor(visitHistory[0].lab.status)" class="font-semibold text-xs capitalize">
                                    Lab {{ visitHistory[0].lab.status }}
                                </span>
                                <span v-if="visitHistory[0].lab.has_abnormal"
                                    class="text-xs font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded">
                                    {{ visitHistory[0].lab.abnormal_count }} Abnormal
                                </span>
                            </div>
                            <div v-if="visitHistory[0].drug_test" class="flex items-center gap-2 text-sm">
                                <TestTube class="w-4 h-4 text-slate-400"/>
                                <span v-if="visitHistory[0].drug_test.result"
                                    :class="['text-xs font-bold px-2 py-0.5 rounded uppercase', drugResultColor[visitHistory[0].drug_test.result]]">
                                    {{ visitHistory[0].drug_test.result }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visit timeline -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Visit Timeline</h3>
                        </div>
                        <Button variant="outline" size="sm" class="text-xs h-7" @click="activeTab = 'visits'">
                            View all
                        </Button>
                    </div>
                    <div class="p-4">
                        <div v-if="visitHistory.length === 0" class="text-center py-8 text-slate-400 text-sm">
                            No visits yet
                        </div>
                        <div class="relative">
                            <div class="absolute left-4 top-0 bottom-0 w-px bg-slate-200"></div>
                            <div v-for="v in visitHistory.slice(0, 6)" :key="v.id"
                                class="relative flex items-start gap-4 mb-4 pl-10">
                                <!-- Dot -->
                                <div class="absolute left-2.5 w-3 h-3 rounded-full border-2 border-white mt-1"
                                    :style="{ background: VISIT_TYPE_BADGE[v.visit_type]?.color ?? '#1B4F9B' }"></div>
                                <!-- Content -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                            :style="{
                                                background: VISIT_TYPE_BADGE[v.visit_type]?.bg,
                                                color: VISIT_TYPE_BADGE[v.visit_type]?.color,
                                            }">
                                            {{ VISIT_TYPE_BADGE[v.visit_type]?.label }}
                                        </span>
                                        <span v-if="v.case_number" class="text-xs font-mono text-slate-400">CN: {{ v.case_number }}</span>
                                        <span v-if="v.consultation?.pe_classification"
                                            :class="['text-xs font-bold px-2 py-0.5 rounded', peClassColor[v.consultation.pe_classification]]">
                                            Class {{ v.consultation.pe_classification }}
                                        </span>
                                        <span v-if="v.lab?.has_abnormal"
                                            class="text-xs font-bold text-red-600 flex items-center gap-1">
                                            <AlertTriangle class="w-3 h-3"/> {{ v.lab.abnormal_count }} abnormal
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ v.visit_date }}
                                        <span v-if="v.employer_company" class="text-purple-600 font-semibold">
                                            · {{ v.employer_company }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Latest vitals -->
                <div v-if="visitHistory.find(v => v.vitals)" class="bg-card rounded-xl border shadow-sm p-5">
                    <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Latest Vitals</h3>
                    <div class="grid grid-cols-4 gap-3">
                        <div v-for="vital in [
                            {label:'Weight', val: visitHistory.find(v=>v.vitals)?.vitals?.weight_kg, unit:'kg'},
                            {label:'Height', val: visitHistory.find(v=>v.vitals)?.vitals?.height_cm ? (visitHistory.find(v=>v.vitals).vitals.height_cm/100).toFixed(2) : null, unit:'m'},
                            {label:'BMI',    val: visitHistory.find(v=>v.vitals)?.vitals?.bmi, unit:''},
                            {label:'BP',     val: visitHistory.find(v=>v.vitals)?.vitals?.bp, unit:'mmHg'},
                        ]" :key="vital.label"
                            class="text-center p-3 bg-slate-50 rounded-xl border border-slate-100">
                            <p class="text-lg font-black text-slate-800">{{ vital.val ?? '—' }}</p>
                            <p class="text-xs text-slate-400">{{ vital.label }}</p>
                            <p v-if="vital.unit" class="text-xs text-slate-300">{{ vital.unit }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 2: VISIT HISTORY                        -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'visits'" class="space-y-3">
            <!-- Search -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="historySearch" placeholder="Search visits..." class="w-full h-9 pl-9 pr-4 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white"/>
                </div>
                <span class="text-xs text-slate-400">{{ filteredHistory.length }} visits</span>
            </div>

            <div v-if="filteredHistory.length === 0" class="text-center py-16 text-slate-400">
                <Calendar class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p>No visits found</p>
            </div>

            <!-- Visit cards — expandable -->
            <div v-for="v in filteredHistory" :key="v.id"
                class="bg-card rounded-xl border shadow-sm overflow-hidden">

                <!-- Visit header — click to expand -->
                <div class="flex items-center gap-4 p-4 cursor-pointer hover:bg-slate-50 transition-colors"
                    @click="toggleVisit(v.id)">
                    <!-- Date column -->
                    <div class="text-center w-16 flex-shrink-0">
                        <div class="text-xs text-slate-400">{{ v.visit_date.split(' ')[1] }} {{ v.visit_date.split(' ')[2] }}</div>
                        <div class="text-lg font-black text-slate-800">{{ v.visit_date.split(' ')[0] }}</div>
                        <div class="text-xs text-slate-500">{{ v.visit_date.split(',')[1]?.trim() }}</div>
                    </div>

                    <div class="w-px h-10 bg-slate-200 flex-shrink-0"></div>

                    <!-- Visit info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <span class="text-sm font-semibold px-2.5 py-0.5 rounded-full"
                                :style="{
                                    background: VISIT_TYPE_BADGE[v.visit_type]?.bg,
                                    color: VISIT_TYPE_BADGE[v.visit_type]?.color,
                                }">
                                {{ VISIT_TYPE_BADGE[v.visit_type]?.label }}
                            </span>
                            <span v-if="v.case_number" class="text-xs font-mono text-slate-400 bg-slate-100 px-2 py-0.5 rounded">
                                CN: {{ v.case_number }}
                            </span>
                            <span v-if="v.is_field_visit" class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                Field Visit
                            </span>
                        </div>
                        <p v-if="v.employer_company" class="text-sm font-semibold text-purple-700">{{ v.employer_company }}</p>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <span v-for="svc in v.services?.slice(0,6)" :key="svc.code"
                                class="text-xs font-mono bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded">
                                {{ svc.code }}
                            </span>
                            <span v-if="v.services?.length > 6" class="text-xs text-slate-400">+{{ v.services.length - 6 }} more</span>
                        </div>
                    </div>

                    <!-- Status badges -->
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <!-- Lab -->
                        <div v-if="v.lab" class="flex items-center gap-1 text-xs"
                            :class="v.lab.status === 'released' ? 'text-emerald-600' : 'text-amber-600'">
                            <FlaskConical class="w-3.5 h-3.5"/>
                            <span v-if="v.lab.has_abnormal" class="font-bold text-red-600">{{ v.lab.abnormal_count }}⚠</span>
                        </div>
                        <!-- X-Ray -->
                        <div v-if="v.imaging" class="flex items-center gap-1 text-xs"
                            :class="v.imaging.status === 'released' ? 'text-emerald-600' : 'text-amber-600'">
                            <ScanLine class="w-3.5 h-3.5"/>
                        </div>
                        <!-- Drug -->
                        <div v-if="v.drug_test" class="flex items-center gap-1 text-xs"
                            :class="v.drug_test.result === 'positive' ? 'text-red-600' : 'text-emerald-600'">
                            <TestTube class="w-3.5 h-3.5"/>
                        </div>
                        <!-- Classification -->
                        <span v-if="v.consultation?.pe_classification"
                            :class="['text-xs font-bold px-2 py-0.5 rounded', peClassColor[v.consultation.pe_classification]]">
                            CLASS {{ v.consultation.pe_classification }}
                        </span>
                        <!-- Invoice -->
                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full',
                            v.invoice_status === 'paid' ? 'bg-emerald-100 text-emerald-700' :
                            v.invoice_status === 'partial' ? 'bg-amber-100 text-amber-700' :
                            'bg-red-100 text-red-700']">
                            ₱{{ Number(v.total_amount).toLocaleString() }}
                        </span>

                        <component :is="expandedVisits.has(v.id) ? ChevronDown : ChevronRight"
                            class="w-4 h-4 text-slate-400 flex-shrink-0"/>
                    </div>
                </div>

                <!-- Expanded detail -->
                <div v-if="expandedVisits.has(v.id)"
                    class="border-t bg-slate-50/50 p-4 grid grid-cols-2 gap-4">

                    <!-- Vitals -->
                    <div v-if="v.vitals" class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                            <Activity class="w-3.5 h-3.5"/> Vitals
                        </h4>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div v-for="item in [
                                {l:'Weight', v:v.vitals.weight_kg+'kg'},
                                {l:'BMI', v:v.vitals.bmi},
                                {l:'BP', v:v.vitals.bp},
                                {l:'Pulse', v:v.vitals.pulse_rate},
                                {l:'Temp', v:v.vitals.temperature ? v.vitals.temperature+'°C' : ''},
                            ]" :key="item.l" class="bg-white rounded-lg p-2 border">
                                <p class="font-bold text-slate-700">{{ item.v || '—' }}</p>
                                <p class="text-slate-400">{{ item.l }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Lab Results Summary -->
                    <div v-if="v.lab?.results?.length > 0" class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                            <FlaskConical class="w-3.5 h-3.5"/>
                            Lab Results
                            <span v-if="v.lab.has_abnormal" class="text-red-600 text-xs font-bold">
                                ({{ v.lab.abnormal_count }} abnormal)
                            </span>
                        </h4>
                        <div class="space-y-1 max-h-40 overflow-y-auto">
                            <div v-for="r in v.lab.results" :key="r.test_code"
                                class="flex items-center justify-between text-xs bg-white rounded-lg px-2 py-1.5 border"
                                :class="r.is_abnormal ? 'border-red-200 bg-red-50' : ''">
                                <span class="text-slate-600">{{ r.test_name }}</span>
                                <div class="flex items-center gap-1.5">
                                    <span :class="r.is_abnormal ? 'font-bold text-red-600' : 'font-semibold text-slate-700'">
                                        {{ r.result_value }}
                                    </span>
                                    <span v-if="r.flag" class="text-xs font-bold text-red-600">{{ r.flag }}</span>
                                    <span class="text-slate-300 text-xs">{{ r.unit }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Imaging -->
                    <div v-if="v.imaging" class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                            <ScanLine class="w-3.5 h-3.5"/> Imaging
                        </h4>
                        <div class="bg-white rounded-lg border p-3 text-xs space-y-1">
                            <p class="font-semibold text-slate-700">{{ v.imaging.imaging_type }}</p>
                            <p v-if="v.imaging.impression" class="font-bold text-slate-800 uppercase">{{ v.imaging.impression }}</p>
                            <p v-if="v.imaging.is_provisional" class="text-amber-600 font-semibold">Provisional reading</p>
                            <a :href="route('xray.print', v.id)" target="_blank"
                                class="text-blue-600 font-semibold flex items-center gap-1 hover:underline">
                                <Printer class="w-3 h-3"/> Print Report
                            </a>
                        </div>
                    </div>

                    <!-- Drug Test -->
                    <div v-if="v.drug_test" class="space-y-2">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                            <TestTube class="w-3.5 h-3.5"/> Drug Test
                        </h4>
                        <div class="bg-white rounded-lg border p-3 text-xs space-y-1">
                            <p class="text-slate-500">{{ v.drug_test.drugs_label }}</p>
                            <span v-if="v.drug_test.result"
                                :class="['font-bold px-2 py-0.5 rounded uppercase', drugResultColor[v.drug_test.result]]">
                                {{ v.drug_test.result }}
                            </span>
                            <p class="text-slate-400">Code: {{ v.drug_test.code_number }}</p>
                        </div>
                    </div>

                    <!-- Consultation -->
                    <div v-if="v.consultation" class="space-y-2 col-span-2">
                        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-widest flex items-center gap-1.5">
                            <Stethoscope class="w-3.5 h-3.5"/> Doctor's Assessment
                        </h4>
                        <div class="bg-white rounded-lg border p-3 text-xs grid grid-cols-2 gap-3">
                            <div>
                                <p v-if="v.consultation.pe_classification"
                                    :class="['inline-block font-bold px-2 py-0.5 rounded mb-1', peClassColor[v.consultation.pe_classification]]">
                                    CLASS {{ v.consultation.pe_classification }} — {{ v.consultation.pe_classification_label }}
                                </p>
                                <p v-if="v.consultation.essentially_normal" class="font-bold text-emerald-600">***ESSENTIALLY NORMAL FINDINGS***</p>
                                <p v-if="v.consultation.pe_findings" class="text-slate-600 mt-1 whitespace-pre-line">{{ v.consultation.pe_findings }}</p>
                                <p v-if="v.consultation.icd10_code" class="mt-1">
                                    <span class="font-mono font-bold text-slate-700">{{ v.consultation.icd10_code }}</span>
                                    <span class="text-slate-500 ml-1">{{ v.consultation.icd10_description }}</span>
                                </p>
                            </div>
                            <div class="flex items-end justify-end">
                                <a :href="route('doctor.print', v.id)" target="_blank">
                                    <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                        <Printer class="w-3 h-3"/> Medical Exam Report
                                    </Button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 3: LAB RESULTS                          -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'labs'">
            <!-- Filters -->
            <div class="flex items-center gap-2 mb-4 flex-wrap">
                <button v-for="f in ['all', 'abnormal', ...labCategories]" :key="f"
                    @click="labFilter = f"
                    :class="['px-3 py-1.5 text-xs font-semibold rounded-lg border transition-all capitalize',
                        labFilter === f
                            ? 'text-white border-transparent'
                            : 'border-slate-200 text-slate-500 hover:border-slate-300']"
                    :style="labFilter === f ? 'background:#1B4F9B' : ''">
                    {{ f === 'abnormal' ? '⚠ Abnormal Only' : f }}
                </button>
                <span class="text-xs text-slate-400 ml-auto">{{ filteredLabResults.length }} results</span>
            </div>

            <div v-if="filteredLabResults.length === 0" class="text-center py-16 text-slate-400">
                <FlaskConical class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p>No lab results found</p>
            </div>

            <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background:#0F2044">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Test</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Category</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Result</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Reference</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Visit Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="r in filteredLabResults" :key="r.test_code + r.visit_id"
                            :class="['hover:bg-slate-50 transition-colors', r.is_abnormal ? 'bg-red-50/30' : '']">
                            <td class="px-4 py-3 font-semibold text-sm text-slate-800">{{ r.test_name }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500 capitalize">{{ r.category }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    <span :class="['font-bold text-sm', r.is_abnormal ? 'text-red-600' : 'text-slate-800']">
                                        {{ r.result_value }}
                                    </span>
                                    <span v-if="r.unit" class="text-xs text-slate-400">{{ r.unit }}</span>
                                    <span v-if="r.flag" class="text-xs font-black text-red-600 bg-red-100 px-1.5 py-0.5 rounded">{{ r.flag }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-400">{{ r.normal_range }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ r.visit_date }}</td>
                            <td class="px-4 py-3">
                                <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                    :style="{
                                        background: VISIT_TYPE_BADGE[r.visit_type]?.bg,
                                        color: VISIT_TYPE_BADGE[r.visit_type]?.color,
                                    }">
                                    {{ VISIT_TYPE_BADGE[r.visit_type]?.label }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 4: IMAGING                              -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'imaging'">
            <div v-if="allImaging.length === 0" class="text-center py-16 text-slate-400">
                <ScanLine class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p>No imaging records found</p>
            </div>
            <div v-else class="space-y-3">
                <div v-for="img in allImaging" :key="img.visit_id"
                    class="bg-card rounded-xl border shadow-sm p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-slate-800">{{ img.imaging_type }}</span>
                                <span v-if="img.is_provisional"
                                    class="text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                    Provisional
                                </span>
                                <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full',
                                    img.status === 'released' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700']">
                                    {{ img.status }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-400">
                                {{ img.visit_date }}
                                <span v-if="img.case_number" class="font-mono ml-2">CN: {{ img.case_number }}</span>
                            </p>
                        </div>
                        <a :href="route('xray.print', img.visit_id)" target="_blank">
                            <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                <Printer class="w-3 h-3"/> Print
                            </Button>
                        </a>
                    </div>
                    <div v-if="img.impression" class="bg-slate-50 rounded-lg p-3 font-bold text-slate-800 uppercase text-sm">
                        {{ img.impression }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 5: DRUG TESTS                           -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'drug_tests'">
            <div v-if="allDrugTests.length === 0" class="text-center py-16 text-slate-400">
                <TestTube class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p>No drug test records found</p>
            </div>
            <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background:#0F2044">
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Code</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Drugs</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Result</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Company</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="dt in allDrugTests" :key="dt.visit_id"
                            :class="['hover:bg-slate-50', dt.result === 'positive' ? 'bg-red-50/30' : '']">
                            <td class="px-4 py-3 font-mono font-bold text-slate-700 text-sm">{{ dt.code_number }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ dt.drugs_label }}</td>
                            <td class="px-4 py-3">
                                <span v-if="dt.result"
                                    :class="['text-xs font-bold px-2.5 py-1 rounded-full uppercase', drugResultColor[dt.result]]">
                                    {{ dt.result }}
                                </span>
                                <span v-else class="text-xs text-slate-400">Pending</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-purple-700 font-semibold">{{ dt.employer || '—' }}</td>
                            <td class="px-4 py-3 text-xs text-slate-500">{{ dt.visit_date }}</td>
                            <td class="px-4 py-3">
                                <a :href="route('drug-test.print', dt.visit_id)" target="_blank">
                                    <Button variant="outline" size="sm" class="text-xs gap-1 h-7">
                                        <Printer class="w-3 h-3"/> Print
                                    </Button>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 6: CONSULTATIONS                        -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'consultations'">
            <div v-if="allConsultations.length === 0" class="text-center py-16 text-slate-400">
                <Stethoscope class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p>No finalized consultations found</p>
            </div>
            <div v-else class="space-y-3">
                <div v-for="c in allConsultations" :key="c.visit_id"
                    class="bg-card rounded-xl border shadow-sm p-5">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <span v-if="c.pe_classification"
                                :class="['text-sm font-black px-3 py-1 rounded-xl', peClassColor[c.pe_classification]]">
                                CLASS {{ c.pe_classification }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded"
                                        :style="{
                                            background: VISIT_TYPE_BADGE[c.visit_type]?.bg,
                                            color: VISIT_TYPE_BADGE[c.visit_type]?.color,
                                        }">
                                        {{ VISIT_TYPE_BADGE[c.visit_type]?.label }}
                                    </span>
                                    <span v-if="c.employer" class="text-xs font-semibold text-purple-700">{{ c.employer }}</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Finalized: {{ c.finalized_at }}
                                </p>
                            </div>
                        </div>
                        <a :href="route('doctor.print', c.visit_id)" target="_blank">
                            <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                                <Printer class="w-3 h-3"/> Medical Exam Report
                            </Button>
                        </a>
                    </div>

                    <div class="space-y-2 text-sm">
                        <div v-if="c.essentially_normal"
                            class="font-bold text-emerald-700 bg-emerald-50 px-3 py-2 rounded-lg border border-emerald-200">
                            ***ESSENTIALLY NORMAL FINDINGS***
                        </div>
                        <div v-if="c.pe_findings" class="text-slate-700 whitespace-pre-line bg-slate-50 px-3 py-2 rounded-lg">
                            {{ c.pe_findings }}
                        </div>
                        <div v-if="c.icd10_code" class="flex items-center gap-2">
                            <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded">{{ c.icd10_code }}</span>
                            <span class="text-slate-600">{{ c.icd10_description }}</span>
                        </div>
                        <div v-if="c.doctor_notes" class="text-slate-500 text-xs italic border-l-2 border-slate-200 pl-3">
                            {{ c.doctor_notes }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- ═══════════════════════════════════════════ -->
        <!-- TAB 7: PRESCRIPTIONS                        -->
        <!-- ═══════════════════════════════════════════ -->
        <div v-if="activeTab === 'prescriptions'">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs text-slate-400">{{ prescriptions.length }} prescriptions on record</span>
                <a :href="route('prescriptions.create') + '?patient_id=' + patient.id">
                    <Button size="sm" class="gap-2 text-white" style="background:#8B5CF6;">
                        <Pill class="w-3.5 h-3.5"/> Write Prescription
                    </Button>
                </a>
            </div>

            <div v-if="prescriptions.length === 0" class="text-center py-16 text-slate-400">
                <Pill class="w-12 h-12 mx-auto mb-3 opacity-30"/>
                <p class="font-semibold">No prescriptions yet</p>
                <p class="text-sm mt-1">Prescriptions written from consultations will appear here</p>
            </div>

            <div v-else class="bg-card rounded-xl border shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr style="background:#0F2044">
                            <th class="text-left px-5 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Rx No.</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Medications</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Doctor</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Date</th>
                            <th class="text-left px-4 py-3 text-xs font-semibold text-white/70 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="rx in prescriptions" :key="rx.id"
                            class="hover:bg-slate-50 transition-colors group"
                            :class="rx.is_controlled ? 'bg-amber-50/20' : ''">
                            <td class="px-5 py-3.5 font-mono font-bold text-blue-700 text-sm">
                                {{ rx.rx_number }}
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="space-y-0.5">
                                    <p v-for="item in rx.items?.slice(0, 2)" :key="item.drug"
                                        class="text-xs font-semibold text-slate-700">
                                        {{ item.drug }}
                                        <span v-if="item.dosage" class="text-slate-500 font-normal">{{ item.dosage }}</span>
                                        <span v-if="item.form" class="text-slate-400 font-normal"> · {{ item.form }}</span>
                                    </p>
                                    <p v-if="rx.items?.length > 2" class="text-xs text-slate-400">
                                        +{{ rx.items.length - 2 }} more
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">{{ rx.doctor_name }}</td>
                            <td class="px-4 py-3.5 text-xs text-slate-500 whitespace-nowrap">{{ rx.rx_date }}</td>
                            <td class="px-4 py-3.5">
                                <span v-if="rx.is_controlled"
                                    class="text-xs font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                    S2
                                </span>
                                <span v-else class="text-xs text-slate-400">Regular</span>
                            </td>
                            <td class="px-4 py-3.5">
                                <a :href="route('prescriptions.print', rx.id)" target="_blank"
                                    class="opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                        <Printer class="w-3 h-3"/> Print
                                    </Button>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </AppLayout>
</template>
