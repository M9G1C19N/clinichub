<script setup>
import { ref, computed, watch } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Separator } from '@/components/ui/separator'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Search, FlaskConical, ScanLine, TestTube,
    Stethoscope, Tag, ChevronRight, Receipt,
    User, CheckCircle2, AlertTriangle, Monitor,
} from 'lucide-vue-next'


// ── SERVICE PACKAGES ──────────────────────────────
const servicePackages = computed(() => {
    const vt = form.visit_type

    // Consultation code depends on visit type
    const consultCode = {
        pre_employment: 'PE_CONSULT',
        annual_pe:      'ANNUAL_PE',
        exit_pe:        'EXIT_PE',
        opd:            'OPD',
        follow_up:      'OPD',
    }[vt] ?? null

    // Base services — PE types include Blood Chemistry, OPD does not
    const isPE = ['pre_employment','annual_pe','exit_pe'].includes(vt)
    const base5 = isPE
        ? ['CBC', 'BLOOD_CHEMISTRY', 'CXRPA', 'FECALYSIS', 'UA']
        : ['CBC', 'CXRPA', 'FECALYSIS', 'UA']
    if (consultCode) base5.push(consultCode)

    const labelMap = {
        pre_employment: 'Basic Pre-Employment Package',
        annual_pe:      'Basic Annual PE Package',
        exit_pe:        'Basic Exit PE Package',
        opd:            'Basic OPD Package',
        follow_up:      'Basic Follow-up Package',
    }

    if (!labelMap[vt]) return []

    return [{
        key:   'basic',
        label: labelMap[vt],
        color: isPE ? '#8B5CF6' : '#1B4F9B',
        desc:  isPE
            ? 'CBC · Blood Chemistry · Chest X-Ray · Stool Exam · Urinalysis · Consultation'
            : 'CBC · Chest X-Ray · Stool Exam · Urinalysis · Consultation',
        codes: base5,
    }]
})

function applyPackage(codes) {
    // Add all codes from package, don't remove existing selections
    codes.forEach(code => {
        if (!form.services.includes(code)) {
            form.services.push(code)
        }
    })
}

function clearServices() {
    form.services = []
}

const props = defineProps({
    patient:  Object,
    services: Array,
    counters: Array,
    checkin:  { type: Object, default: null },
})

const form = useForm({
    patient_id:         props.patient?.id ?? null,
    visit_type:         props.checkin?.visit_type ?? 'opd',
    employer_company:   props.checkin?.employer_company ?? '',
    chief_complaint:    props.checkin?.chief_complaint ?? '',
    referral_validated: false,
    services:           props.checkin?.services ?? [],
    priority:           props.checkin?.priority ?? 'regular',
    queue_counter_id:   props.counters[0]?.id ? String(props.counters[0].id) : null,
    discount_amount:    0,
    notes:              '',
    is_field_visit:     false,
    checkin_id:         props.checkin?.id ?? null,
})

// Patient search
const patientSearch   = ref(props.patient?.full_name ?? '')
const patientResults  = ref([])
const selectedPatient = ref(props.patient ?? null)
const searching       = ref(false)

let searchTimer = null
function onPatientSearch() {
    clearTimeout(searchTimer)
    if (patientSearch.value.length < 2) { patientResults.value = []; return }
    searching.value = true
    searchTimer = setTimeout(async () => {
        try {
            const res = await fetch(route('reception.search-patient') + '?q=' + patientSearch.value)
            patientResults.value = await res.json()
        } finally { searching.value = false }
    }, 350)
}

function selectPatient(p) {
    selectedPatient.value    = p
    form.patient_id          = p.id
    patientSearch.value      = p.full_name
    patientResults.value     = []
}

function clearPatient() {
    selectedPatient.value = null
    form.patient_id       = null
    patientSearch.value   = ''
}

// Services grouped by category
const serviceGroups = computed(() => {
    const groups = {}
    for (const svc of props.services) {
        if (!groups[svc.category]) groups[svc.category] = []
        groups[svc.category].push(svc)
    }
    return groups
})

function toggleService(code) {
    const idx = form.services.indexOf(code)
    idx === -1 ? form.services.push(code) : form.services.splice(idx, 1)
}

function isSelected(code) {
    return form.services.includes(code)
}

// Compute invoice preview
const selectedServices = computed(() =>
    props.services.filter(s => form.services.includes(s.service_code))
)

const subtotal = computed(() =>
    selectedServices.value.reduce((sum, s) => sum + parseFloat(s.base_price), 0)
)

const total = computed(() =>
    Math.max(0, subtotal.value - parseFloat(form.discount_amount || 0))
)

// Category config
const categoryConfig = {
    laboratory:   { label: 'Laboratory',      color: '#3B82F6', icon: FlaskConical },
    xray_utz:     { label: 'X-Ray & UTZ',     color: '#8B5CF6', icon: ScanLine },
    drug_test:    { label: 'Drug Test',        color: '#F43F5E', icon: TestTube },
    consultation: { label: 'Consultation',    color: '#10B981', icon: Stethoscope },
    procedure:    { label: 'Procedure',       color: '#F59E0B', icon: Tag },
    other:        { label: 'Other',           color: '#6B7280', icon: Tag },
}

function submit() {
    form.post(route('reception.store'))
}
</script>

<template>
    <AppLayout title="New Visit">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('reception.index')">
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Register New Visit</h1>
                    <p class="text-slate-400 text-xs mt-0.5">Select patient, services, and issue queue ticket</p>
                </div>
            </div>
        </template>

        <!-- Kiosk check-in banner -->
        <div v-if="checkin"
             class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-4 flex items-center gap-3">
            <Monitor class="w-4 h-4 text-amber-600 flex-shrink-0"/>
            <p class="text-sm text-amber-800">
                <strong>Kiosk Check-in</strong> — Services and details have been pre-filled from the patient's self-registration.
                Review and confirm before issuing the visit and invoice.
            </p>
        </div>

        <form @submit.prevent="submit">
            <div class="flex gap-5 items-start">

                <!-- ── LEFT: Invoice Preview ────────── -->
                <div class="w-100 flex-shrink-0 space-y-4">

                    <!-- Patient card -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Patient</p>

                        <div v-if="selectedPatient"
                            class="flex items-center gap-3 p-3 bg-emerald-50 border border-emerald-200 rounded-xl mb-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                style="background:#1B4F9B">
                                {{ selectedPatient.full_name.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-slate-800 truncate">{{ selectedPatient.full_name }}</p>
                                <p class="text-xs text-slate-500">{{ selectedPatient.patient_code }} · {{ selectedPatient.age_sex }}</p>
                            </div>
                            <button type="button" @click="clearPatient" class="text-slate-400 hover:text-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Search -->
                        <div v-if="!selectedPatient" class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                            <Input v-model="patientSearch" @input="onPatientSearch"
                                placeholder="Search patient..."
                                class="pl-9 text-sm"/>
                        </div>

                        <!-- Results -->
                        <div v-if="patientResults.length > 0"
                            class="mt-2 border rounded-xl shadow-lg bg-white divide-y overflow-hidden">
                            <button v-for="p in patientResults" :key="p.id"
                                type="button" @click="selectPatient(p)"
                                class="w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-slate-50 text-left">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
                                    style="background:#1B4F9B">
                                    {{ p.full_name.charAt(0) }}
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800">{{ p.full_name }}</p>
                                    <p class="text-xs text-slate-400">{{ p.patient_code }} · {{ p.age_sex }}</p>
                                </div>
                            </button>
                        </div>

                        <p v-if="searching" class="text-xs text-slate-400 text-center mt-2 animate-pulse">Searching...</p>

                        <!-- Register new patient link -->
                        <Link :href="route('patients.create')"
                            class="mt-3 flex items-center gap-1.5 text-xs text-blue-600 hover:text-blue-800 font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Register new patient
                        </Link>
                    </div>

                    <!-- Visit Config -->
                    <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Visit Details</p>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Visit Type</Label>
                            <Select :model-value="form.visit_type" @update:model-value="(val) => form.visit_type = val">
                                <SelectTrigger class="h-8 text-xs">
                                    <SelectValue/>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="opd">OPD (Out-Patient)</SelectItem>
                                    <SelectItem value="pre_employment">Pre-Employment PE</SelectItem>
                                    <SelectItem value="annual_pe">Annual PE</SelectItem>
                                    <SelectItem value="exit_pe">Exit PE</SelectItem>
                                    <SelectItem value="follow_up">Follow-up</SelectItem>
                                    <SelectItem value="lab_only">Lab Only</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div v-if="['pre_employment','annual_pe','exit_pe'].includes(form.visit_type)" class="space-y-1.5">
                            <Label class="text-xs">Employer Company</Label>
                            <Input v-model="form.employer_company" placeholder="e.g. TMC, PGMC" class="h-8 text-xs"/>
                        </div>

                        <div v-if="form.visit_type === 'opd'" class="space-y-1.5">
                            <Label class="text-xs">Chief Complaint</Label>
                            <Input v-model="form.chief_complaint" placeholder="e.g. Fever, cough" class="h-8 text-xs"/>
                        </div>
                        <!-- ── VISIT MODE TOGGLE ─────────────────── -->
                        <div class="space-y-2">
                            <Label class="text-xs">Visit Mode</Label>
                            <div class="grid grid-cols-2 gap-2">

                                <!-- In-Clinic -->
                                <button type="button"
                                    @click="form.is_field_visit = false"
                                    :class="[
                                        'flex flex-col items-center gap-1 p-3 rounded-xl border-2 text-xs font-semibold transition-all',
                                        !form.is_field_visit
                                            ? 'border-blue-500 bg-blue-50 text-blue-700'
                                            : 'border-slate-200 bg-white text-slate-400 hover:border-slate-300'
                                    ]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 8v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4"/>
                                    </svg>
                                    <span>In-Clinic</span>
                                    <span class="font-normal opacity-60 text-xs">Auto case no.</span>
                                </button>

                                <!-- Field / Off-Site -->
                                <button type="button"
                                    @click="form.is_field_visit = true"
                                    :class="[
                                        'flex flex-col items-center gap-1 p-3 rounded-xl border-2 text-xs font-semibold transition-all',
                                        form.is_field_visit
                                            ? 'border-amber-500 bg-amber-50 text-amber-700'
                                            : 'border-slate-200 bg-white text-slate-400 hover:border-slate-300'
                                    ]">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    <span>Field / Off-Site</span>
                                    <span class="font-normal opacity-60 text-xs">No case no. yet</span>
                                </button>
                            </div>

                            <!-- Warning shown when field mode selected -->
                            <div v-if="form.is_field_visit"
                                class="flex items-start gap-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg">
                                <svg class="w-3.5 h-3.5 text-amber-600 flex-shrink-0 mt-0.5" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <p class="text-xs text-amber-700 leading-relaxed">
                                    <strong>Field visit.</strong> Case number will not be assigned yet.
                                    Sync to clinic system later to assign case numbers.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Priority</Label>
                            <Select v-model="form.priority">
                                <SelectTrigger class="h-8 text-xs"><SelectValue/></SelectTrigger>
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
                            <Label class="text-xs">Counter</Label>
                            <Select v-model="form.queue_counter_id">
                                <SelectTrigger class="h-8 text-xs"><SelectValue/></SelectTrigger>
                                <SelectContent>
                                <SelectItem v-for="c in counters" :key="c.id" :value="String(c.id)">
                                    {{ c.counter_name }}
                                </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" v-model="form.referral_validated"
                                class="w-3.5 h-3.5 rounded accent-blue-600"/>
                            <span class="text-xs text-slate-600">Referral slip validated</span>
                        </label>
                    </div>

                    <!-- Invoice Preview -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Invoice Preview</p>

                        <div v-if="selectedServices.length === 0" class="text-center py-4">
                            <Receipt class="w-8 h-8 text-slate-200 mx-auto mb-2"/>
                            <p class="text-xs text-slate-400">No services selected</p>
                        </div>

                        <div v-else class="space-y-2">
                            <div v-for="svc in selectedServices" :key="svc.service_code"
                                class="flex justify-between items-center text-xs">
                                <span class="text-slate-600 truncate flex-1 mr-2">{{ svc.service_name }}</span>
                                <span class="font-semibold text-slate-700 flex-shrink-0">{{ svc.formatted_price }}</span>
                            </div>

                            <Separator/>

                            <div class="space-y-1.5">
                                <div class="flex justify-between text-xs text-slate-500">
                                    <span>Subtotal</span>
                                    <span>₱ {{ subtotal.toLocaleString('en-PH', {minimumFractionDigits:2}) }}</span>
                                </div>

                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs text-slate-500">Discount</span>
                                    <Input v-model="form.discount_amount"
                                        type="number" min="0" step="0.01"
                                        class="h-7 w-24 text-xs text-right"/>
                                </div>

                                <Separator/>

                                <div class="flex justify-between text-sm font-bold">
                                    <span style="color:#1B4F9B">Total</span>
                                    <span style="color:#1B4F9B">
                                        ₱ {{ total.toLocaleString('en-PH', {minimumFractionDigits:2}) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                   <Button type="submit"
                        :disabled="form.processing || !form.patient_id || form.services.length === 0"
                        class="w-full gap-2 text-white" style="background-color:#1B4F9B">
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <CheckCircle2 v-else class="w-4 h-4"/>
                        {{ form.processing ? 'Processing...' : 'Register Visit & Issue Ticket' }}
                    </Button>

                    <!-- Show ALL validation errors, not just patient_id and services -->
                    <div v-if="Object.keys(form.errors).length > 0"
                        class="mt-3 p-3 bg-red-50 border border-red-200 rounded-xl space-y-1">
                        <p class="text-xs font-bold text-red-700 mb-1">Please fix these errors:</p>
                        <p v-for="(error, field) in form.errors" :key="field"
                            class="text-xs text-red-600">
                            · {{ error }}
                        </p>
                    </div>

                    <p v-if="form.errors.patient_id" class="text-xs text-red-500 text-center">{{ form.errors.patient_id }}</p>
                    <p v-if="form.errors.services" class="text-xs text-red-500 text-center">{{ form.errors.services }}</p>

                </div>

                <!-- ── RIGHT: Service Selection ──────── -->
                <div class="flex-1 min-w-0 space-y-4">

                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Select Services</h3>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-muted-foreground">{{ form.services.length }} selected</span>
                                <button v-if="form.services.length > 0"
                                    type="button" @click="clearServices"
                                    class="text-xs text-red-500 hover:text-red-700 font-semibold underline">
                                    Clear all
                                </button>
                            </div>
                        </div>

                        <!-- Quick Package Buttons — only shown when packages are available -->
                        <div v-if="servicePackages.length > 0" class="px-5 py-3 border-b bg-slate-50/60 flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-bold text-slate-500 mr-1">Quick Select:</span>
                            <button v-for="pkg in servicePackages" :key="pkg.key"
                                type="button"
                                @click="applyPackage(pkg.codes)"
                                class="group flex items-center gap-2 px-3 py-1.5 rounded-lg border-2 text-xs font-semibold transition-all hover:shadow-sm"
                                :style="{
                                    borderColor: pkg.color,
                                    color: pkg.color,
                                    background: pkg.color + '10'
                                }">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                {{ pkg.label }}
                                <span class="text-xs opacity-60 font-normal hidden group-hover:inline">— {{ pkg.desc }}</span>
                            </button>
                        </div>

                        <div class="p-5 space-y-6">
                            <div v-for="(svcs, cat) in serviceGroups" :key="cat">

                                <!-- Category header -->
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="w-6 h-6 rounded-lg flex items-center justify-center"
                                        :style="{ background: categoryConfig[cat]?.color + '20' }">
                                        <component :is="categoryConfig[cat]?.icon" class="w-3.5 h-3.5"
                                            :style="{ color: categoryConfig[cat]?.color }"/>
                                    </div>
                                    <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wide">
                                        {{ categoryConfig[cat]?.label }}
                                    </h4>
                                    <span class="text-xs text-muted-foreground">
                                        ({{ svcs.filter(s => isSelected(s.service_code)).length }}/{{ svcs.length }} selected)
                                    </span>
                                </div>

                                <!-- Service buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="svc in svcs" :key="svc.service_code"
                                        type="button"
                                        @click="toggleService(svc.service_code)"
                                        :class="[
                                            'flex items-center gap-2 px-3 py-2 rounded-xl border-2 text-xs font-semibold transition-all',
                                            isSelected(svc.service_code)
                                                ? 'text-white'
                                                : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'
                                        ]"
                                        :style="isSelected(svc.service_code)
                                            ? { background: categoryConfig[cat]?.color, borderColor: categoryConfig[cat]?.color }
                                            : {}">
                                        <span>{{ svc.service_name }}</span>
                                        <span :class="[
                                            'text-xs px-1.5 py-0.5 rounded-md font-bold',
                                            isSelected(svc.service_code) ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500'
                                        ]">
                                            ₱{{ Number(svc.base_price).toLocaleString() }}
                                        </span>
                                        <span v-if="svc.requires_fasting"
                                            :title="'Requires fasting'"
                                            class="text-amber-400">⚠</span>
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </AppLayout>
</template>
