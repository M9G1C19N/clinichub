<script setup>
import { computed, watch } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Ear, ChevronLeft, Save, CheckCircle2, Printer } from 'lucide-vue-next'

const props = defineProps({
    visit:       Object,
    patient:     Object,
    audResult:   Object,
    currentUser: Object,
    staffList:   Array,
})

const isReleased = computed(() => props.audResult?.status === 'released')

// ── FORM ──────────────────────────────────────────────────
const form = useForm({
    audiometer_used:        props.audResult?.audiometer_used        ?? '',
    last_calibrated:        props.audResult?.last_calibrated        ?? '',
    examiner:               props.audResult?.examiner               ?? '',
    reason_for_audiometry:  props.audResult?.reason_for_audiometry  ?? '',
    // Case History
    ch_hearing_loss:        props.audResult?.ch_hearing_loss        ?? false,
    ch_hearing_loss_when:   props.audResult?.ch_hearing_loss_when   ?? '',
    ch_hearing_aid:         props.audResult?.ch_hearing_aid         ?? false,
    ch_better_ear:          props.audResult?.ch_better_ear          ?? false,
    ch_better_ear_which:    props.audResult?.ch_better_ear_which    ?? '',
    ch_sudden_progression:  props.audResult?.ch_sudden_progression  ?? false,
    ch_ringing_noises:      props.audResult?.ch_ringing_noises      ?? false,
    ch_drainage:            props.audResult?.ch_drainage            ?? false,
    ch_pain_discomfort:     props.audResult?.ch_pain_discomfort     ?? false,
    ch_medical_consultation:props.audResult?.ch_medical_consultation ?? false,
    // Otoscopic
    otoscopic_right_ear: props.audResult?.otoscopic_right_ear ?? '',
    otoscopic_left_ear:  props.audResult?.otoscopic_left_ear  ?? '',
    // Right Ear frequencies
    re_250:  props.audResult?.re_250  ?? null,
    re_500:  props.audResult?.re_500  ?? null,
    re_1000: props.audResult?.re_1000 ?? null,
    re_1500: props.audResult?.re_1500 ?? null,
    re_2000: props.audResult?.re_2000 ?? null,
    re_3000: props.audResult?.re_3000 ?? null,
    re_4000: props.audResult?.re_4000 ?? null,
    re_8000: props.audResult?.re_8000 ?? null,
    re_average: props.audResult?.re_average ?? null,
    // Left Ear frequencies
    le_250:  props.audResult?.le_250  ?? null,
    le_500:  props.audResult?.le_500  ?? null,
    le_1000: props.audResult?.le_1000 ?? null,
    le_1500: props.audResult?.le_1500 ?? null,
    le_2000: props.audResult?.le_2000 ?? null,
    le_3000: props.audResult?.le_3000 ?? null,
    le_4000: props.audResult?.le_4000 ?? null,
    le_8000: props.audResult?.le_8000 ?? null,
    le_average: props.audResult?.le_average ?? null,
    // Remarks
    remarks_right_ear: props.audResult?.remarks_right_ear ?? '',
    remarks_left_ear:  props.audResult?.remarks_left_ear  ?? '',
    recommendations:   props.audResult?.recommendations   ?? '',
    // Staff
    examined_by_name:      props.audResult?.examined_by_name      ?? props.currentUser?.name ?? '',
    examined_by_license:   props.audResult?.examined_by_license   ?? props.currentUser?.esignature?.license_number ?? '',
    examined_by_signature: props.audResult?.examined_by_signature ?? props.currentUser?.esignature?.signature_path ?? '',
    noted_by_name:         props.audResult?.noted_by_name         ?? '',
    noted_by_license:      props.audResult?.noted_by_license      ?? '',
    noted_by_signature:    props.audResult?.noted_by_signature    ?? '',
    // Dates
    result_date: props.audResult?.result_date ?? new Date().toISOString().slice(0, 10),
    result_time: props.audResult?.result_time ?? new Date().toTimeString().slice(0, 5),
    release: false,
})

// ── AUTO-CALCULATE AVERAGES ────────────────────────────────
const reFreqs = ['re_250', 're_500', 're_1000', 're_1500', 're_2000', 're_3000', 're_4000', 're_8000']
const leFreqs = ['le_250', 'le_500', 'le_1000', 'le_1500', 'le_2000', 'le_3000', 'le_4000', 'le_8000']

function calcAvg(fields) {
    const vals = fields.map(f => parseFloat(form[f])).filter(v => !isNaN(v))
    if (!vals.length) return null
    return Math.round((vals.reduce((a, b) => a + b, 0) / vals.length) * 10) / 10
}

watch(reFreqs.map(f => () => form[f]), () => { form.re_average = calcAvg(reFreqs) }, { deep: false })
watch(leFreqs.map(f => () => form[f]), () => { form.le_average = calcAvg(leFreqs) }, { deep: false })

// ── LIVE AUDIOGRAM CHART ──────────────────────────────────
// Chart dimensions
const CHART_W   = 480
const CHART_H   = 260
const MARGIN    = { top: 16, right: 24, bottom: 36, left: 52 }
const PLOT_W    = CHART_W - MARGIN.left - MARGIN.right
const PLOT_H    = CHART_H - MARGIN.top  - MARGIN.bottom
const DB_MIN    = -10
const DB_MAX    = 120
const DB_RANGE  = DB_MAX - DB_MIN

// Standard audiogram frequencies on X-axis (log-spaced display, equal steps)
const FREQS     = [250, 500, 1000, 2000, 4000, 8000]
const FREQ_KEYS_RE = ['re_250', 're_500', 're_1000', 're_2000', 're_4000', 're_8000']
const FREQ_KEYS_LE = ['le_250', 'le_500', 'le_1000', 'le_2000', 'le_4000', 'le_8000']

function xPos(idx) {
    return MARGIN.left + (idx / (FREQS.length - 1)) * PLOT_W
}
function yPos(db) {
    return MARGIN.top + ((db - DB_MIN) / DB_RANGE) * PLOT_H
}

// dB gridlines every 10
const dbGridLines = computed(() => {
    const lines = []
    for (let db = DB_MIN; db <= DB_MAX; db += 10) {
        lines.push({ db, y: yPos(db) })
    }
    return lines
})

// Frequency gridlines
const freqGridLines = computed(() =>
    FREQS.map((hz, i) => ({ hz, x: xPos(i) }))
)

// Right Ear points (O marker, red)
const rePoints = computed(() =>
    FREQ_KEYS_RE.map((key, i) => {
        const val = parseFloat(form[key])
        if (isNaN(val)) return null
        return { x: xPos(i), y: yPos(val), db: val }
    })
)

// Left Ear points (X marker, blue)
const lePoints = computed(() =>
    FREQ_KEYS_LE.map((key, i) => {
        const val = parseFloat(form[key])
        if (isNaN(val)) return null
        return { x: xPos(i), y: yPos(val), db: val }
    })
)

// Polyline path strings (only connect non-null points)
function buildPath(points) {
    const segments = []
    let segStart = null
    for (let i = 0; i < points.length; i++) {
        if (points[i]) {
            if (segStart === null) segStart = points[i]
            if (i > 0 && points[i - 1]) {
                segments.push(`${points[i - 1].x},${points[i - 1].y} ${points[i].x},${points[i].y}`)
            }
        }
    }
    return segments
}

const reSegments = computed(() => buildPath(rePoints.value))
const leSegments = computed(() => buildPath(lePoints.value))

// Normal hearing shading (0–25 dB)
const normalZoneY1 = yPos(0)
const normalZoneY2 = yPos(25)

// ── STAFF SELECTION ───────────────────────────────────────
function selectExaminer(staff) {
    form.examined_by_name      = staff.name
    form.examined_by_license   = staff.license_number
    form.examined_by_signature = staff.signature_path ?? staff.signature_url ?? ''
}
function selectNotedBy(staff) {
    form.noted_by_name      = staff.name
    form.noted_by_license   = staff.license_number
    form.noted_by_signature = staff.signature_path ?? staff.signature_url ?? ''
}

// ── SUBMIT ────────────────────────────────────────────────
function submit(release = false) {
    form.release = release
    form.post(route('audiometry.save', props.visit.id), {
        preserveScroll: true,
    })
}

// ── TABLE COLUMN CONFIG ───────────────────────────────────
const freqCols = [
    { label: '250',  reKey: 're_250',  leKey: 'le_250'  },
    { label: '500',  reKey: 're_500',  leKey: 'le_500'  },
    { label: '1000', reKey: 're_1000', leKey: 'le_1000' },
    { label: '1500', reKey: 're_1500', leKey: 'le_1500' },
    { label: '2000', reKey: 're_2000', leKey: 'le_2000' },
    { label: '3000', reKey: 're_3000', leKey: 'le_3000' },
    { label: '4000', reKey: 're_4000', leKey: 'le_4000' },
    { label: '8000', reKey: 're_8000', leKey: 'le_8000' },
]

const caseHistoryQuestions = [
    { key: 'ch_hearing_loss',       label: 'Do you think you have a hearing loss?',                             hasWhen: true,  whenKey: 'ch_hearing_loss_when',   whenLabel: 'If yes, when?' },
    { key: 'ch_hearing_aid',        label: 'Have hearing aid(s) ever been recommended for you?',               hasWhen: false },
    { key: 'ch_better_ear',         label: 'Is your hearing better in one ear?',                               hasWhen: true,  whenKey: 'ch_better_ear_which',    whenLabel: 'If yes, which ear?' },
    { key: 'ch_sudden_progression', label: 'Have you ever had a sudden or rapid progression of hearing loss?', hasWhen: false },
    { key: 'ch_ringing_noises',     label: 'Do you have ringing or noises in your ears?',                     hasWhen: false },
    { key: 'ch_drainage',           label: 'Have you had recent drainage from your ears?',                     hasWhen: false },
    { key: 'ch_pain_discomfort',    label: 'Do you have pain or discomfort in your ears?',                     hasWhen: false },
    { key: 'ch_medical_consultation',label: 'Have you received medical consultation for any of the above?',   hasWhen: false },
]
</script>

<template>
    <AppLayout title="Audiometry — Enter Results">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('audiometry.index')">
                    <button class="h-8 w-8 flex items-center justify-center rounded-md border border-slate-200 hover:bg-slate-50">
                        <ChevronLeft class="w-4 h-4"/>
                    </button>
                </Link>
                <div class="flex-1">
                    <h1 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <Ear class="w-5 h-5 text-teal-600"/>
                        Screening Audiometry
                        <span v-if="isReleased"
                            class="text-xs font-semibold px-2 py-0.5 rounded-full"
                            style="background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0;">
                            Released
                        </span>
                    </h1>
                    <p class="text-slate-400 text-xs">{{ patient.full_name }} · {{ patient.age_sex }} · {{ visit.visit_date }}</p>
                </div>
                <a v-if="audResult" :href="route('audiometry.print', visit.id)" target="_blank">
                    <Button variant="outline" size="sm" class="gap-1.5 text-xs">
                        <Printer class="w-3.5 h-3.5"/> Print
                    </Button>
                </a>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-5">

            <!-- ── PATIENT INFO BAR ── -->
            <div class="bg-white rounded-xl border border-slate-200 px-5 py-3 flex items-center gap-6 text-sm shadow-sm">
                <div>
                    <p class="text-xs text-slate-400 font-medium">Patient</p>
                    <p class="font-bold text-slate-800">{{ patient.full_name }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Age / Sex</p>
                    <p class="font-semibold text-slate-700">{{ patient.age_sex }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Birthday</p>
                    <p class="font-semibold text-slate-700">{{ patient.birthdate ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-medium">Address</p>
                    <p class="font-semibold text-slate-700 max-w-xs truncate">{{ patient.address ?? '—' }}</p>
                </div>
                <div v-if="visit.employer_company">
                    <p class="text-xs text-slate-400 font-medium">Company</p>
                    <p class="font-semibold text-slate-700">{{ visit.employer_company }}</p>
                </div>
                <div class="ml-auto">
                    <p class="text-xs text-slate-400 font-medium">Request No.</p>
                    <p class="font-mono font-bold text-teal-700">{{ audResult?.request_number ?? 'New' }}</p>
                </div>
            </div>

            <!-- ── INSTRUMENT INFO ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Instrument & Exam Info</h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Audiometer Used</label>
                        <Input v-model="form.audiometer_used" :disabled="isReleased" class="h-8 text-sm" placeholder="e.g. GSI 18"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Last Calibrated</label>
                        <Input v-model="form.last_calibrated" :disabled="isReleased" class="h-8 text-sm" placeholder="Date calibrated"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Examiner</label>
                        <Input v-model="form.examiner" :disabled="isReleased" class="h-8 text-sm" placeholder="Examiner name"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Reason for Audiometry</label>
                        <Input v-model="form.reason_for_audiometry" :disabled="isReleased" class="h-8 text-sm" placeholder="Reason / indication"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Result Date</label>
                        <Input v-model="form.result_date" type="date" :disabled="isReleased" class="h-8 text-sm"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Result Time</label>
                        <Input v-model="form.result_time" type="time" :disabled="isReleased" class="h-8 text-sm"/>
                    </div>
                </div>
            </div>

            <!-- ── CASE HISTORY ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Case History</h2>
                </div>
                <div class="p-5 space-y-3">
                    <div v-for="q in caseHistoryQuestions" :key="q.key"
                        class="flex items-start gap-4 py-2 border-b border-slate-50 last:border-0">
                        <div class="flex-1 text-sm text-slate-700">{{ q.label }}</div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" :name="q.key" :value="false" v-model="form[q.key]"
                                    :disabled="isReleased"
                                    class="accent-slate-600"/>
                                <span class="text-xs font-semibold text-slate-600">NO</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer">
                                <input type="radio" :name="q.key" :value="true" v-model="form[q.key]"
                                    :disabled="isReleased"
                                    class="accent-teal-600"/>
                                <span class="text-xs font-semibold text-teal-700">YES</span>
                            </label>
                            <div v-if="q.hasWhen && form[q.key]" class="ml-2">
                                <Input v-model="form[q.whenKey]" :placeholder="q.whenLabel"
                                    :disabled="isReleased" class="h-7 text-xs w-40"/>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── VISUAL / OTOSCOPIC ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Visual / Otoscopic Inspection</h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Right Ear</label>
                        <textarea v-model="form.otoscopic_right_ear" :disabled="isReleased"
                            rows="2" placeholder="Right ear findings..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:opacity-60"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Left Ear</label>
                        <textarea v-model="form.otoscopic_left_ear" :disabled="isReleased"
                            rows="2" placeholder="Left ear findings..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:opacity-60"/>
                    </div>
                </div>
            </div>

            <!-- ── PURE TONE TABLE ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Pure Tone Screening Audiometry (dB HL)</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Enter threshold values in decibels. Average auto-calculates.</p>
                </div>
                <div class="p-5 overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr>
                                <th class="text-left px-3 py-2 text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 w-16">Ear</th>
                                <th v-for="col in freqCols" :key="col.label"
                                    class="px-2 py-2 text-center text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 w-16">
                                    {{ col.label }}
                                </th>
                                <th class="px-3 py-2 text-center text-xs font-bold text-slate-600 bg-slate-100 border border-slate-200 w-20">AVG</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Right Ear -->
                            <tr>
                                <td class="px-3 py-2 border border-slate-200 font-bold text-xs" style="color:#dc2626; background:#fef2f2;">
                                    R.E.
                                </td>
                                <td v-for="col in freqCols" :key="col.reKey"
                                    class="px-1 py-1 border border-slate-200 text-center">
                                    <input
                                        v-model.number="form[col.reKey]"
                                        type="number" step="5" min="-10" max="140"
                                        :disabled="isReleased"
                                        class="w-full text-center text-sm font-semibold rounded border-0 bg-transparent focus:outline-none focus:ring-1 focus:ring-red-400 disabled:opacity-60 py-1"
                                        style="color:#dc2626;"
                                        placeholder="—"/>
                                </td>
                                <td class="px-3 py-2 border border-slate-200 text-center font-black text-sm" style="color:#dc2626; background:#fef2f2;">
                                    {{ form.re_average !== null ? form.re_average : '—' }}
                                </td>
                            </tr>
                            <!-- Left Ear -->
                            <tr>
                                <td class="px-3 py-2 border border-slate-200 font-bold text-xs" style="color:#2563eb; background:#eff6ff;">
                                    L.E.
                                </td>
                                <td v-for="col in freqCols" :key="col.leKey"
                                    class="px-1 py-1 border border-slate-200 text-center">
                                    <input
                                        v-model.number="form[col.leKey]"
                                        type="number" step="5" min="-10" max="140"
                                        :disabled="isReleased"
                                        class="w-full text-center text-sm font-semibold rounded border-0 bg-transparent focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:opacity-60 py-1"
                                        style="color:#2563eb;"
                                        placeholder="—"/>
                                </td>
                                <td class="px-3 py-2 border border-slate-200 text-center font-black text-sm" style="color:#2563eb; background:#eff6ff;">
                                    {{ form.le_average !== null ? form.le_average : '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ── LIVE AUDIOGRAM CHART ── -->
                <div class="px-5 pb-5">
                    <div class="border border-slate-200 rounded-xl overflow-hidden bg-white">
                        <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-600">Audiogram</span>
                            <div class="flex items-center gap-4 text-xs font-semibold">
                                <span class="flex items-center gap-1.5" style="color:#dc2626;">
                                    <svg width="14" height="14" viewBox="0 0 14 14">
                                        <circle cx="7" cy="7" r="5" fill="none" stroke="#dc2626" stroke-width="2"/>
                                    </svg>
                                    Right Ear (O)
                                </span>
                                <span class="flex items-center gap-1.5" style="color:#2563eb;">
                                    <svg width="14" height="14" viewBox="0 0 14 14">
                                        <line x1="2" y1="2" x2="12" y2="12" stroke="#2563eb" stroke-width="2"/>
                                        <line x1="12" y1="2" x2="2" y2="12" stroke="#2563eb" stroke-width="2"/>
                                    </svg>
                                    Left Ear (X)
                                </span>
                            </div>
                        </div>
                        <div class="p-2">
                            <svg :width="CHART_W" :height="CHART_H" :viewBox="`0 0 ${CHART_W} ${CHART_H}`"
                                style="width:100%;height:auto;display:block;">

                                <!-- Normal hearing zone (0-25 dB) -->
                                <rect
                                    :x="MARGIN.left" :y="normalZoneY1"
                                    :width="PLOT_W" :height="normalZoneY2 - normalZoneY1"
                                    fill="#d1fae5" fill-opacity="0.4"/>

                                <!-- Gridlines horizontal (dB) -->
                                <g v-for="line in dbGridLines" :key="line.db">
                                    <line
                                        :x1="MARGIN.left" :y1="line.y"
                                        :x2="MARGIN.left + PLOT_W" :y2="line.y"
                                        :stroke="line.db === 0 ? '#6b7280' : '#e5e7eb'"
                                        :stroke-width="line.db === 0 ? 1.5 : 1"
                                        stroke-dasharray="line.db === 0 ? 'none' : '3,3'"/>
                                    <text
                                        :x="MARGIN.left - 6" :y="line.y + 4"
                                        text-anchor="end" font-size="9" fill="#9ca3af">
                                        {{ line.db }}
                                    </text>
                                </g>

                                <!-- Gridlines vertical (frequency) -->
                                <g v-for="gf in freqGridLines" :key="gf.hz">
                                    <line
                                        :x1="gf.x" :y1="MARGIN.top"
                                        :x2="gf.x" :y2="MARGIN.top + PLOT_H"
                                        stroke="#e5e7eb" stroke-width="1"/>
                                    <text
                                        :x="gf.x" :y="MARGIN.top + PLOT_H + 16"
                                        text-anchor="middle" font-size="9" fill="#6b7280">
                                        {{ gf.hz >= 1000 ? (gf.hz / 1000) + 'k' : gf.hz }}
                                    </text>
                                </g>

                                <!-- Axis labels -->
                                <text
                                    :x="MARGIN.left + PLOT_W / 2" :y="CHART_H - 2"
                                    text-anchor="middle" font-size="10" fill="#6b7280" font-weight="600">
                                    Frequency (Hz)
                                </text>
                                <text
                                    :x="12" :y="MARGIN.top + PLOT_H / 2"
                                    text-anchor="middle" font-size="10" fill="#6b7280" font-weight="600"
                                    :transform="`rotate(-90, 12, ${MARGIN.top + PLOT_H / 2})`">
                                    dB HL
                                </text>

                                <!-- Border box -->
                                <rect
                                    :x="MARGIN.left" :y="MARGIN.top"
                                    :width="PLOT_W" :height="PLOT_H"
                                    fill="none" stroke="#d1d5db" stroke-width="1"/>

                                <!-- Right Ear lines (O — red) -->
                                <g v-for="(seg, i) in reSegments" :key="`re-seg-${i}`">
                                    <line
                                        :x1="parseFloat(seg.split(' ')[0].split(',')[0])"
                                        :y1="parseFloat(seg.split(' ')[0].split(',')[1])"
                                        :x2="parseFloat(seg.split(' ')[1].split(',')[0])"
                                        :y2="parseFloat(seg.split(' ')[1].split(',')[1])"
                                        stroke="#dc2626" stroke-width="1.5"/>
                                </g>
                                <!-- Right Ear O markers -->
                                <g v-for="(pt, i) in rePoints" :key="`re-pt-${i}`">
                                    <circle v-if="pt"
                                        :cx="pt.x" :cy="pt.y" r="5"
                                        fill="white" stroke="#dc2626" stroke-width="2"/>
                                </g>

                                <!-- Left Ear lines (X — blue) -->
                                <g v-for="(seg, i) in leSegments" :key="`le-seg-${i}`">
                                    <line
                                        :x1="parseFloat(seg.split(' ')[0].split(',')[0])"
                                        :y1="parseFloat(seg.split(' ')[0].split(',')[1])"
                                        :x2="parseFloat(seg.split(' ')[1].split(',')[0])"
                                        :y2="parseFloat(seg.split(' ')[1].split(',')[1])"
                                        stroke="#2563eb" stroke-width="1.5"/>
                                </g>
                                <!-- Left Ear X markers -->
                                <g v-for="(pt, i) in lePoints" :key="`le-pt-${i}`">
                                    <g v-if="pt">
                                        <line :x1="pt.x - 5" :y1="pt.y - 5" :x2="pt.x + 5" :y2="pt.y + 5"
                                            stroke="#2563eb" stroke-width="2"/>
                                        <line :x1="pt.x + 5" :y1="pt.y - 5" :x2="pt.x - 5" :y2="pt.y + 5"
                                            stroke="#2563eb" stroke-width="2"/>
                                    </g>
                                </g>

                            </svg>
                        </div>
                        <div class="px-4 py-1.5 bg-emerald-50 border-t border-emerald-100">
                            <p class="text-xs text-emerald-700 font-medium">
                                <span class="font-bold">Green zone (0–25 dB):</span> Normal hearing range
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── REMARKS ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Remarks</h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:#dc2626;">Right Ear</label>
                        <textarea v-model="form.remarks_right_ear" :disabled="isReleased"
                            rows="3" placeholder="Right ear remarks..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-red-400 disabled:opacity-60"/>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:#2563eb;">Left Ear</label>
                        <textarea v-model="form.remarks_left_ear" :disabled="isReleased"
                            rows="3" placeholder="Left ear remarks..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:opacity-60"/>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Recommendations</label>
                        <textarea v-model="form.recommendations" :disabled="isReleased"
                            rows="2" placeholder="Recommendations..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm resize-none focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:opacity-60"/>
                    </div>
                </div>
            </div>

            <!-- ── STAFF / SIGNATURES ── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-700">Staff & Signatures</h2>
                </div>
                <div class="p-5 grid grid-cols-2 gap-6">
                    <!-- Examined By -->
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Examined By</p>
                        <div v-if="staffList?.length" class="flex flex-wrap gap-1.5 mb-2">
                            <button v-for="s in staffList" :key="s.id"
                                type="button"
                                :disabled="isReleased"
                                @click="selectExaminer(s)"
                                class="text-xs px-2.5 py-1 rounded-full border transition-all disabled:opacity-50"
                                :style="form.examined_by_name === s.name
                                    ? 'background:#0d9488;color:white;border-color:#0d9488;'
                                    : 'background:white;color:#475569;border-color:#e2e8f0;'">
                                {{ s.name }}
                            </button>
                        </div>
                        <Input v-model="form.examined_by_name" :disabled="isReleased" class="h-8 text-xs" placeholder="Name"/>
                        <Input v-model="form.examined_by_license" :disabled="isReleased" class="h-8 text-xs" placeholder="License No."/>
                    </div>
                    <!-- Noted By -->
                    <div class="space-y-2">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Noted By</p>
                        <div v-if="staffList?.length" class="flex flex-wrap gap-1.5 mb-2">
                            <button v-for="s in staffList" :key="s.id"
                                type="button"
                                :disabled="isReleased"
                                @click="selectNotedBy(s)"
                                class="text-xs px-2.5 py-1 rounded-full border transition-all disabled:opacity-50"
                                :style="form.noted_by_name === s.name
                                    ? 'background:#0d9488;color:white;border-color:#0d9488;'
                                    : 'background:white;color:#475569;border-color:#e2e8f0;'">
                                {{ s.name }}
                            </button>
                        </div>
                        <Input v-model="form.noted_by_name" :disabled="isReleased" class="h-8 text-xs" placeholder="Name"/>
                        <Input v-model="form.noted_by_license" :disabled="isReleased" class="h-8 text-xs" placeholder="License No."/>
                    </div>
                </div>
            </div>

            <!-- ── ACTION BUTTONS ── -->
            <div v-if="!isReleased" class="space-y-3 pb-6">

                <!-- Form errors -->
                <div v-if="Object.keys(form.errors).length"
                    class="bg-red-50 border border-red-200 rounded-xl px-4 py-3">
                    <p class="text-xs font-bold text-red-700 mb-1">Please fix the following errors:</p>
                    <ul class="list-disc list-inside space-y-0.5">
                        <li v-for="(msg, field) in form.errors" :key="field"
                            class="text-xs text-red-600">{{ msg }}</li>
                    </ul>
                </div>

                <div class="flex items-center justify-between">
                    <Link :href="route('audiometry.index')">
                        <Button variant="outline" size="sm" class="text-xs">Back to List</Button>
                    </Link>
                    <div class="flex items-center gap-2">
                        <Button
                            size="sm"
                            class="text-xs gap-1.5"
                            style="background:#64748b; color:white;"
                            :disabled="form.processing"
                            @click="submit(false)">
                            <Save class="w-3.5 h-3.5"/>
                            {{ form.processing && !form.release ? 'Saving...' : 'Save Draft' }}
                        </Button>
                        <Button
                            size="sm"
                            class="text-xs gap-1.5"
                            style="background:#0d9488; color:white;"
                            :disabled="form.processing"
                            @click="submit(true)">
                            <CheckCircle2 class="w-3.5 h-3.5"/>
                            {{ form.processing && form.release ? 'Releasing...' : 'Save & Release' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- View-only notice when released -->
            <div v-else class="flex items-center justify-between pb-6">
                <p class="text-xs text-slate-400 italic">Results have been released — view only.</p>
                <a :href="route('audiometry.print', visit.id)" target="_blank">
                    <Button size="sm" class="text-xs gap-1.5" style="background:#0d9488; color:white;">
                        <Printer class="w-3.5 h-3.5"/> Print Results
                    </Button>
                </a>
            </div>

        </div>
    </AppLayout>
</template>
