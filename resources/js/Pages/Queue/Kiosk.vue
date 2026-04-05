<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { router, usePage, Head } from '@inertiajs/vue3'
import { printKioskCheckin } from '@/utils/printKioskCheckin.js'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { VISIT_TYPE_BADGE } from '@/config/visitTypes.js'
import {
    Search, X, ArrowLeft, CheckCircle2, Printer,
    FlaskConical, ScanLine, TestTube, Stethoscope, HeartPulse,
    UserCheck, Clock, AlertTriangle, ChevronRight,
    Monitor, Briefcase, RotateCcw, User, Shield, Heart, Zap,
    ClipboardList, LogOut, MapPin,
} from 'lucide-vue-next'

const props = defineProps({
    services: { type: Array, default: () => [] },
})

const page = usePage()

// ── Screen states ────────────────────────────────────────
// 'screensaver' | 'search' | 'form' | 'success'
const screen = ref('screensaver')

// ── Clock ────────────────────────────────────────────────
const timeStr = ref('')
const dateStr = ref('')
const ampm    = ref('')

let clockInterval = null
function tick() {
    const now = new Date()
    const h   = now.getHours()
    const m   = String(now.getMinutes()).padStart(2, '0')
    const s   = String(now.getSeconds()).padStart(2, '0')
    ampm.value    = h >= 12 ? 'PM' : 'AM'
    const h12 = h % 12 || 12
    timeStr.value = `${String(h12).padStart(2, '0')}:${m}:${s}`
    dateStr.value = now.toLocaleDateString('en-PH', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    })
}

// ── Idle timer ───────────────────────────────────────────
// Longer timeout on the form screen so filling in the form
// doesn't accidentally trigger the screensaver.
const IDLE_SEARCH_MS = 60_000    // 60 s on search screen
const IDLE_FORM_MS   = 300_000   // 5 min on form screen

let idleTimer = null

function resetIdle() {
    if (screen.value === 'screensaver') return
    clearTimeout(idleTimer)
    const delay = screen.value === 'form' ? IDLE_FORM_MS : IDLE_SEARCH_MS
    idleTimer = setTimeout(goScreensaver, delay)
}

function wakeUp() {
    if (screen.value === 'screensaver') {
        screen.value = 'search'
        nextTick(() => searchInput.value?.focus())
    }
    resetIdle()
}

function goScreensaver() {
    clearTimeout(idleTimer)
    clearTimeout(successTimer)
    screen.value = 'screensaver'
    resetForm()
}

// ── Patient search ───────────────────────────────────────
const searchInput    = ref(null)
const patientSearch  = ref('')
const patientResults = ref([])
const selectedPatient= ref(null)
const searching      = ref(false)
let   searchTimer    = null

function onSearch() {
    clearTimeout(searchTimer)
    if (patientSearch.value.length < 2) { patientResults.value = []; return }
    searching.value = true
    searchTimer = setTimeout(async () => {
        try {
            const res = await fetch(route('queue.search-patient-kiosk') + '?q=' + encodeURIComponent(patientSearch.value))
            patientResults.value = await res.json()
        } finally { searching.value = false }
    }, 300)
}

function pickPatient(p) {
    selectedPatient.value         = p
    form.value.patient_id         = p.id
    form.value.visit_type         = p.visit_type ?? 'opd'
    patientSearch.value           = p.full_name
    patientResults.value          = []
    screen.value = 'form'
    resetIdle()
}

function clearPatient() {
    selectedPatient.value         = null
    form.value.patient_id         = null
    patientSearch.value           = ''
    patientResults.value          = []
}

// ── Issue form ───────────────────────────────────────────
const form = ref({
    patient_id:         null,
    visit_type:         'opd',
    priority:           'regular',
    services_requested: [],
    employer_company:   '',
    chief_complaint:    '',
})

const submitting  = ref(false)
const submitError = ref('')

function toggleService(code) {
    const i = form.value.services_requested.indexOf(code)
    if (i === -1) form.value.services_requested.push(code)
    else          form.value.services_requested.splice(i, 1)
    submitError.value = ''
}
const isSelected = (code) => form.value.services_requested.includes(code)

function submitTicket() {
    if (!form.value.patient_id || !form.value.services_requested.length) return
    submitError.value = ''
    submitting.value  = true
    router.post(route('queue.kiosk-checkin'), form.value, {
        onError: (errors) => {
            submitting.value  = false
            const msgs = Object.values(errors).flat()
            submitError.value = msgs[0] ?? 'Something went wrong. Please ask a staff member for help.'
        },
        onFinish: () => { submitting.value = false },
    })
}

// ── Success (flash watcher) ──────────────────────────────
const issuedTicket = ref(null)
let successTimer   = null
const successCountdown = ref(15)
let countdownInterval  = null

watch(() => page.props.flash?.kioskSuccess, (checkin) => {
    if (!checkin) return
    issuedTicket.value    = checkin
    screen.value          = 'success'
    clearTimeout(idleTimer)

    // Auto-print check-in reference slip
    setTimeout(() => printKioskCheckin(checkin), 800)

    // Countdown back to screensaver
    successCountdown.value = 15
    countdownInterval = setInterval(() => {
        successCountdown.value--
        if (successCountdown.value <= 0) {
            clearInterval(countdownInterval)
            goScreensaver()
        }
    }, 1000)
}, { immediate: true })

function resetForm() {
    clearInterval(countdownInterval)
    selectedPatient.value         = null
    patientSearch.value           = ''
    patientResults.value          = []
    issuedTicket.value            = null
    form.value.patient_id         = null
    form.value.visit_type         = 'opd'
    form.value.priority           = 'regular'
    form.value.services_requested = []
    form.value.employer_company   = ''
    form.value.chief_complaint    = ''
    submitError.value             = ''
}

// ── Lifecycle ────────────────────────────────────────────
const EVENTS = ['mousemove','mousedown','keydown','touchstart','touchmove','wheel']

onMounted(() => {
    tick()
    clockInterval = setInterval(tick, 1000)
    EVENTS.forEach(e => window.addEventListener(e, resetIdle, { passive: true }))
})

onUnmounted(() => {
    clearInterval(clockInterval)
    clearTimeout(idleTimer)
    clearTimeout(successTimer)
    clearInterval(countdownInterval)
    EVENTS.forEach(e => window.removeEventListener(e, resetIdle))
})

// ── Service definitions (dynamic from DB) ────────────────
const CATEGORY_META = {
    laboratory:   { label: 'Laboratory',        icon: FlaskConical, light: 'bg-blue-50 border-blue-200 text-blue-700',       active: 'bg-blue-600 border-blue-600 text-white' },
    xray_utz:     { label: 'X-Ray & Ultrasound', icon: ScanLine,     light: 'bg-violet-50 border-violet-200 text-violet-700',  active: 'bg-violet-600 border-violet-600 text-white' },
    drug_test:    { label: 'Drug Test',          icon: TestTube,     light: 'bg-rose-50 border-rose-200 text-rose-700',        active: 'bg-rose-600 border-rose-600 text-white' },
    consultation: { label: 'Consultation',       icon: Stethoscope,  light: 'bg-emerald-50 border-emerald-200 text-emerald-700', active: 'bg-emerald-600 border-emerald-600 text-white' },
}

const serviceGroups = computed(() =>
    props.services.map(grp => {
        const meta = CATEGORY_META[grp.category] ?? {
            label: grp.category, icon: HeartPulse,
            light:  'bg-slate-50 border-slate-200 text-slate-700',
            active: 'bg-slate-600 border-slate-600 text-white',
        }
        return {
            group:    meta.label,
            room:     grp.room,
            icon:     meta.icon,
            light:    meta.light,
            active:   meta.active,
            services: grp.services,
        }
    })
)

const PRIORITY_OPTIONS = [
    { value: 'regular',  label: 'Regular',        icon: User,      color: 'border-slate-300 text-slate-600', active: 'bg-slate-700 border-slate-700 text-white' },
    { value: 'senior',   label: 'Senior Citizen', icon: UserCheck, color: 'border-amber-300 text-amber-700', active: 'bg-amber-500 border-amber-500 text-white' },
    { value: 'pwd',      label: 'PWD',             icon: Shield,    color: 'border-blue-300 text-blue-700',   active: 'bg-blue-600 border-blue-600 text-white' },
    { value: 'pregnant', label: 'Pregnant',        icon: Heart,     color: 'border-pink-300 text-pink-700',   active: 'bg-pink-600 border-pink-600 text-white' },
    { value: 'urgent',   label: 'Urgent',          icon: Zap,       color: 'border-red-300 text-red-700',     active: 'bg-red-600 border-red-600 text-white' },
]

const VISIT_OPTIONS = [
    { value: 'opd',            label: 'OPD',           icon: Stethoscope   },
    { value: 'pre_employment', label: 'Pre-Employment', icon: Briefcase     },
    { value: 'annual_pe',      label: 'Annual PE',      icon: ClipboardList },
    { value: 'exit_pe',        label: 'Exit PE',        icon: LogOut        },
    { value: 'follow_up',      label: 'Follow-up',      icon: RotateCcw     },
    { value: 'lab_only',       label: 'Lab Only',       icon: FlaskConical  },
]

const ROOM_LABEL = {
    laboratory: 'Laboratory', xray_utz: 'X-Ray / UTZ',
    drug_test: 'Drug Test', interview_room: 'Doctor', nurse_station: 'Nurse',
}

const canSubmit = computed(() =>
    form.value.patient_id && form.value.services_requested.length > 0
)

function visitBadge(type) {
    return VISIT_TYPE_BADGE[type] ?? { bg: '#f1f5f9', color: '#475569', label: type }
}
</script>

<template>
    <Head title="Kiosk — Queue" />

    <!-- ══════════════════════════════════════════════════════
         ROOT — full screen, captures all touch/click to wake
    ═══════════════════════════════════════════════════════ -->
    <div class="fixed inset-0 overflow-hidden">

        <!-- ─────────────────────────────────────────────────
             SCREENSAVER
             :duration="600" — prevents Vue from waiting for the
             infinite background animations (18-25 s) before
             removing the screensaver from the DOM.
        ───────────────────────────────────────────────── -->
        <Transition name="fade" :duration="600">
        <div v-if="screen === 'screensaver'"
             class="absolute inset-0 screensaver-bg flex flex-col items-center justify-center z-50 cursor-pointer overflow-hidden select-none"
             @click="wakeUp">

            <!-- Floating orbs -->
            <div class="orb orb-1" />
            <div class="orb orb-2" />
            <div class="orb orb-3" />
            <div class="orb orb-4" />

            <!-- Grid overlay -->
            <div class="absolute inset-0 grid-overlay pointer-events-none" />

            <!-- Content -->
            <div class="relative z-10 flex flex-col items-center gap-6 px-8 text-center">

                <!-- Logo -->
                <div class="logo-ring">
                    <img :src="CLINIC_LOGO" alt="Logo" class="w-28 h-28 object-contain logo-img" />
                </div>

                <!-- Clinic name -->
                <div>
                    <h1 class="text-3xl font-black text-white tracking-wide drop-shadow-lg">
                        {{ CLINIC_INFO.shortName }}
                    </h1>
                    <p class="text-sky-300 text-sm font-medium tracking-widest uppercase mt-1">
                        {{ CLINIC_INFO.subtitle }}
                    </p>
                </div>

                <!-- Clock -->
                <div class="clock-container">
                    <div class="clock-time">
                        <span class="digits">{{ timeStr }}</span>
                        <span class="ampm-badge">{{ ampm }}</span>
                    </div>
                    <div class="clock-date">{{ dateStr }}</div>
                </div>

                <!-- Touch prompt -->
                <div class="touch-prompt">
                    <span class="touch-dot" />
                    Touch screen to begin
                    <span class="touch-dot" />
                </div>

            </div>

            <!-- Staff link bottom-right -->
            <a :href="route('queue.index')"
               class="absolute bottom-5 right-6 text-white/20 hover:text-white/60 text-xs font-medium transition-colors flex items-center gap-1.5"
               @click.stop>
                <Monitor class="w-3.5 h-3.5" /> Staff Dashboard
            </a>
        </div>
        </Transition>

        <!-- ─────────────────────────────────────────────────
             SEARCH SCREEN
        ───────────────────────────────────────────────── -->
        <Transition name="slide-up">
        <div v-if="screen === 'search'"
             class="absolute inset-0 bg-slate-50 flex flex-col z-40"
             @touchstart.passive="resetIdle"
             @mousedown.passive="resetIdle"
             @keydown.passive="resetIdle">

            <!-- Header -->
            <div class="kiosk-header">
                <img :src="CLINIC_LOGO" class="w-10 h-10 object-contain" />
                <div class="flex-1">
                    <p class="text-white font-black text-lg leading-tight">{{ CLINIC_INFO.shortName }}</p>
                    <p class="text-white/60 text-xs">Queue Management System</p>
                </div>
                <div class="text-right">
                    <p class="text-white font-bold text-xl tabular-nums">{{ timeStr }} <span class="text-sm text-white/60">{{ ampm }}</span></p>
                    <p class="text-white/50 text-xs">{{ dateStr }}</p>
                </div>
            </div>

            <!-- Body -->
            <div class="flex-1 flex flex-col items-center justify-start p-8 gap-8 overflow-y-auto">

                <div class="w-full max-w-2xl">
                    <!-- Title -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-100 mb-4">
                            <Search class="w-7 h-7 text-blue-600" />
                        </div>
                        <h2 class="text-3xl font-black text-slate-800">Find Your Record</h2>
                        <p class="text-slate-500 mt-1">Enter your name, patient code, or contact number</p>
                    </div>

                    <!-- Search input -->
                    <div class="flex items-center gap-3 bg-white border-2 border-slate-200 rounded-2xl px-5
                                shadow-sm transition-all focus-within:border-blue-400 focus-within:ring-4 focus-within:ring-blue-100">
                        <Search class="w-6 h-6 text-slate-400 flex-shrink-0" />
                        <input
                            ref="searchInput"
                            v-model="patientSearch"
                            @input="onSearch"
                            type="text"
                            placeholder="Type to search..."
                            class="flex-1 py-5 text-xl bg-transparent border-0 outline-none focus:outline-none focus:ring-0 min-w-0"
                            autocomplete="off"
                            spellcheck="false"
                        />
                        <button v-if="patientSearch"
                            @click="patientSearch = ''; patientResults = []"
                            class="p-1.5 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100 flex-shrink-0 transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Searching indicator -->
                    <div v-if="searching" class="flex items-center justify-center gap-2 py-4 text-slate-400">
                        <div class="w-5 h-5 border-2 border-slate-300 border-t-blue-500 rounded-full animate-spin" />
                        <span>Searching...</span>
                    </div>

                    <!-- Results -->
                    <div v-if="patientResults.length > 0" class="mt-3 space-y-2">
                        <button v-for="p in patientResults" :key="p.id"
                            @click.stop="pickPatient(p)"
                            class="w-full flex items-center gap-4 p-4 bg-white rounded-2xl border-2 border-slate-200
                                   hover:border-blue-400 hover:bg-blue-50 active:scale-[0.99]
                                   transition-all text-left shadow-sm group">
                            <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black text-lg flex-shrink-0">
                                {{ p.full_name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-800 text-lg truncate">{{ p.full_name }}</p>
                                <div class="flex items-center gap-3 text-sm text-slate-500 mt-0.5">
                                    <span class="font-mono">{{ p.patient_code }}</span>
                                    <span>·</span>
                                    <span>{{ p.age_sex }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs px-3 py-1.5 rounded-full font-semibold"
                                    :style="{ background: visitBadge(p.visit_type).bg, color: visitBadge(p.visit_type).color }">
                                    {{ visitBadge(p.visit_type).label }}
                                </span>
                                <ChevronRight class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transition-colors" />
                            </div>
                        </button>
                    </div>

                    <!-- No results -->
                    <div v-else-if="patientSearch.length >= 2 && !searching"
                        class="flex flex-col items-center gap-3 py-10 text-slate-400">
                        <Search class="w-10 h-10" />
                        <p class="text-lg font-medium">No patients found</p>
                        <p class="text-sm">Please check the spelling or contact the receptionist</p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="kiosk-footer">
                <button @click.stop="goScreensaver" class="kiosk-back-btn">
                    <ArrowLeft class="w-5 h-5" /> Back
                </button>
                <p class="text-white/40 text-sm">Need help? Ask our staff at the front desk.</p>
                <div class="w-24" />
            </div>
        </div>
        </Transition>

        <!-- ─────────────────────────────────────────────────
             FORM SCREEN
        ───────────────────────────────────────────────── -->
        <Transition name="slide-up">
        <div v-if="screen === 'form'"
             class="absolute inset-0 bg-slate-50 flex flex-col z-40 overflow-hidden"
             @touchstart.passive="resetIdle"
             @mousedown.passive="resetIdle"
             @keydown.passive="resetIdle">

            <!-- Header -->
            <div class="kiosk-header">
                <img :src="CLINIC_LOGO" class="w-10 h-10 object-contain" />
                <div class="flex-1">
                    <p class="text-white font-black text-lg leading-tight">{{ CLINIC_INFO.shortName }}</p>
                    <p class="text-white/60 text-xs">Issuing ticket for</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="text-right">
                        <p class="text-white font-bold text-base leading-tight">{{ selectedPatient?.full_name }}</p>
                        <p class="text-white/60 text-xs font-mono">{{ selectedPatient?.patient_code }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center text-white font-black text-lg">
                        {{ selectedPatient?.full_name?.charAt(0)?.toUpperCase() }}
                    </div>
                </div>
            </div>

            <!-- Body (scrollable) -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">

                <!-- Visit Type -->
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Visit Type</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="vt in VISIT_OPTIONS" :key="vt.value"
                            @click.stop="form.visit_type = vt.value; resetIdle()"
                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all"
                            :class="form.visit_type === vt.value
                                ? 'border-blue-600 bg-blue-600 text-white shadow-lg shadow-blue-200'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300'">
                            <component :is="vt.icon" class="w-4 h-4" />{{ vt.label }}
                        </button>
                    </div>
                </div>

                <!-- Services -->
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">
                        Select Services
                        <span v-if="form.services_requested.length"
                            class="ml-2 bg-blue-600 text-white text-xs rounded-full px-2 py-0.5 font-bold normal-case">
                            {{ form.services_requested.length }} selected
                        </span>
                    </p>
                    <div class="space-y-4">
                        <div v-for="grp in serviceGroups" :key="grp.group">
                            <!-- Group header -->
                            <div class="flex items-center gap-2 mb-2">
                                <component :is="grp.icon" class="w-4 h-4 text-slate-400" />
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ grp.group }}</span>
                            </div>
                            <!-- Service buttons -->
                            <div class="flex flex-wrap gap-2">
                                <button v-for="svc in grp.services" :key="svc.code"
                                    @click.stop="toggleService(svc.code); resetIdle()"
                                    class="px-4 py-2.5 rounded-xl border-2 text-sm font-semibold transition-all active:scale-95"
                                    :class="isSelected(svc.code) ? grp.active : grp.light">
                                    {{ svc.label }}
                                    <span v-if="isSelected(svc.code)" class="ml-1.5">✓</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Priority -->
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Priority</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="p in PRIORITY_OPTIONS" :key="p.value"
                            @click.stop="form.priority = p.value; resetIdle()"
                            class="flex items-center gap-2 px-5 py-3 rounded-xl border-2 text-sm font-bold transition-all active:scale-95"
                            :class="form.priority === p.value ? p.active : p.color + ' bg-white hover:opacity-80'">
                            <component :is="p.icon" class="w-4 h-4" />{{ p.label }}
                        </button>
                    </div>
                </div>

                <!-- Employer (if PE types) -->
                <div v-if="['pre_employment','annual_pe','exit_pe'].includes(form.visit_type)">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Company / Employer</p>
                    <input v-model="form.employer_company" @input="resetIdle"
                        type="text" placeholder="Enter company name (optional)"
                        class="w-full px-4 py-3 text-base rounded-xl border-2 border-slate-200 bg-white
                               focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                </div>

                <!-- OPD chief complaint -->
                <div v-if="form.visit_type === 'opd'">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-3">Chief Complaint (Optional)</p>
                    <textarea v-model="form.chief_complaint" @input="resetIdle"
                        rows="2" placeholder="Describe your main complaint..."
                        class="w-full px-4 py-3 text-base rounded-xl border-2 border-slate-200 bg-white resize-none
                               focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100" />
                </div>

                <!-- Spacer for fixed submit bar -->
                <div class="h-24" />
            </div>

            <!-- Fixed submit bar -->
            <div class="absolute bottom-0 left-0 right-0 bg-white border-t border-slate-200 shadow-xl">
                <!-- Error banner -->
                <div v-if="submitError"
                     class="flex items-center gap-3 px-6 py-3 bg-red-50 border-b border-red-100 text-red-700 text-sm font-medium">
                    <AlertTriangle class="w-5 h-5 flex-shrink-0" />
                    {{ submitError }}
                </div>
                <div class="flex items-center gap-4 px-6 py-4">
                    <button @click.stop="screen = 'search'; clearPatient(); submitError = ''" class="kiosk-back-btn">
                        <ArrowLeft class="w-5 h-5" />
                    </button>
                    <button @click.stop="submitTicket"
                        :disabled="!canSubmit || submitting"
                        class="flex-1 flex items-center justify-center gap-3 py-4 rounded-2xl text-xl font-black transition-all
                               disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.99]"
                        :class="canSubmit && !submitting
                            ? 'bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white shadow-lg shadow-blue-200'
                            : 'bg-slate-200 text-slate-400'">
                        <span v-if="submitting" class="w-6 h-6 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        <Printer v-else class="w-6 h-6" />
                        {{ submitting ? 'Issuing Ticket…' : 'Issue Ticket & Print' }}
                    </button>
                </div>
            </div>
        </div>
        </Transition>

        <!-- ─────────────────────────────────────────────────
             SUCCESS SCREEN
        ───────────────────────────────────────────────── -->
        <Transition name="scale-up">
        <div v-if="screen === 'success' && issuedTicket"
             class="absolute inset-0 z-50 flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 to-blue-950 overflow-hidden"
             @click.stop>

            <!-- Background rings -->
            <div class="success-ring success-ring-1" />
            <div class="success-ring success-ring-2" />
            <div class="success-ring success-ring-3" />

            <div class="relative z-10 flex flex-col items-center gap-8 px-8 text-center w-full max-w-xl">

                <!-- Check icon -->
                <div class="w-20 h-20 rounded-full bg-green-500 flex items-center justify-center shadow-2xl shadow-green-500/50 success-check">
                    <CheckCircle2 class="w-12 h-12 text-white" />
                </div>

                <!-- Title -->
                <div>
                    <p class="text-green-400 text-sm font-bold uppercase tracking-widest mb-1">Check-In Recorded!</p>
                    <h2 class="text-white font-black text-3xl leading-tight">Proceed to the<br>Reception Counter</h2>
                </div>

                <!-- Patient info card -->
                <div class="bg-white/10 backdrop-blur-sm rounded-2xl px-8 py-5 w-full">
                    <p class="text-white font-black text-xl">{{ issuedTicket.patient_name }}</p>
                    <p class="text-white/50 font-mono text-sm mt-0.5">{{ issuedTicket.patient_code }}</p>
                    <div class="flex items-center justify-center gap-2 mt-3">
                        <span class="text-xs px-3 py-1.5 rounded-full font-bold text-white/90 bg-white/20">
                            {{ issuedTicket.visit_type?.replace(/_/g,' ').toUpperCase() }}
                        </span>
                        <span class="text-xs px-3 py-1.5 rounded-full font-bold"
                            :class="{
                                'bg-red-500 text-white':   issuedTicket.priority === 'urgent',
                                'bg-pink-500 text-white':  issuedTicket.priority === 'pregnant',
                                'bg-blue-500 text-white':  issuedTicket.priority === 'pwd',
                                'bg-amber-500 text-white': issuedTicket.priority === 'senior',
                                'bg-white/20 text-white':  issuedTicket.priority === 'regular',
                            }">
                            {{ issuedTicket.priority?.toUpperCase() }}
                        </span>
                    </div>
                    <!-- Services summary -->
                    <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                        <span v-for="svc in issuedTicket.services" :key="svc"
                              class="text-xs bg-white/10 text-white/70 px-2 py-0.5 rounded-full">
                            {{ svc }}
                        </span>
                    </div>
                </div>

                <!-- Direction instruction -->
                <div class="bg-amber-500/20 border border-amber-400/40 rounded-2xl px-6 py-4 w-full flex items-center gap-4">
                    <MapPin class="w-8 h-8 text-amber-400 flex-shrink-0" />
                    <div class="text-left">
                        <p class="text-amber-300 font-bold text-sm">Next Step</p>
                        <p class="text-white/80 text-sm leading-snug mt-0.5">
                            Please present yourself at the <strong class="text-white">Reception Counter</strong>
                            to complete your registration, services, and payment before proceeding.
                        </p>
                    </div>
                </div>

                <!-- Print + Countdown -->
                <div class="flex flex-col items-center gap-3">
                    <button @click="printKioskCheckin(issuedTicket)"
                            class="flex items-center gap-2 text-white/60 hover:text-white text-sm transition-colors px-4 py-2 rounded-xl hover:bg-white/10">
                        <Printer class="w-4 h-4" />
                        Reprint Slip
                    </button>
                    <div class="flex items-center gap-3">
                        <div class="countdown-ring">
                            <svg class="w-10 h-10 -rotate-90">
                                <circle cx="20" cy="20" r="16" stroke="rgba(255,255,255,0.15)" stroke-width="3" fill="none" />
                                <circle cx="20" cy="20" r="16" stroke="rgba(255,255,255,0.7)" stroke-width="3" fill="none"
                                    stroke-dasharray="100.5"
                                    :stroke-dashoffset="100.5 - (100.5 * successCountdown / 15)" />
                            </svg>
                            <span class="absolute text-white font-bold text-sm">{{ successCountdown }}</span>
                        </div>
                        <span class="text-white/50 text-sm">Returning to home screen</span>
                    </div>
                </div>

            </div>
        </div>
        </Transition>

    </div>
</template>

<style scoped>
/* ── Screensaver background ─────────────────────────────── */
.screensaver-bg {
    background: linear-gradient(135deg, #0F2044 0%, #0d1b3e 25%, #1a3a6b 50%, #0a1628 75%, #0F2044 100%);
    background-size: 400% 400%;
    animation: bgShift 18s ease infinite;
}
@keyframes bgShift {
    0%   { background-position: 0% 50% }
    50%  { background-position: 100% 50% }
    100% { background-position: 0% 50% }
}

/* Grid overlay */
.grid-overlay {
    background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 60px 60px;
}

/* Floating orbs */
.orb {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.25;
    pointer-events: none;
}
.orb-1 { width: 500px; height: 500px; background: #1d4ed8; top: -150px; left: -100px; animation: float1 20s ease-in-out infinite; }
.orb-2 { width: 400px; height: 400px; background: #0ea5e9; bottom: -100px; right: -80px; animation: float2 25s ease-in-out infinite; }
.orb-3 { width: 300px; height: 300px; background: #7c3aed; top: 40%; left: 60%; animation: float3 18s ease-in-out infinite; }
.orb-4 { width: 250px; height: 250px; background: #0f766e; bottom: 20%; left: 10%; animation: float1 22s ease-in-out infinite reverse; }

@keyframes float1 {
    0%, 100% { transform: translate(0, 0) }
    33%       { transform: translate(40px, -60px) }
    66%       { transform: translate(-30px, 40px) }
}
@keyframes float2 {
    0%, 100% { transform: translate(0, 0) }
    33%       { transform: translate(-50px, 30px) }
    66%       { transform: translate(40px, -50px) }
}
@keyframes float3 {
    0%, 100% { transform: translate(0, 0) scale(1) }
    50%       { transform: translate(-80px, -40px) scale(1.2) }
}

/* Logo ring */
.logo-ring {
    width: 148px; height: 148px;
    border-radius: 50%;
    background: rgba(255,255,255,0.08);
    border: 2px solid rgba(255,255,255,0.15);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 60px rgba(14, 165, 233, 0.3), inset 0 0 30px rgba(255,255,255,0.05);
    animation: logoPulse 3s ease-in-out infinite;
}
@keyframes logoPulse {
    0%, 100% { box-shadow: 0 0 60px rgba(14,165,233,0.3), inset 0 0 30px rgba(255,255,255,0.05); }
    50%       { box-shadow: 0 0 100px rgba(14,165,233,0.5), inset 0 0 40px rgba(255,255,255,0.1); }
}
.logo-img { filter: drop-shadow(0 0 20px rgba(14,165,233,0.5)) brightness(1.1); }

/* Clock */
.clock-container { text-align: center; }
.clock-time {
    display: flex; align-items: baseline; gap: 8px; justify-content: center;
}
.digits {
    font-size: 4.5rem;
    font-weight: 900;
    color: white;
    letter-spacing: 4px;
    font-variant-numeric: tabular-nums;
    font-family: 'Courier New', monospace;
    text-shadow: 0 0 40px rgba(14,165,233,0.6);
}
.ampm-badge {
    font-size: 1.2rem;
    font-weight: 700;
    color: rgba(14,165,233,0.9);
    letter-spacing: 2px;
}
.clock-date {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.5);
    letter-spacing: 1px;
    margin-top: 4px;
}

/* Touch prompt */
.touch-prompt {
    display: flex; align-items: center; gap: 12px;
    color: rgba(255,255,255,0.5);
    font-size: 0.9rem;
    font-weight: 500;
    letter-spacing: 2px;
    text-transform: uppercase;
    animation: promptPulse 2.5s ease-in-out infinite;
}
.touch-dot {
    width: 6px; height: 6px;
    background: rgba(14,165,233,0.7);
    border-radius: 50%;
}
@keyframes promptPulse {
    0%, 100% { opacity: 0.4; }
    50%       { opacity: 0.9; }
}

/* ── Kiosk header & footer ────────────────────────────── */
.kiosk-header {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 24px;
    background: linear-gradient(135deg, #0F2044, #1B4F9B);
    flex-shrink: 0;
    box-shadow: 0 4px 20px rgba(15,32,68,0.3);
}
.kiosk-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 24px;
    background: linear-gradient(135deg, #0F2044, #1B4F9B);
    flex-shrink: 0;
}
.kiosk-back-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 20px;
    background: rgba(255,255,255,0.15);
    color: white;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background 0.2s;
}
.kiosk-back-btn:hover { background: rgba(255,255,255,0.25); }

/* ── Success screen ───────────────────────────────────── */
.success-ring {
    position: absolute; border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.06);
    pointer-events: none;
    animation: ringExpand 4s ease-in-out infinite;
}
.success-ring-1 { width: 400px; height: 400px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 0s; }
.success-ring-2 { width: 600px; height: 600px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 0.8s; }
.success-ring-3 { width: 800px; height: 800px; top: 50%; left: 50%; transform: translate(-50%,-50%); animation-delay: 1.6s; }
@keyframes ringExpand {
    0%   { opacity: 0.3; transform: translate(-50%,-50%) scale(0.8); }
    100% { opacity: 0;   transform: translate(-50%,-50%) scale(1.3); }
}

.success-check { animation: checkBounce 0.6s cubic-bezier(0.36, 0.07, 0.19, 0.97); }
@keyframes checkBounce {
    0%   { transform: scale(0); opacity: 0; }
    60%  { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

.ticket-number-display {
    font-size: 7rem;
    font-weight: 900;
    color: white;
    letter-spacing: 8px;
    text-shadow: 0 0 60px rgba(14,165,233,0.8), 0 0 120px rgba(14,165,233,0.4);
    line-height: 1;
    animation: ticketGlow 2s ease-in-out infinite;
}
@keyframes ticketGlow {
    0%, 100% { text-shadow: 0 0 60px rgba(14,165,233,0.8), 0 0 120px rgba(14,165,233,0.4); }
    50%       { text-shadow: 0 0 80px rgba(14,165,233,1), 0 0 160px rgba(14,165,233,0.6); }
}

.countdown-ring { position: relative; display: flex; align-items: center; justify-content: center; }
.countdown-ring span { position: absolute; }

/* ── Transitions ─────────────────────────────────────── */
.fade-enter-active { transition: opacity 0.6s ease; }
.fade-leave-active { transition: opacity 0.6s ease; pointer-events: none; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.slide-up-enter-active { transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); }
.slide-up-leave-active  { transition: all 0.3s ease-in; }
.slide-up-enter-from { opacity: 0; transform: translateY(30px); }
.slide-up-leave-to   { opacity: 0; transform: translateY(-20px); }

.scale-up-enter-active { transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
.scale-up-leave-active  { transition: all 0.3s ease-in; }
.scale-up-enter-from { opacity: 0; transform: scale(0.92); }
.scale-up-leave-to   { opacity: 0; transform: scale(1.04); }
</style>
