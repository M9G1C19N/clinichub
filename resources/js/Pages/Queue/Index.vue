<script setup>
import { ref, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import {
    Dialog, DialogContent, DialogHeader,
    DialogTitle, DialogDescription,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'

import {
    FlaskConical, ScanLine, TestTube, Stethoscope, HeartPulse,
    Monitor, Tv2, Bell, ChevronRight,
    AlertTriangle, Users, Activity, Printer, Lock,
} from 'lucide-vue-next'
import { printQueueSlip } from '@/utils/printQueueSlip.js'

const page = usePage()

const props = defineProps({
    tickets:   Array,
    roomStats: Object,
    summary:   Object,
    counters:  Array,
})

// ── Issue Ticket Modal ─────────────────────────────────
const showIssueModal  = ref(false)
const patientSearch   = ref('')
const patientResults  = ref([])
const selectedPatient = ref(null)
const searching       = ref(false)

const issueForm = ref({
    patient_id:          null,
    visit_type:          'opd',
    priority:            'regular',
    queue_counter_id:    props.counters[0]?.id ?? null,
    services_requested:  [],
    employer_company:    '',
    chief_complaint:     '',
})

// All available services grouped
const serviceGroups = [
    {
        group: 'Laboratory',
        room:  'laboratory',
        color: 'blue',
        services: [
            { code: 'CBC',          label: 'CBC' },
            { code: 'UA',           label: 'Urinalysis' },
            { code: 'FECALYSIS',    label: 'Fecalysis' },
            { code: 'BLOODTYPING',  label: 'Blood Typing' },
            { code: 'FBS',          label: 'FBS' },
            { code: 'BUN',          label: 'BUN' },
            { code: 'CREATININE',   label: 'Creatinine' },
            { code: 'URICACID',     label: 'Uric Acid' },
            { code: 'CHOLESTEROL',  label: 'Cholesterol' },
            { code: 'TRIGLYCERIDES',label: 'Triglycerides' },
            { code: 'HDLLDL',       label: 'HDL/LDL' },
            { code: 'SGOT',         label: 'SGOT' },
            { code: 'SGPT',         label: 'SGPT' },
            { code: 'HBSAG',        label: 'HBsAg' },
            { code: 'VDRL',         label: 'VDRL' },
            { code: 'PREGNANCY',    label: 'Pregnancy Test' },
        ],
    },
    {
        group: 'X-Ray & Ultrasound',
        room:  'xray_utz',
        color: 'purple',
        services: [
            { code: 'CXRPA', label: 'Chest X-Ray (PA)' },
            { code: 'UTZ',   label: 'Ultrasound' },
            { code: 'ECG',   label: 'ECG' },
        ],
    },
    {
        group: 'Drug Test',
        room:  'drug_test',
        color: 'rose',
        services: [
            { code: 'DRUGTEST', label: 'Drug Test (THC + MET)' },
            { code: 'MET',      label: 'Methamphetamine' },
            { code: 'THC',      label: 'Marijuana (THC)' },
        ],
    },
    {
        group: 'Consultation',
        room:  'interview_room',
        color: 'emerald',
        services: [
            { code: 'OPD',          label: 'OPD Consultation' },
            { code: 'CONSULTATION', label: 'Doctor Consultation' },
        ],
    },
]

// Toggle service selection
function toggleService(code) {
    const idx = issueForm.value.services_requested.indexOf(code)
    if (idx === -1) {
        issueForm.value.services_requested.push(code)
    } else {
        issueForm.value.services_requested.splice(idx, 1)
    }
}

function isSelected(code) {
    return issueForm.value.services_requested.includes(code)
}

// Patient search
let searchTimer = null
function onPatientSearch() {
    clearTimeout(searchTimer)
    if (patientSearch.value.length < 2) {
        patientResults.value = []
        return
    }
    searching.value = true
    searchTimer = setTimeout(async () => {
        try {
            const res = await fetch(route('queue.search-patient') + '?q=' + patientSearch.value)
            patientResults.value = await res.json()
        } finally {
            searching.value = false
        }
    }, 350)
}

function selectPatient(patient) {
    selectedPatient.value       = patient
    issueForm.value.patient_id  = patient.id
    issueForm.value.visit_type  = patient.visit_type
    patientSearch.value         = patient.full_name
    patientResults.value        = []
}

function clearPatient() {
    selectedPatient.value      = null
    issueForm.value.patient_id = null
    patientSearch.value        = ''
    patientResults.value       = []
}

// ── Queue Slip Print Modal ─────────────────────────────
const slipTicket    = ref(null)
const showSlipModal = ref(false)

// Auto-show slip modal when a newTicket is flashed
watch(() => page.props.flash?.newTicket, (ticket) => {
    if (ticket) {
        slipTicket.value    = ticket
        showSlipModal.value = true
    }
}, { immediate: true })

function submitIssueTicket() {
    router.post(route('queue.issue'), issueForm.value, {
        onSuccess: () => {
            showIssueModal.value               = false
            selectedPatient.value              = null
            patientSearch.value                = ''
            issueForm.value.services_requested = []
            issueForm.value.employer_company   = ''
            issueForm.value.chief_complaint    = ''
        },
    })
}

function openIssueModal() {
    showIssueModal.value = true
}

// ── Queue actions ──────────────────────────────────────
function callNext(room) {
    router.post(route('queue.call-next'), { room }, { preserveScroll: true })
}

function cancelTicket(ticketId) {
    if (!confirm('Cancel this ticket?')) return
    router.patch(route('queue.cancel', ticketId), {}, { preserveScroll: true })
}

// ── Helpers ────────────────────────────────────────────
const statusBadge = {
    waiting:     'bg-slate-100 text-slate-600',
    routing:     'bg-blue-100 text-blue-700',
    in_progress: 'bg-amber-100 text-amber-700',
    completed:   'bg-emerald-100 text-emerald-700',
    cancelled:   'bg-red-100 text-red-600',
    no_show:     'bg-orange-100 text-orange-600',
}

const priorityBadge = {
    urgent:   'bg-red-500 text-white',
    pregnant: 'bg-pink-100 text-pink-700',
    pwd:      'bg-blue-100 text-blue-700',
    senior:   'bg-amber-100 text-amber-700',
    regular:  'bg-slate-100 text-slate-500',
}

const roomMeta = {
    laboratory:     { color: '#3B82F6', bg: '#EFF6FF', icon: FlaskConical,  label: 'Lab' },
    xray_utz:       { color: '#8B5CF6', bg: '#F5F3FF', icon: ScanLine,      label: 'X-Ray/UTZ' },
    drug_test:      { color: '#F43F5E', bg: '#FFF1F2', icon: TestTube,      label: 'Drug Test' },
    nurse_station:  { color: '#10B981', bg: '#F0FDF4', icon: HeartPulse,    label: 'Nurse' },
    interview_room: { color: '#0EA5E9', bg: '#F0F9FF', icon: Stethoscope,   label: 'Doctor', gated: true },
}

// legacy alias used in ticket rows
const roomIcon = {
    laboratory:     FlaskConical,
    xray_utz:       ScanLine,
    drug_test:      TestTube,
    nurse_station:  HeartPulse,
    interview_room: Stethoscope,
}
const roomIconMap = roomIcon
</script>

<template>
    <AppLayout title="Queue">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Queue Management</h1>
                    <p class="text-slate-400 text-xs mt-0.5">
                        Today · {{ new Date().toLocaleDateString('en-PH', { weekday:'long', year:'numeric', month:'long', day:'numeric' }) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- TV Display link -->
                        <a :href="route('queue.display')" target="_blank">
                            <Button variant="outline" size="sm" class="gap-2">
                                <Tv2 class="w-4 h-4" />
                                TV Display
                            </Button>
                        </a>
                <Button @click="openIssueModal" style="background-color:#1B4F9B" class="gap-2">
                    <Monitor class="w-4 h-4" />
                    Issue Ticket
                </Button>
                </div>
            </div>
        </template>

        <!-- ── Summary Cards ─────────────────────────── -->
        <div class="grid grid-cols-5 gap-4 mb-5">
            <div v-for="(val, key) in summary" :key="key"
                class="bg-card rounded-xl border shadow-sm p-4 text-center">
                <p class="text-2xl font-bold text-slate-800">{{ val }}</p>
                <p class="text-xs text-muted-foreground capitalize mt-0.5">
                    {{ key.replace('_', ' ') }}
                </p>
            </div>
        </div>

        <!-- ── Room Status Cards ──────────────────────── -->
        <!-- Flow legend -->
        <div class="flex items-center gap-2 mb-3 text-xs text-slate-400">
            <span class="font-semibold text-slate-500">Queue Flow:</span>
            <span class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-rose-50 border border-rose-200 text-rose-600 font-semibold">
                <TestTube class="w-3 h-3"/> Drug Test
            </span>
            <span class="text-slate-300 font-bold">→</span>
            <span class="flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 border border-slate-200 text-slate-500 font-semibold">
                <FlaskConical class="w-3 h-3"/> Lab
                <span class="text-slate-300">·</span>
                <ScanLine class="w-3 h-3"/> X-Ray
                <span class="text-slate-300">·</span>
                <HeartPulse class="w-3 h-3"/> Nurse
                <span class="ml-1 text-slate-400 font-normal italic">(parallel)</span>
            </span>
            <span class="text-slate-300 font-bold">→</span>
            <span class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-sky-50 border border-sky-200 text-sky-600 font-semibold">
                <Lock class="w-3 h-3"/> Doctor
                <span class="ml-0.5 font-normal italic text-sky-400">(unlocks after all parallel done)</span>
            </span>
        </div>

        <div class="grid grid-cols-5 gap-3 mb-5">
            <div v-for="(stat, room) in roomStats" :key="room"
                class="bg-card rounded-xl border shadow-sm overflow-hidden flex flex-col"
                :class="roomMeta[room]?.gated ? 'ring-1 ring-sky-200' : ''">

                <!-- Room header -->
                <div class="px-3 py-2.5 flex items-center gap-2 border-b"
                    :style="{ borderLeft: `4px solid ${roomMeta[room]?.color ?? '#94A3B8'}` }">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                        :style="{ background: roomMeta[room]?.bg ?? '#F8FAFC' }">
                        <component :is="roomMeta[room]?.icon"
                            class="w-3.5 h-3.5"
                            :style="{ color: roomMeta[room]?.color ?? '#94A3B8' }" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-700 truncate">{{ stat.label }}</p>
                        <span v-if="roomMeta[room]?.gated"
                            class="inline-flex items-center gap-0.5 text-[10px] text-sky-500 font-semibold">
                            <Lock class="w-2.5 h-2.5"/> Gated
                        </span>
                        <span v-else class="text-[10px] text-slate-400">Parallel</span>
                    </div>
                </div>

                <!-- Stats grid -->
                <div class="p-3 grid grid-cols-2 gap-1.5 flex-1">
                    <div class="text-center p-1.5 rounded-lg bg-amber-50">
                        <p class="text-lg font-bold text-amber-600">{{ stat.waiting }}</p>
                        <p class="text-[10px] text-amber-600/70 font-medium">Waiting</p>
                    </div>
                    <div class="text-center p-1.5 rounded-lg bg-emerald-50">
                        <p class="text-lg font-bold text-emerald-600">{{ stat.serving }}</p>
                        <p class="text-[10px] text-emerald-600/70 font-medium">Serving</p>
                    </div>
                    <div class="text-center p-1.5 rounded-lg bg-slate-50">
                        <p class="text-lg font-bold text-slate-600">{{ stat.completed }}</p>
                        <p class="text-[10px] text-slate-500 font-medium">Done</p>
                    </div>
                    <div class="text-center p-1.5 rounded-lg bg-red-50">
                        <p class="text-lg font-bold text-red-400">{{ stat.no_show }}</p>
                        <p class="text-[10px] text-red-400/70 font-medium">No Show</p>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="px-3 pb-3 space-y-1.5">
                    <a :href="route('queue.room', room)" target="_blank"
                        class="w-full flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold text-white transition-all hover:opacity-90"
                        :style="{ background: roomMeta[room]?.color ?? '#1B4F9B' }">
                        <Monitor class="w-3 h-3" />
                        Room Screen
                    </a>
                    <Button variant="outline" size="sm" class="w-full text-xs gap-1.5 h-7"
                        @click="callNext(room)">
                        <Bell class="w-3 h-3" />
                        Call Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- ── Today's Ticket List ────────────────────── -->
        <div class="bg-card rounded-xl border shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                    <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                        Today's Queue
                    </h3>
                    <span class="text-xs text-muted-foreground">({{ tickets.length }} tickets)</span>
                </div>
            </div>

            <!-- Empty -->
            <div v-if="tickets.length === 0" class="py-16 text-center">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mx-auto mb-3">
                    <Activity class="w-7 h-7 text-slate-300" />
                </div>
                <p class="text-sm font-medium text-slate-400">No tickets issued today</p>
                <p class="text-xs text-slate-300 mt-1">Click "Issue Ticket" to get started</p>
            </div>

            <!-- Ticket rows -->
            <div v-else class="divide-y divide-border">
                <div v-for="ticket in tickets" :key="ticket.id"
                    class="px-5 py-3.5 flex items-center gap-4 hover:bg-muted/30 transition-colors group">

                    <!-- Ticket number -->
                    <div class="w-20 flex-shrink-0 text-center">
                        <p class="text-lg font-black text-slate-800 font-mono">{{ ticket.ticket_number }}</p>
                        <p class="text-xs text-muted-foreground">{{ ticket.issued_at }}</p>
                    </div>

                    <Separator orientation="vertical" class="h-10"/>

                    <!-- Patient -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ ticket.patient_name }}</p>
                            <span v-if="ticket.priority !== 'regular'"
                                :class="['text-xs font-bold px-2 py-0.5 rounded-full', priorityBadge[ticket.priority]]">
                                {{ ticket.priority.toUpperCase() }}
                            </span>
                        </div>
                        <p class="text-xs text-muted-foreground">{{ ticket.patient_code }}</p>
                    </div>

                    <!-- Visit type -->
                    <div class="w-28 flex-shrink-0">
                        <span :class="['text-xs font-semibold px-2 py-1 rounded-full',
                            ticket.visit_type === 'pre_employment'
                                ? 'bg-purple-100 text-purple-700'
                                : 'bg-blue-100 text-blue-700']">
                            {{ ticket.visit_type === 'pre_employment' ? 'Pre-Emp' : ticket.visit_type.toUpperCase() }}
                        </span>
                    </div>

                    <!-- Room routing — parallel mode display -->
                    <div class="flex items-center gap-1 flex-shrink-0 flex-wrap max-w-xs">
                        <template v-for="(room, i) in ticket.rooms" :key="room.room">
                            <!-- Separator: → before gated rooms, · between parallel rooms -->
                            <span v-if="i > 0" class="text-slate-300 text-xs font-bold">
                                {{ room.is_gated ? '→' : '·' }}
                            </span>
                            <div :class="[
                                'flex items-center gap-1 px-1.5 py-0.5 rounded-md text-xs font-semibold border transition-all',
                                room.status === 'completed' ? 'bg-emerald-50 border-emerald-200 text-emerald-600 line-through opacity-50' :
                                room.status === 'serving'   ? 'bg-amber-50 border-amber-300 text-amber-700 ring-1 ring-amber-300' :
                                room.status === 'calling'   ? 'bg-blue-50 border-blue-300 text-blue-700 animate-pulse' :
                                room.status === 'locked'    ? 'bg-slate-100 border-slate-200 text-slate-300 opacity-50' :
                                room.status === 'no_show'   ? 'bg-red-50 border-red-200 text-red-400 line-through opacity-50' :
                                room.status === 'skipped'   ? 'bg-orange-50 border-orange-200 text-orange-400 line-through opacity-50' :
                                'bg-slate-50 border-slate-200 text-slate-500'
                            ]"
                            :title="`${roomMeta[room.room]?.label ?? room.room} · ${room.status}`">
                                <component :is="roomIcon[room.room]" class="w-3 h-3 flex-shrink-0" />
                                <span>{{ room.queue_number }}</span>
                                <Lock v-if="room.status === 'locked'" class="w-2.5 h-2.5 ml-0.5 text-slate-400" />
                            </div>
                        </template>

                        <!-- Pending count badge when doctor room is still locked -->
                        <span v-if="ticket.interview_locked && ticket.parallel_pending > 0"
                            class="text-[10px] text-sky-500 bg-sky-50 border border-sky-200 px-1.5 py-0.5 rounded font-semibold ml-0.5"
                            :title="`${ticket.parallel_pending} parallel room(s) still in progress`">
                            {{ ticket.parallel_pending }} left
                        </span>
                    </div>

                    <!-- Status -->
                    <div class="w-24 flex-shrink-0 text-center">
                        <span :class="['text-xs font-semibold px-2.5 py-1 rounded-full', statusBadge[ticket.status]]">
                            {{ ticket.status.replace('_', ' ') }}
                        </span>
                    </div>

                    <!-- Actions -->
                    <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <Button variant="ghost" size="sm"
                            class="text-xs text-slate-500 hover:text-slate-700 hover:bg-slate-100 gap-1"
                            @click="slipTicket = ticket; showSlipModal = true">
                            <Printer class="w-3.5 h-3.5"/>
                            Print
                        </Button>
                        <Button v-if="ticket.status !== 'completed' && ticket.status !== 'cancelled'"
                            variant="ghost" size="sm"
                            class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50"
                            @click="cancelTicket(ticket.id)">
                            Cancel
                        </Button>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── ISSUE TICKET MODAL ─────────────────────── -->
        <Dialog :open="showIssueModal" @update:open="val => showIssueModal = val">
            <DialogContent class="sm:max-w-2xl max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Issue Queue Ticket</DialogTitle>
                    <DialogDescription>
                        Search for a patient, select services, and assign to a counter.
                    </DialogDescription>
                </DialogHeader>

                <div class="space-y-5 pt-2">

                    <!-- Patient Search -->
                    <div class="space-y-1.5">
                        <Label>Patient <span class="text-red-500">*</span></Label>
                        <div class="relative">
                            <Input
                                v-model="patientSearch"
                                @input="onPatientSearch"
                                placeholder="Search by name, code, or contact..."
                                :class="selectedPatient ? 'border-emerald-400 bg-emerald-50' : ''"
                            />
                            <!-- Selected patient display -->
                            <div v-if="selectedPatient"
                                class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-2">
                                <span class="text-xs font-semibold text-emerald-600">✓ Selected</span>
                                <button type="button" @click="clearPatient"
                                    class="text-xs text-red-400 hover:text-red-600">✕</button>
                            </div>
                        </div>

                        <!-- Search results dropdown -->
                        <div v-if="patientResults.length > 0"
                            class="border rounded-xl shadow-lg bg-white divide-y z-50">
                            <button v-for="p in patientResults" :key="p.id"
                                type="button"
                                @click="selectPatient(p)"
                                class="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-50 text-left transition-colors">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                    style="background:#1B4F9B">
                                    {{ p.full_name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-slate-800">{{ p.full_name }}</p>
                                    <p class="text-xs text-slate-400">{{ p.patient_code }} · {{ p.age_sex }}</p>
                                </div>
                                <span :class="['ml-auto text-xs font-semibold px-2 py-0.5 rounded-full',
                                    p.visit_type === 'pre_employment' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700']">
                                    {{ p.visit_type === 'pre_employment' ? 'Pre-Emp' : 'OPD' }}
                                </span>
                            </button>
                        </div>

                        <!-- Searching indicator -->
                        <p v-if="searching" class="text-xs text-muted-foreground animate-pulse">
                            Searching...
                        </p>
                    </div>

                    <!-- Visit Type + Priority + Counter -->
                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-1.5">
                            <Label>Visit Type</Label>
                            <Select v-model="issueForm.visit_type">
                                <SelectTrigger><SelectValue/></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="opd">OPD</SelectItem>
                                    <SelectItem value="pre_employment">Pre-Employment</SelectItem>
                                    <SelectItem value="follow_up">Follow-up</SelectItem>
                                    <SelectItem value="lab_only">Lab Only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Priority</Label>
                            <Select v-model="issueForm.priority">
                                <SelectTrigger><SelectValue/></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="regular">Regular</SelectItem>
                                    <SelectItem value="senior">Senior Citizen</SelectItem>
                                    <SelectItem value="pwd">PWD</SelectItem>
                                    <SelectItem value="pregnant">Pregnant</SelectItem>
                                    <SelectItem value="urgent">Urgent</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Counter</Label>
                            <Select v-model="issueForm.queue_counter_id">
                                <SelectTrigger><SelectValue/></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in counters" :key="c.id" :value="c.id">
                                        {{ c.counter_name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <!-- Employer (pre-employment only) -->
                    <div v-if="issueForm.visit_type === 'pre_employment'" class="space-y-1.5">
                        <Label>Employer Company</Label>
                        <Input v-model="issueForm.employer_company" placeholder="e.g. PGMC, TMC"/>
                    </div>

                    <!-- Chief Complaint (OPD only) -->
                    <div v-if="issueForm.visit_type === 'opd'" class="space-y-1.5">
                        <Label>Chief Complaint</Label>
                        <Input v-model="issueForm.chief_complaint" placeholder="e.g. Fever, cough, check-up"/>
                    </div>

                    <!-- Services Selection -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <Label>Services Requested <span class="text-red-500">*</span></Label>
                            <span class="text-xs text-muted-foreground">
                                {{ issueForm.services_requested.length }} selected
                            </span>
                        </div>

                        <div class="space-y-3">
                            <div v-for="group in serviceGroups" :key="group.group">
                                <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-2 flex items-center gap-2">
                                    <span>{{ group.group }}</span>
                                    <span class="text-xs normal-case font-normal">
                                        ({{ group.services.filter(s => isSelected(s.code)).length }} selected)
                                    </span>
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="svc in group.services" :key="svc.code"
                                        type="button"
                                        @click="toggleService(svc.code)"
                                        :class="[
                                            'px-3 py-1.5 text-xs font-semibold rounded-lg border-2 transition-all',
                                            isSelected(svc.code)
                                                ? group.color === 'blue'   ? 'border-blue-500 bg-blue-500 text-white' :
                                                  group.color === 'purple' ? 'border-purple-500 bg-purple-500 text-white' :
                                                  group.color === 'rose'   ? 'border-rose-500 bg-rose-500 text-white' :
                                                                             'border-emerald-500 bg-emerald-500 text-white'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                        ]">
                                        {{ svc.label }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Routing Preview -->
            <div v-if="issueForm.services_requested.length > 0"
                class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">
                    Routing Preview — Smart Engine
                </p>
                <div class="flex items-center gap-2 flex-wrap">

                    <!-- Drug Test — always first -->
                    <template v-if="issueForm.services_requested.some(s => ['DRUGTEST','MET','THC'].includes(s))">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-rose-100 border border-rose-200 text-rose-700 rounded-lg text-xs font-bold">
                            <TestTube class="w-3.5 h-3.5" />
                            Drug Test
                            <span class="text-rose-400 font-normal text-xs">(1st — priority)</span>
                        </div>
                        <ChevronRight class="w-4 h-4 text-slate-300" />
                    </template>

                    <!-- Laboratory -->
                    <template v-if="issueForm.services_requested.some(s =>
                        ['CBC','UA','FECALYSIS','BLOODTYPING','FBS','BUN','CREATININE',
                        'URICACID','CHOLESTEROL','TRIGLYCERIDES','HDLLDL','SGOT',
                        'SGPT','HBSAG','VDRL','PREGNANCY'].includes(s))">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-100 border border-blue-200 text-blue-700 rounded-lg text-xs font-bold">
                            <FlaskConical class="w-3.5 h-3.5" />
                            Laboratory
                        </div>
                        <ChevronRight class="w-4 h-4 text-slate-300" />
                    </template>

                    <!-- X-Ray & UTZ -->
                    <template v-if="issueForm.services_requested.some(s => ['CXRPA','UTZ','ECG','XRAY'].includes(s))">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-purple-100 border border-purple-200 text-purple-700 rounded-lg text-xs font-bold">
                            <ScanLine class="w-3.5 h-3.5" />
                            X-Ray & UTZ
                        </div>
                        <ChevronRight class="w-4 h-4 text-slate-300" />
                    </template>

                    <!-- Interview Room -->
                    <template v-if="issueForm.visit_type === 'opd' || issueForm.services_requested.some(s => ['OPD','CONSULTATION'].includes(s))">
                        <div class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-lg text-xs font-bold">
                            <Stethoscope class="w-3.5 h-3.5" />
                            Interview Room
                        </div>
                    </template>

                </div>
            </div>
                    <!-- Submit -->
                    <div class="flex justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" @click="showIssueModal = false">
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            :disabled="!issueForm.patient_id || issueForm.services_requested.length === 0"
                            @click="submitIssueTicket"
                            style="background-color:#1B4F9B"
                            class="gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                            </svg>
                            Issue Ticket & Route
                        </Button>
                    </div>

                </div>
            </DialogContent>
        </Dialog>

        <!-- ── QUEUE SLIP PRINT MODAL ─────────────────── -->
        <Teleport to="body">
            <div v-if="showSlipModal && slipTicket"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background:rgba(0,0,0,.55)">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden">
                    <!-- Header -->
                    <div class="px-5 py-4 border-b flex items-center justify-between" style="background:#0F2044">
                        <div class="flex items-center gap-2">
                            <Printer class="w-4 h-4 text-white"/>
                            <h2 class="text-sm font-bold text-white">Queue Slip</h2>
                        </div>
                        <button @click="showSlipModal = false"
                            class="text-white/60 hover:text-white text-xl leading-none w-7 h-7 flex items-center justify-center rounded-lg hover:bg-white/10">
                            &times;
                        </button>
                    </div>

                    <!-- Slip Preview -->
                    <div class="p-5">
                        <div class="border-2 border-slate-200 rounded-xl p-4 text-center space-y-2 bg-slate-50">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Saint Peter Diagnostics</p>
                            <div class="text-5xl font-black text-slate-800 tracking-wider font-mono leading-none py-2">
                                {{ slipTicket.ticket_number }}
                            </div>
                            <span class="inline-block text-xs font-bold px-3 py-1 rounded-full text-white"
                                :style="{
                                    background: slipTicket.priority === 'urgent'   ? '#dc2626' :
                                                slipTicket.priority === 'pregnant' ? '#db2777' :
                                                slipTicket.priority === 'pwd'      ? '#2563eb' :
                                                slipTicket.priority === 'senior'   ? '#d97706' : '#64748b'
                                }">
                                {{ (slipTicket.priority ?? 'regular').toUpperCase() }}
                            </span>
                            <p class="text-sm font-bold text-slate-800 pt-1">{{ slipTicket.patient_name }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ slipTicket.patient_code }}</p>
                            <div class="flex flex-wrap gap-1 justify-center pt-1">
                                <span v-for="svc in slipTicket.services" :key="svc"
                                    class="text-xs px-2 py-0.5 rounded bg-slate-200 text-slate-600 font-semibold">
                                    {{ svc }}
                                </span>
                            </div>
                            <div v-if="slipTicket.rooms?.length" class="pt-2 w-full">
                                <!-- Parallel rooms group (non-gated) -->
                                <div class="flex items-center justify-center gap-2 flex-wrap">
                                    <template v-for="(r, i) in slipTicket.rooms.filter(r => !r.is_gated)" :key="r.room">
                                        <span v-if="i > 0" class="text-slate-300 text-xs font-bold">·</span>
                                        <div class="text-center">
                                            <div class="text-xs text-slate-400 uppercase">{{ r.room_label }}</div>
                                            <div class="text-base font-black text-slate-800 font-mono">{{ r.queue_number }}</div>
                                        </div>
                                    </template>
                                </div>
                                <!-- Gated rooms (interview room — nurse + doctor) -->
                                <template v-for="r in slipTicket.rooms.filter(r => r.is_gated)" :key="r.room">
                                    <div class="flex items-center gap-1 justify-center mt-1 text-slate-400">
                                        <span class="text-xs font-bold">↓ after lab/xray</span>
                                    </div>
                                    <div class="text-center mt-0.5">
                                        <div class="text-xs text-slate-400 uppercase">{{ r.room_label }} (Nurse → Doctor)</div>
                                        <div class="text-base font-black text-slate-800 font-mono">{{ r.queue_number }}</div>
                                    </div>
                                </template>
                            </div>
                            <p class="text-xs text-slate-400 pt-1">{{ slipTicket.issued_at }}</p>
                        </div>
                    </div>

                    <div class="px-5 pb-5 flex gap-3">
                        <button @click="showSlipModal = false"
                            class="flex-1 py-2.5 border border-slate-200 rounded-xl text-sm text-slate-600 hover:bg-slate-50 font-semibold transition-colors">
                            Close
                        </button>
                        <button @click="printQueueSlip(slipTicket)"
                            class="flex-1 py-2.5 text-white rounded-xl text-sm font-bold transition-colors hover:opacity-90 flex items-center justify-center gap-2"
                            style="background:#1B4F9B">
                            <Printer class="w-4 h-4"/>
                            Print Slip
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
