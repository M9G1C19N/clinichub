<script setup>
import { ref, computed } from 'vue'
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
    Stethoscope, Activity, Eye, Heart,
    Thermometer, Save, CheckCircle2,
    AlertTriangle, FlaskConical, FileText, Printer,
    ScanLine, TestTube, Pill, Trash2, Plus,
} from 'lucide-vue-next'
import { IS_PE_TYPE } from '@/config/visitTypes.js'

const props = defineProps({
    visit:          Object,
    patient:        Object,
    vitals:         Object,
    consultation:   Object,
    doctor:         Object,
    labResults:     Object,
    imagingResult:  Object,
    drugTestResult: Object,
    prescriptions:  { type: Array, default: () => [] },
    signatories:    { type: Array, default: () => [] },
})

const isPreEmployment = IS_PE_TYPE(props.visit.visit_type)

const form = useForm({
    // OPD
    chief_complaint:   props.consultation?.chief_complaint   ?? props.visit.chief_complaint ?? '',
    soap_subjective:   props.consultation?.soap_subjective   ?? '',
    soap_objective:    props.consultation?.soap_objective    ?? '',
    soap_assessment:   props.consultation?.soap_assessment   ?? '',
    soap_plan:         props.consultation?.soap_plan         ?? '',
    icd10_code:        props.consultation?.icd10_code        ?? '',
    icd10_description: props.consultation?.icd10_description ?? '',
    diagnosis_type:    props.consultation?.diagnosis_type    ?? 'primary',

    // PE Header
    pe_classification:  props.consultation?.pe_classification  ?? '',
    pe_findings:        props.consultation?.pe_findings        ?? '',
    pe_recommendation:  props.consultation?.pe_recommendation  ?? '',
    position_applied:   props.consultation?.position_applied   ?? '',
    requesting_physician: props.consultation?.requesting_physician ?? '',

    // Physical Examination
    pe_heent:         props.consultation?.pe_heent         ?? '',
    pe_chest_lungs:   props.consultation?.pe_chest_lungs   ?? '',
    pe_heart:         props.consultation?.pe_heart         ?? '',
    pe_abdomen:       props.consultation?.pe_abdomen       ?? '',
    pe_extremities:   props.consultation?.pe_extremities   ?? '',
    pe_neurological:  props.consultation?.pe_neurological  ?? '',
    pe_genitourinary: props.consultation?.pe_genitourinary ?? '',
    pe_skin:          props.consultation?.pe_skin          ?? '',
    pe_others:        props.consultation?.pe_others        ?? '',

    // Medical History
    past_illnesses:      props.consultation?.past_illnesses      ?? '',
    surgical_history:    props.consultation?.surgical_history    ?? '',
    allergies:           props.consultation?.allergies           ?? '',
    current_medications: props.consultation?.current_medications ?? '',
    family_history:      props.consultation?.family_history      ?? '',

    // Common
    doctor_notes:       props.consultation?.doctor_notes       ?? '',
    is_finalized:       false,
    essentially_normal: props.consultation?.essentially_normal ?? false,
    follow_up_date:     props.consultation?.follow_up_date     ?? '',

    // ECG (only for pre-employment visits, optional)
    ecg_impression:       props.consultation?.ecg_impression       ?? '',
    ecg_findings:         props.consultation?.ecg_findings         ?? '',
    ecg_noted_by_user_id: props.consultation?.ecg_noted_by_user_id ?? null,
})

// "Fill Normal" for PE — fills all exam fields with "Normal"
function fillAllNormal() {
    const normalFields = [
        'pe_heent','pe_chest_lungs','pe_heart','pe_abdomen',
        'pe_extremities','pe_neurological','pe_genitourinary','pe_skin',
    ]
    normalFields.forEach(f => { if (!form[f]) form[f] = 'Normal' })
    form.essentially_normal = true
    form.pe_classification = form.pe_classification || 'A'
}
// ICD-10 search — common codes for quick selection
const commonICD10 = [
    { code: 'J00',   desc: 'Acute Nasopharyngitis (Common Cold)' },
    { code: 'J06.9', desc: 'Acute Upper Respiratory Infection' },
    { code: 'A09',   desc: 'Gastroenteritis' },
    { code: 'K29.7', desc: 'Gastritis' },
    { code: 'R50.9', desc: 'Fever, Unspecified' },
    { code: 'M54.5', desc: 'Low Back Pain' },
    { code: 'I10',   desc: 'Essential Hypertension' },
    { code: 'E11',   desc: 'Type 2 Diabetes Mellitus' },
    { code: 'J18.9', desc: 'Pneumonia, Unspecified' },
    { code: 'Z00.0', desc: 'General Medical Examination' },
    { code: 'Z10.0', desc: 'Pre-Employment Medical Examination' },
]

const showICD10Picker = ref(false)


// ── Inline Prescription form ───────────────────────────
const showRxForm = ref(false)
const rxForm = useForm({
    items: [
        { drug: '', dosage: '', form: '', quantity: '', frequency: '', duration: '', instructions: '' },
    ],
    notes:         '',
    is_controlled: false,
})

const commonDrugs = [
    { drug:'Amoxicillin',    dosage:'500mg', form:'Capsule', quantity:'21',  frequency:'3x daily',   duration:'7 days'  },
    { drug:'Paracetamol',    dosage:'500mg', form:'Tablet',  quantity:'20',  frequency:'q4h PRN',    duration:'5 days'  },
    { drug:'Ibuprofen',      dosage:'400mg', form:'Tablet',  quantity:'15',  frequency:'3x daily',   duration:'5 days'  },
    { drug:'Metformin',      dosage:'500mg', form:'Tablet',  quantity:'60',  frequency:'Twice daily',duration:'30 days' },
    { drug:'Amlodipine',     dosage:'5mg',   form:'Tablet',  quantity:'30',  frequency:'Once daily', duration:'30 days' },
    { drug:'Omeprazole',     dosage:'20mg',  form:'Capsule', quantity:'14',  frequency:'Twice daily',duration:'7 days'  },
    { drug:'Azithromycin',   dosage:'500mg', form:'Tablet',  quantity:'3',   frequency:'Once daily', duration:'3 days'  },
    { drug:'Mefenamic Acid', dosage:'500mg', form:'Capsule', quantity:'20',  frequency:'3x daily',   duration:'5 days'  },
]

const showRxPicker = ref(false)

function addRxItem() {
    rxForm.items.push({ drug: '', dosage: '', form: '', quantity: '', frequency: '', duration: '', instructions: '' })
}
function removeRxItem(i) {
    if (rxForm.items.length === 1) return
    rxForm.items.splice(i, 1)
}
function addRxFromPicker(drug) {
    const empty = rxForm.items.find(m => !m.drug)
    if (empty) Object.assign(empty, drug)
    else rxForm.items.push({ ...drug, instructions: '' })
    showRxPicker.value = false
}
function saveRx() {
    // Remove rows where drug name is empty
    const filledItems = rxForm.items.filter(i => i.drug?.trim())
    if (filledItems.length === 0) {
        alert('Please enter at least one medication.')
        return
    }
    rxForm.items = filledItems

    rxForm.post(route('doctor.prescription.store', props.visit.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRxForm.value = false
            rxForm.reset()
            rxForm.items = [{ drug: '', dosage: '', form: '', quantity: '', frequency: '', duration: '', instructions: '' }]
            showRxPicker.value = false
        },
        onError: (errors) => {
            console.error('Rx save errors:', errors)
            // Restore empty item if all were filtered
            if (rxForm.items.length === 0) {
                rxForm.items = [{ drug: '', dosage: '', form: '', quantity: '', frequency: '', duration: '', instructions: '' }]
            }
        },
    })
}

function deleteRx(id) {
    if (!confirm('Delete this prescription?')) return
    router.delete(route('doctor.prescription.destroy', id), { preserveScroll: true })
}
function selectICD10(item) {
    form.icd10_code        = item.code
    form.icd10_description = item.desc
    showICD10Picker.value  = false
}

// PE Classification config
const peClasses = [
    {
        value: 'A',
        label: 'Class A',
        desc:  'Physically fit for all types of work. No defects noted.',
        color: 'emerald',
        recommendation: 'FIT TO WORK',
    },
    {
        value: 'B',
        label: 'Class B',
        desc:  'Physically fit for all types of work. Has minor and curable ailment that offers no handicap to the job applied.',
        color: 'blue',
        recommendation: 'FIT TO WORK WITH FINDINGS',
    },
    {
        value: 'C',
        label: 'Class C',
        desc:  'With findings that are generally unacceptable. Employment at risk and at the discretion of management.',
        color: 'amber',
        recommendation: 'CONDITIONALLY FIT',
    },
    {
        value: 'D',
        label: 'Class D',
        desc:  'Not fit for employment.',
        color: 'orange',
        recommendation: 'NOT FIT FOR EMPLOYMENT',
    },
    {
        value: 'E',
        label: 'Class E',
        desc:  'Lacking in requirements or need further evaluation.',
        color: 'red',
        recommendation: 'FURTHER EVALUATION NEEDED',
    },
]

const selectedClass = computed(() =>
    peClasses.find(c => c.value === form.pe_classification)
)

function saveDraft() {
    form.is_finalized = false
    form.post(route('doctor.store', props.visit.id))
}

function finalize() {
    form.is_finalized = true
    form.post(route('doctor.store', props.visit.id))
}

// Vitals helpers
const bpStatus = computed(() => {
    if (!props.vitals) return null
    const s = props.vitals.blood_pressure_systolic
    const d = props.vitals.blood_pressure_diastolic
    if (!s || !d) return null
    if (s < 90  || d < 60)  return { label: 'Low BP',       color: 'text-blue-600'   }
    if (s <= 120 && d <= 80) return { label: 'Normal',       color: 'text-emerald-600'}
    if (s <= 130 && d <= 80) return { label: 'Elevated',     color: 'text-amber-500'  }
    if (s <= 140 || d <= 90) return { label: 'Stage 1 HTN',  color: 'text-orange-600' }
    return                          { label: 'Stage 2 HTN',  color: 'text-red-600'    }
})
</script>

<template>
    <AppLayout :title="`Consult — ${patient.full_name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('doctor.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">{{ patient.full_name }}</h1>
                            <span :class="[
                                'text-xs font-bold px-2.5 py-1 rounded-full',
                                isPreEmployment ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'
                            ]">
                                {{ isPreEmployment ? 'Pre-Employment' : 'OPD' }}
                            </span>
                            <span v-if="consultation?.is_finalized"
                                class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                                Finalized
                            </span>
                        </div>
                        <p class="text-slate-400 text-xs mt-0.5">
                            {{ patient.patient_code }} · {{ patient.age_sex }} · {{ visit.visit_date }}
                        </p>
                    </div>
                </div>

                <!-- Action buttons -->
                <div class="flex items-center gap-2" v-if="!consultation?.is_finalized">
                    <Button variant="outline" class="gap-2" @click="saveDraft"
                        :disabled="form.processing">
                        <Save class="w-4 h-4"/>
                        Save Draft
                    </Button>
                    <Button class="gap-2 text-white" @click="finalize"
                        :disabled="form.processing"
                        :style="{ backgroundColor: isPreEmployment ? '#8B5CF6' : '#1B4F9B' }">
                        <CheckCircle2 class="w-4 h-4"/>
                        {{ isPreEmployment ? 'Finalize & Issue Certificate' : 'Finalize Consultation' }}
                    </Button>
                </div>
            </div>
        </template>

        <div class="flex gap-5 items-start">

            <!-- ── LEFT: Patient Summary ─────────── -->
            <div class="w-100 flex-shrink-0 space-y-4">

                <!-- Patient Info -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Patient</p>
                     <div class="flex items-center gap-3 mb-3">
                            <!-- Photo or fallback initial -->
                            <div class="w-12 h-12 rounded-xl flex-shrink-0 overflow-hidden border border-slate-200">
                                <img v-if="patient.photo_url"
                                    :src="patient.photo_url"
                                    class="w-full h-full object-cover"
                                    crossorigin="anonymous"
                                />
                                <div v-else
                                    class="w-full h-full flex items-center justify-center text-white font-bold text-lg"
                                    style="background:#1B4F9B">
                                    {{ patient.full_name.charAt(0) }}
                                </div>
                            </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800">{{ patient.full_name }}</p>
                            <p class="text-xs text-muted-foreground">{{ patient.patient_code }}</p>
                        </div>
                    </div>
                    <div class="space-y-1.5 text-xs">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Age / Sex</span>
                            <span class="font-semibold">{{ patient.age_sex }}</span>
                        </div>
                        <div v-if="visit.employer_company" class="flex justify-between">
                            <span class="text-muted-foreground">Company</span>
                            <span class="font-semibold text-purple-600">{{ visit.employer_company }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Visit Date</span>
                            <span class="font-semibold">{{ visit.visit_date }}</span>
                        </div>
                    </div>
                </div>

                <!-- Vitals Summary -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-2">
                        <Activity class="w-3.5 h-3.5"/>
                        Vitals
                    </p>

                    <div v-if="vitals" class="space-y-2 text-xs">
                        <!-- BMI -->
                        <div class="flex justify-between items-center p-2 bg-slate-50 rounded-lg">
                            <span class="text-muted-foreground">BMI</span>
                            <div class="text-right">
                                <span class="font-bold">{{ vitals.bmi }}</span>
                                <span class="text-muted-foreground ml-1">{{ vitals.bmi_category }}</span>
                            </div>
                        </div>

                        <!-- BP -->
                        <div class="flex justify-between items-center p-2 bg-slate-50 rounded-lg">
                            <span class="text-muted-foreground">Blood Pressure</span>
                            <div class="text-right">
                                <span class="font-bold">
                                    {{ vitals.blood_pressure_systolic }}/{{ vitals.blood_pressure_diastolic }}
                                </span>
                                <p v-if="bpStatus" :class="['text-xs', bpStatus.color]">{{ bpStatus.label }}</p>
                            </div>
                        </div>

                        <!-- Temp -->
                        <div v-if="vitals.temperature_celsius" class="flex justify-between p-2 bg-slate-50 rounded-lg">
                            <span class="text-muted-foreground">Temperature</span>
                            <span class="font-bold">{{ vitals.temperature_celsius }}°C</span>
                        </div>

                        <!-- Pulse -->
                        <div v-if="vitals.pulse_rate" class="flex justify-between p-2 bg-slate-50 rounded-lg">
                            <span class="text-muted-foreground">Pulse Rate</span>
                            <span class="font-bold">{{ vitals.pulse_rate }} bpm</span>
                        </div>

                        <!-- SpO2 -->
                        <div v-if="vitals.oxygen_saturation" class="flex justify-between p-2 bg-slate-50 rounded-lg">
                            <span class="text-muted-foreground">SpO₂</span>
                            <span class="font-bold">{{ vitals.oxygen_saturation }}%</span>
                        </div>

                        <!-- Visual Acuity — pre-employment -->
                        <div v-if="vitals.visual_acuity_right || vitals.visual_acuity_left"
                            class="p-2 bg-slate-50 rounded-lg">
                            <p class="text-muted-foreground mb-1 flex items-center gap-1">
                                <Eye class="w-3 h-3"/> Visual Acuity
                            </p>
                            <div class="flex justify-between">
                                <span>Right (OD): <strong>{{ vitals.visual_acuity_right ?? '—' }}</strong></span>
                                <span>Left (OS): <strong>{{ vitals.visual_acuity_left ?? '—' }}</strong></span>
                            </div>
                        </div>

                        <!-- Ishihara -->
                        <div v-if="vitals.ishihara_result" class="flex justify-between p-2 bg-slate-50 rounded-lg">
                            <span class="text-muted-foreground">Ishihara</span>
                            <span :class="['font-bold',
                                vitals.ishihara_result === 'Normal' ? 'text-emerald-600' : 'text-red-600']">
                                {{ vitals.ishihara_result }}
                            </span>
                        </div>

                        <!-- Nurse notes -->
                        <div v-if="vitals.nurse_notes" class="p-2 bg-amber-50 rounded-lg border border-amber-100">
                            <p class="text-xs font-semibold text-amber-700 mb-1">Nurse Notes</p>
                            <p class="text-xs text-amber-600">{{ vitals.nurse_notes }}</p>
                        </div>
                    </div>

                    <!-- No vitals -->
                    <div v-else class="py-4 text-center">
                        <AlertTriangle class="w-6 h-6 text-amber-400 mx-auto mb-2"/>
                        <p class="text-xs text-amber-600 font-semibold">No vitals recorded yet</p>
                        <p class="text-xs text-slate-400 mt-1">Nurse intake pending</p>
                    </div>
                </div>
                <!-- ── LAB RESULTS ──────────────────── -->
                <div v-if="labResults" class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center justify-between"
                        :class="labResults.has_abnormal ? 'bg-red-50 border-red-200' : 'bg-blue-50 border-blue-200'">
                        <div class="flex items-center gap-2">
                            <FlaskConical class="w-4 h-4"
                                :class="labResults.has_abnormal ? 'text-red-600' : 'text-blue-600'"/>
                            <span class="text-xs font-bold uppercase tracking-widest"
                                :class="labResults.has_abnormal ? 'text-red-700' : 'text-blue-700'">
                                Lab Results
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-if="labResults.has_abnormal"
                                class="text-xs font-bold px-2 py-0.5 rounded-full bg-red-100 text-red-700">
                                {{ labResults.abnormal_count }} Abnormal
                            </span>
                            <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full',
                                labResults.is_released
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-amber-100 text-amber-700']">
                                {{ labResults.is_released ? 'Released' : 'Pending' }}
                            </span>
                        </div>
                    </div>

                    <!-- Only show if released -->
                    <div v-if="labResults.is_released" class="divide-y divide-slate-50">
                        <div v-for="result in labResults.results.filter(r => r.result_value)"
                            :key="result.test_code"
                            :class="['flex items-center justify-between px-4 py-2 text-xs',
                                result.is_abnormal ? 'bg-red-50/50' : '']">
                            <span class="text-slate-600 flex-1">{{ result.test_name }}</span>
                            <div class="flex items-center gap-2">
                                <span :class="['font-bold font-mono',
                                    result.is_abnormal ? 'text-red-700' : 'text-slate-800']">
                                    {{ result.result_value }}
                                    <span v-if="result.unit" class="font-normal text-slate-400 text-xs">
                                        {{ result.unit }}
                                    </span>
                                </span>
                                <span v-if="result.abnormal_flag"
                                    :class="['text-xs font-black px-1 rounded',
                                        result.abnormal_flag === 'H'
                                            ? 'bg-red-100 text-red-700'
                                            : 'bg-blue-100 text-blue-700']">
                                    {{ result.abnormal_flag }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-4 py-3 text-xs text-amber-600 flex items-center gap-2">
                        <AlertTriangle class="w-3.5 h-3.5"/>
                        Results not yet released by lab technician
                    </div>

                    <!-- Lab staff -->
                    <div v-if="labResults.is_released && labResults.examined_by_name"
                        class="px-4 py-2 border-t bg-slate-50/50 text-xs text-slate-500">
                        Examined by: <strong>{{ labResults.examined_by_name }}</strong>
                        <span v-if="labResults.noted_by_name">
                            · Noted by: <strong>{{ labResults.noted_by_name }}</strong>
                        </span>
                    </div>
                </div>

                <!-- ── XRAY RESULTS ─────────────────── -->
                <div v-if="imagingResult" class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b bg-purple-50 border-purple-200 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <ScanLine class="w-4 h-4 text-purple-600"/>
                            <span class="text-xs font-bold uppercase tracking-widest text-purple-700">
                                {{ imagingResult.imaging_type_label }}
                            </span>
                        </div>
                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full',
                            imagingResult.is_released
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700']">
                            {{ imagingResult.is_released ? 'Released' : 'Pending' }}
                        </span>
                    </div>
                    <div v-if="imagingResult.is_released" class="px-4 py-3 space-y-2 text-xs">
                        <div v-if="imagingResult.is_provisional"
                            class="text-amber-700 font-bold bg-amber-50 p-2 rounded-lg border border-amber-200">
                            **SEE ATTACHED CXR OFFICIAL READING**
                        </div>
                        <div v-else-if="imagingResult.radiographic_findings">
                            <p class="text-muted-foreground mb-1 font-semibold">Findings:</p>
                            <p class="text-slate-700 whitespace-pre-line leading-relaxed">
                                {{ imagingResult.radiographic_findings }}
                            </p>
                        </div>
                        <div v-if="imagingResult.impression"
                            class="pt-2 border-t mt-2">
                            <p class="text-muted-foreground mb-1 font-semibold">Impression:</p>
                            <p class="font-bold text-slate-800 uppercase">{{ imagingResult.impression }}</p>
                        </div>
                        <p v-if="imagingResult.radiologist_name"
                            class="text-muted-foreground border-t pt-2 mt-2">
                            Radiologist: <strong>{{ imagingResult.radiologist_name }}</strong>
                        </p>
                    </div>
                    <div v-else class="px-4 py-3 text-xs text-amber-600 flex items-center gap-2">
                        <AlertTriangle class="w-3.5 h-3.5"/>
                        Report not yet released by X-Ray technician
                    </div>
                </div>

                <!-- ── DRUG TEST RESULT ──────────────── -->
                <div v-if="drugTestResult" class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-4 py-3 border-b flex items-center justify-between"
                        :class="drugTestResult.result === 'positive'
                            ? 'bg-red-50 border-red-200'
                            : drugTestResult.result === 'negative'
                            ? 'bg-emerald-50 border-emerald-200'
                            : 'bg-rose-50 border-rose-200'">
                        <div class="flex items-center gap-2">
                            <TestTube class="w-4 h-4"
                                :class="drugTestResult.result === 'positive' ? 'text-red-600' :
                                        drugTestResult.result === 'negative' ? 'text-emerald-600' : 'text-rose-600'"/>
                            <span class="text-xs font-bold uppercase tracking-widest text-rose-700">Drug Test</span>
                        </div>
                        <span :class="['text-xs font-semibold px-2 py-0.5 rounded-full',
                            drugTestResult.is_released
                                ? 'bg-emerald-100 text-emerald-700'
                                : 'bg-amber-100 text-amber-700']">
                            {{ drugTestResult.is_released ? 'Released' : 'Pending' }}
                        </span>
                    </div>
                    <div v-if="drugTestResult.is_released" class="px-4 py-3 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Result</span>
                            <span :class="['font-black text-base',
                                drugTestResult.result === 'negative' ? 'text-emerald-600' :
                                drugTestResult.result === 'positive' ? 'text-red-600' : 'text-slate-600']">
                                {{ drugTestResult.result?.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Drugs Tested</span>
                            <span class="font-semibold text-right">{{ drugTestResult.drugs_label }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Purpose</span>
                            <span class="font-semibold">{{ drugTestResult.purpose }}</span>
                        </div>
                    </div>
                    <div v-else class="px-4 py-3 text-xs text-amber-600 flex items-center gap-2">
                        <AlertTriangle class="w-3.5 h-3.5"/>
                        Result not yet released by drug test staff
                    </div>
                </div>

                <!-- No results at all -->
                <div v-if="!labResults && !imagingResult && !drugTestResult"
                    class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-xs text-amber-700">
                    <div class="flex items-center gap-2 font-semibold mb-1">
                        <AlertTriangle class="w-4 h-4"/>
                        No diagnostic results yet
                    </div>
                    <p class="text-amber-600">Lab, X-Ray, and Drug Test results will appear here once released by the respective technicians.</p>
                </div>

                <!-- Services ordered -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-2">
                        <FlaskConical class="w-3.5 h-3.5"/>
                        Services Ordered
                    </p>
                    <div class="space-y-1.5">
                        <div v-for="svc in visit.services" :key="svc.service_code"
                            class="flex items-center gap-2 text-xs">
                            <span class="font-mono font-bold text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded">
                                {{ svc.service_code }}
                            </span>
                            <span class="text-slate-600">{{ svc.service_name }}</span>
                        </div>
                        <p v-if="!visit.services?.length" class="text-xs text-slate-400">No services</p>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT: Consultation Form ──────── -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- ═══ OPD MODE ═══ -->
                <template v-if="!isPreEmployment">

                    <!-- Chief Complaint -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Chief Complaint</h3>
                        </div>
                        <div class="p-5">
                            <Textarea v-model="form.chief_complaint" :rows="2"
                                placeholder="Patient's main complaint..."
                                class="resize-none"/>
                        </div>
                    </div>

                    <!-- SOAP Notes -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <Stethoscope class="w-4 h-4 text-muted-foreground"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">SOAP Notes</h3>
                        </div>
                        <div class="p-5 space-y-4">

                            <div class="space-y-1.5">
                                <Label class="text-xs flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">S</span>
                                    Subjective
                                    <span class="text-muted-foreground font-normal">— what the patient tells you</span>
                                </Label>
                                <Textarea v-model="form.soap_subjective" :rows="2"
                                    placeholder="Patient's history, symptoms, complaints..."
                                    class="resize-none"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">O</span>
                                    Objective
                                    <span class="text-muted-foreground font-normal">— what you observe/measure</span>
                                </Label>
                                <Textarea v-model="form.soap_objective" :rows="2"
                                    placeholder="Vital signs, physical exam findings, lab results..."
                                    class="resize-none"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold">A</span>
                                    Assessment
                                    <span class="text-muted-foreground font-normal">— your diagnosis</span>
                                </Label>
                                <Textarea v-model="form.soap_assessment" :rows="2"
                                    placeholder="Diagnosis, differential diagnosis..."
                                    class="resize-none"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold">P</span>
                                    Plan
                                    <span class="text-muted-foreground font-normal">— treatment plan</span>
                                </Label>
                                <Textarea v-model="form.soap_plan" :rows="2"
                                    placeholder="Medications, procedures, referrals, follow-up..."
                                    class="resize-none"/>
                            </div>

                        </div>
                    </div>
                    <!-- Essentially Normal Findings — quick option -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" v-model="form.essentially_normal"
                                class="w-4 h-4 rounded accent-blue-600"/>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Essentially Normal Findings</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Auto-fills assessment as "***ESSENTIALLY NORMAL FINDINGS***"
                                </p>
                            </div>
                        </label>
                    </div>

                    <!-- Follow-up Date -->
                    <div class="space-y-1.5">
                        <Label class="text-xs">Follow-up Date <span class="text-muted-foreground">(optional)</span></Label>
                        <Input v-model="form.follow_up_date" type="date"/>
                    </div>

                    <!-- ICD-10 -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <FileText class="w-4 h-4 text-muted-foreground"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">ICD-10 Diagnosis</h3>
                        </div>
                        <div class="p-5 space-y-3">

                            <div class="grid grid-cols-3 gap-3">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">ICD-10 Code</Label>
                                    <Input v-model="form.icd10_code"
                                        placeholder="e.g. J00"
                                        class="font-mono font-bold uppercase"/>
                                </div>
                                <div class="col-span-2 space-y-1.5">
                                    <Label class="text-xs">Description</Label>
                                    <Input v-model="form.icd10_description"
                                        placeholder="e.g. Acute Nasopharyngitis"/>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Diagnosis Type</Label>
                                <Select v-model="form.diagnosis_type">
                                    <SelectTrigger class="w-48">
                                        <SelectValue/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="primary">Primary Diagnosis</SelectItem>
                                        <SelectItem value="secondary">Secondary Diagnosis</SelectItem>
                                        <SelectItem value="provisional">Provisional Diagnosis</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <!-- Quick ICD-10 picker -->
                            <div>
                                <button type="button"
                                    @click="showICD10Picker = !showICD10Picker"
                                    class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                    {{ showICD10Picker ? '▲ Hide' : '▼ Quick select common diagnoses' }}
                                </button>

                                <div v-if="showICD10Picker"
                                    class="mt-2 grid grid-cols-1 gap-1.5">
                                    <button v-for="item in commonICD10" :key="item.code"
                                        type="button"
                                        @click="selectICD10(item)"
                                        class="flex items-center gap-2 px-3 py-2 rounded-lg border hover:bg-slate-50 text-left transition-colors">
                                        <span class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">
                                            {{ item.code }}
                                        </span>
                                        <span class="text-xs text-slate-600">{{ item.desc }}</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                </template>

                <!-- ═══ PRE-EMPLOYMENT MODE ═══ -->
                <template v-else>

                    <!-- Classification -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-purple-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                Pre-Employment Classification
                            </h3>
                        </div>
                        <div class="p-5">

                            <!-- Class selector -->
                            <!-- PE Classification — exact descriptions from actual form -->
                            <div class="space-y-2 mb-4">
                                <Label class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Classification</Label>
                                <div v-for="cls in peClasses" :key="cls.value"
                                    @click="form.pe_classification = cls.value"
                                    :class="[
                                        'flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer transition-all',
                                        form.pe_classification === cls.value
                                            ? `border-${cls.color}-500 bg-${cls.color}-50`
                                            : 'border-border hover:border-slate-300 hover:bg-muted/30'
                                    ]">
                                    <!-- Checkbox -->
                                    <div :class="[
                                        'w-5 h-5 rounded border-2 flex-shrink-0 mt-0.5 flex items-center justify-center',
                                        form.pe_classification === cls.value
                                            ? `border-${cls.color}-500 bg-${cls.color}-500`
                                            : 'border-slate-300'
                                    ]">
                                        <svg v-if="form.pe_classification === cls.value"
                                            class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p :class="['text-sm font-bold',
                                            form.pe_classification === cls.value ? `text-${cls.color}-700` : 'text-slate-700']">
                                            {{ cls.label }}
                                        </p>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ cls.desc }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Auto-recommendation based on class -->
                            <div v-if="selectedClass" class="p-3 rounded-xl border-2 border-dashed"
                                :class="`border-${selectedClass.color}-300 bg-${selectedClass.color}-50`">
                                <p class="text-xs text-muted-foreground mb-1">Recommendation</p>
                                <p :class="['text-lg font-black', `text-${selectedClass.color}-700`]">
                                    {{ selectedClass.recommendation }}
                                </p>
                            </div>

                            <!-- Essentially Normal quick option for PE -->
                            <div class="p-3 bg-slate-50 rounded-xl border mt-3">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" v-model="form.essentially_normal"
                                        class="w-4 h-4 rounded accent-blue-600"/>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-700">Essentially Normal Findings</p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            Marks diagnosis as "***ESSENTIALLY NORMAL FINDINGS***"
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <!-- Selected class description -->
                            <div v-if="selectedClass"
                                :class="[
                                    'p-4 rounded-xl border mb-4',
                                    `bg-${selectedClass.color}-50 border-${selectedClass.color}-200`
                                ]">
                                <p :class="['text-sm font-bold', `text-${selectedClass.color}-700`]">
                                    {{ selectedClass.label }} — {{ selectedClass.desc }}
                                </p>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Medical Findings</Label>
                                    <Textarea v-model="form.pe_findings" :rows="4"
                                        placeholder="Summarize all medical findings from lab, xray, vitals..."
                                        class="resize-none"/>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs">Recommendation</Label>
                                    <Textarea v-model="form.pe_recommendation" :rows="4"
                                        placeholder="Doctor's recommendation and remarks..."
                                        class="resize-none"/>
                                </div>
                            </div>

                        </div>
                    </div>

                </template>

                <!-- ─── ECG (Pre-Employment only, optional) ─── -->
                <div v-if="isPreEmployment" class="bg-card rounded-xl border border-blue-100 shadow-sm">
                    <div class="px-5 py-3.5 border-b border-blue-100 flex items-center justify-between" style="background:#EFF6FF">
                        <div class="flex items-center gap-2">
                            <Activity class="w-4 h-4 text-blue-600"/>
                            <h3 class="text-xs font-bold text-blue-700 uppercase tracking-widest">
                                Electrocardiography (ECG)
                            </h3>
                            <span class="text-xs text-blue-500 font-medium">(Optional)</span>
                        </div>
                        <span class="text-xs text-blue-500 bg-blue-100 px-2 py-0.5 rounded-full font-semibold">
                            Required for HTN / Heart Disease
                        </span>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label class="text-xs">ECG Impression</Label>
                                <Input v-model="form.ecg_impression"
                                    placeholder="e.g. Normal Sinus Rhythm, Sinus Tachycardia..."
                                    class="h-8 text-xs"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Noted By</Label>
                                <Select
                                    :model-value="form.ecg_noted_by_user_id?.toString() ?? '__none__'"
                                    :disabled="consultation?.is_finalized"
                                    @update:model-value="form.ecg_noted_by_user_id = ($event && $event !== '__none__') ? Number($event) : null">
                                    <SelectTrigger class="h-8 text-xs">
                                        <SelectValue placeholder="— Select signatory —"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="__none__">— None —</SelectItem>
                                        <SelectItem
                                            v-for="sig in signatories"
                                            :key="sig.user_id"
                                            :value="sig.user_id.toString()">
                                            {{ sig.name }}{{ sig.title ? ' — ' + sig.title : '' }}
                                            {{ sig.license_number ? ' (Lic. ' + sig.license_number + ')' : '' }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <!-- E-signature preview -->
                                <div v-if="form.ecg_noted_by_user_id" class="mt-1.5">
                                    <template v-if="signatories.find(s => s.user_id === form.ecg_noted_by_user_id)?.signature_url">
                                        <img
                                            :src="signatories.find(s => s.user_id === form.ecg_noted_by_user_id).signature_url"
                                            class="h-10 object-contain border border-slate-200 rounded-lg p-1.5 bg-white"
                                            alt="E-Signature"/>
                                    </template>
                                    <span v-else class="text-xs text-slate-400 italic">No e-signature on file for this user</span>
                                </div>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <Label class="text-xs">ECG Findings / Details</Label>
                            <Textarea v-model="form.ecg_findings" :rows="3"
                                placeholder="Additional ECG findings and interpretations (optional)..."
                                class="resize-none text-xs"
                                :disabled="consultation?.is_finalized"/>
                        </div>
                    </div>
                </div>

                <!-- ─── Examination Details ─── -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-purple-400"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                Examination Details
                            </h3>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Position Applied</Label>
                                <Input v-model="form.position_applied"
                                    placeholder="e.g. Driver, Security Guard, Office Staff"
                                    class="h-8 text-xs"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Requesting Physician</Label>
                                <Input v-model="form.requesting_physician"
                                    placeholder="e.g. Dr. Roland E. Mira"
                                    class="h-8 text-xs"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Medical / Surgical History ─── -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-amber-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                Medical / Surgical History
                            </h3>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Past Illnesses</Label>
                                <Textarea v-model="form.past_illnesses" :rows="2"
                                    class="resize-none text-xs"
                                    placeholder="Hypertension, DM, PTB, etc. or None"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Surgical History</Label>
                                <Textarea v-model="form.surgical_history" :rows="2"
                                    class="resize-none text-xs"
                                    placeholder="Previous operations or None"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Allergies</Label>
                                <Textarea v-model="form.allergies" :rows="2"
                                    class="resize-none text-xs"
                                    placeholder="Drug/food allergies or NKDA"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Current Medications</Label>
                                <Textarea v-model="form.current_medications" :rows="2"
                                    class="resize-none text-xs"
                                    placeholder="Maintenance medications or None"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="col-span-2 space-y-1.5">
                                <Label class="text-xs">Family History</Label>
                                <Input v-model="form.family_history"
                                    class="h-8 text-xs"
                                    placeholder="e.g. DM (father), HPN (mother) or None"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                        </div>
                    </div>

                    <!-- ─── Physical Examination ─── -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Stethoscope class="w-4 h-4 text-emerald-600"/>
                                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                    Physical Examination
                                </h3>
                            </div>
                            <Button v-if="!consultation?.is_finalized"
                                type="button" variant="outline" size="sm"
                                class="text-xs h-7 gap-1.5 text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                                @click="fillAllNormal">
                                <CheckCircle2 class="w-3.5 h-3.5"/>
                                Fill All Normal
                            </Button>
                        </div>
                        <div class="p-5 grid grid-cols-2 gap-4">
                            <div v-for="field in [
                                { key: 'pe_heent',         label: 'HEENT',             placeholder: 'Head, Eyes, Ears, Nose, Throat' },
                                { key: 'pe_chest_lungs',   label: 'Chest / Lungs',     placeholder: 'Chest expansion, breath sounds' },
                                { key: 'pe_heart',         label: 'Heart / CVS',       placeholder: 'Rate, rhythm, murmurs' },
                                { key: 'pe_abdomen',       label: 'Abdomen',           placeholder: 'Soft, non-tender, no organomegaly' },
                                { key: 'pe_extremities',   label: 'Extremities',       placeholder: 'No edema, full ROM' },
                                { key: 'pe_neurological',  label: 'Neurological',      placeholder: 'Intact, no focal deficit' },
                                { key: 'pe_genitourinary', label: 'Genitourinary',     placeholder: 'Normal or Not examined' },
                                { key: 'pe_skin',          label: 'Skin / Integument', placeholder: 'No lesions, normal turgor' },
                            ]" :key="field.key" class="space-y-1.5">
                                <Label class="text-xs font-semibold">{{ field.label }}</Label>
                                <Input v-model="form[field.key]"
                                    :placeholder="field.placeholder"
                                    class="h-8 text-xs transition-colors"
                                    :class="form[field.key] === 'Normal'
                                        ? 'border-emerald-300 bg-emerald-50/40 text-emerald-700 font-semibold'
                                        : form[field.key]
                                        ? 'border-amber-300 bg-amber-50/30'
                                        : ''"
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                            <div class="col-span-2 space-y-1.5">
                                <Label class="text-xs font-semibold">Other Findings</Label>
                                <Textarea v-model="form.pe_others" :rows="2"
                                    class="resize-none text-xs"
                                    placeholder="Any other physical examination findings..."
                                    :disabled="consultation?.is_finalized"/>
                            </div>
                        </div>
                    </div>

                <!-- Doctor Notes — both modes -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-slate-400"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                            Additional Notes
                        </h3>
                        <span class="text-xs text-muted-foreground">(Optional)</span>
                    </div>
                    <div class="p-5">
                        <Textarea v-model="form.doctor_notes" :rows="2"
                            placeholder="Additional observations or notes..."
                            class="resize-none"/>
                    </div>
                </div>

                <!-- Action Bar -->
                <div v-if="!consultation?.is_finalized"
                    class="bg-card rounded-xl border shadow-sm px-5 py-4 flex items-center justify-between">
                    <div class="text-xs text-muted-foreground">
                        <p v-if="isPreEmployment && !form.pe_classification" class="text-amber-600 flex items-center gap-1.5">
                            <AlertTriangle class="w-3.5 h-3.5"/>
                            Please select a classification before finalizing
                        </p>
                        <p v-else class="text-slate-400">
                            Finalize to mark visit as complete and generate certificate
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" class="gap-2" @click="saveDraft"
                            :disabled="form.processing">
                            <Save class="w-4 h-4"/>
                            {{ form.processing ? 'Saving...' : 'Save Draft' }}
                        </Button>
                        <Button class="gap-2 text-white" @click="finalize"
                            :disabled="form.processing || (isPreEmployment && !form.pe_classification)"
                            :style="{ backgroundColor: isPreEmployment ? '#8B5CF6' : '#1B4F9B' }">
                            <CheckCircle2 class="w-4 h-4"/>
                            {{ form.processing ? 'Saving...' :
                               isPreEmployment ? 'Finalize & Issue Certificate' : 'Finalize Consultation' }}
                        </Button>
                    </div>
                </div>

                <!-- Finalized notice -->
            <div v-if="consultation?.is_finalized" class="space-y-4">

                <!-- Green finalized card -->
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                    <div class="flex items-center gap-3 mb-3">
                        <CheckCircle2 class="w-8 h-8 text-emerald-500 flex-shrink-0"/>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-emerald-700">Consultation Finalized</p>
                            <p class="text-xs text-emerald-600">Results are linked to patient profile</p>
                        </div>
                    </div>
                    <!-- Action buttons -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <a :href="route('doctor.print', visit.id)" target="_blank">
                            <Button variant="outline" class="gap-2 border-emerald-300 text-emerald-700 hover:bg-emerald-100">
                                <Printer class="w-4 h-4"/>
                                {{ isPreEmployment ? 'Medical Exam Report' : 'Print Summary' }}
                            </Button>
                        </a>
                        <Button class="gap-2 text-white" style="background:#8B5CF6;"
                            @click="showRxForm = !showRxForm">
                            <Pill class="w-4 h-4"/>
                            {{ showRxForm ? 'Cancel Rx' : 'Write Prescription' }}
                        </Button>
                    </div>
                    <div class="border-t border-emerald-200 pt-3 mt-3 text-xs text-slate-600">
                        <p class="font-bold">{{ doctor.name }}, M.D.</p>
                        <p>Lic. No.: {{ doctor.prc_number }} · PTR No.: {{ doctor.ptr_number }}</p>
                    </div>
                </div>

                <!-- ── Existing Prescriptions ──────────────── -->
                <div v-if="prescriptions.length > 0" class="bg-card rounded-xl border shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <Pill class="w-4 h-4 text-purple-600"/>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                            Prescriptions ({{ prescriptions.length }})
                        </h3>
                    </div>
                    <div class="divide-y">
                        <div v-for="rx in prescriptions" :key="rx.id"
                            class="flex items-start justify-between px-5 py-3 hover:bg-slate-50 transition-colors"
                            :class="rx.is_controlled ? 'bg-amber-50/30' : ''">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="font-mono font-bold text-blue-700 text-xs">{{ rx.rx_number }}</span>
                                    <span v-if="rx.is_controlled"
                                        class="text-xs font-bold px-1.5 py-0.5 rounded bg-amber-100 text-amber-700">
                                        S2
                                    </span>
                                    <span class="text-xs text-slate-400">{{ rx.rx_date }}</span>
                                </div>
                                <div class="space-y-0.5">
                                    <p v-for="item in rx.items?.slice(0, 3)" :key="item.drug"
                                        class="text-xs text-slate-700">
                                        <span class="font-semibold">{{ item.drug }}</span>
                                        <span v-if="item.dosage" class="text-slate-500"> {{ item.dosage }}</span>
                                        <span v-if="item.form" class="text-slate-400"> · {{ item.form }}</span>
                                        <span v-if="item.frequency" class="text-slate-500 italic"> — {{ item.frequency }}</span>
                                    </p>
                                    <p v-if="rx.items?.length > 3" class="text-xs text-slate-400">
                                        +{{ rx.items.length - 3 }} more
                                    </p>
                                </div>
                                <p v-if="rx.notes" class="text-xs text-slate-400 italic mt-1">{{ rx.notes }}</p>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0 ml-3">
                                <a :href="route('doctor.prescription.print', rx.id)" target="_blank">
                                    <Button variant="outline" size="sm" class="text-xs h-7 gap-1">
                                        <Printer class="w-3 h-3"/> Print
                                    </Button>
                                </a>
                                <Button variant="outline" size="sm"
                                    class="text-xs h-7 gap-1 text-red-600 border-red-200 hover:bg-red-50"
                                    @click="deleteRx(rx.id)">
                                    <Trash2 class="w-3 h-3"/>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Write Prescription Form ─────────────── -->
                <div v-if="showRxForm" class="bg-card rounded-xl border-2 border-purple-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-3.5 border-b bg-purple-50 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Pill class="w-4 h-4 text-purple-600"/>
                            <h3 class="text-xs font-bold text-purple-700 uppercase tracking-widest">
                                New Prescription
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- S2 toggle -->
                            <label class="flex items-center gap-2 text-xs cursor-pointer">
                                <input type="checkbox" v-model="rxForm.is_controlled"
                                    class="w-3.5 h-3.5 accent-amber-500"/>
                                <span :class="rxForm.is_controlled ? 'font-bold text-amber-700' : 'text-slate-500'">
                                    S2 Controlled
                                </span>
                            </label>
                            <!-- Quick drug picker -->
                            <Button type="button" variant="outline" size="sm"
                                class="text-xs h-7 gap-1 text-purple-700 border-purple-200"
                                @click="showRxPicker = !showRxPicker">
                                <Pill class="w-3 h-3"/> Quick Add
                            </Button>
                            <!-- Add row -->
                            <Button type="button" variant="outline" size="sm"
                                class="text-xs h-7 gap-1" @click="addRxItem">
                                <Plus class="w-3 h-3"/> Row
                            </Button>
                        </div>
                    </div>

                    <!-- Quick picker -->
                    <div v-if="showRxPicker" class="px-5 py-3 bg-purple-50/50 border-b">
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="drug in commonDrugs" :key="drug.drug"
                                type="button" @click="addRxFromPicker(drug)"
                                class="text-xs px-2 py-1 bg-white border border-purple-200 rounded-lg
                                    hover:bg-purple-100 transition-colors font-semibold text-purple-800">
                                {{ drug.drug }} {{ drug.dosage }}
                            </button>
                        </div>
                    </div>

                    <!-- Medication rows -->
                    <div class="p-4 space-y-2">
                        <div v-for="(item, i) in rxForm.items" :key="i"
                            class="border rounded-xl overflow-hidden bg-slate-50">
                            <!-- Drug name row -->
                            <div class="flex items-center gap-2 px-3 py-2 border-b bg-white">
                                <span class="w-5 h-5 rounded flex items-center justify-center text-xs font-black text-white flex-shrink-0"
                                    style="background:#8B5CF6">{{ i + 1 }}</span>
                                <input v-model="item.drug" placeholder="Drug name (e.g. Amoxicillin)"
                                    class="flex-1 text-sm font-bold bg-transparent border-0 outline-none placeholder:font-normal placeholder:text-slate-400"/>
                                <button type="button" @click="removeRxItem(i)" :disabled="rxForm.items.length === 1"
                                    class="text-slate-300 hover:text-red-500 disabled:opacity-30">
                                    <Trash2 class="w-3.5 h-3.5"/>
                                </button>
                            </div>
                            <!-- Fields row -->
                            <div class="grid grid-cols-6 gap-2 p-3 text-xs">
                                <div v-for="f in [
                                    {key:'dosage',       ph:'500mg'},
                                    {key:'form',         ph:'Tablet/Cap'},
                                    {key:'quantity',     ph:'Qty'},
                                    {key:'frequency',    ph:'3x daily'},
                                    {key:'duration',     ph:'7 days'},
                                ]" :key="f.key" class="space-y-0.5">
                                    <label class="text-slate-400 capitalize">{{ f.key }}</label>
                                    <input v-model="item[f.key]" :placeholder="f.ph"
                                        class="w-full h-7 px-2 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-purple-400"/>
                                </div>
                                <div class="space-y-0.5">
                                    <label class="text-slate-400">Sig</label>
                                    <input v-model="item.instructions" placeholder="Instructions"
                                        class="w-full h-7 px-2 text-xs border border-slate-200 rounded-lg bg-white focus:outline-none focus:ring-1 focus:ring-purple-400"/>
                                </div>
                            </div>
                        </div>

                        <!-- Add more button -->
                        <button type="button" @click="addRxItem"
                            class="w-full py-2 border-2 border-dashed border-purple-200 rounded-xl text-xs text-purple-400
                                hover:border-purple-400 hover:text-purple-600 transition-colors flex items-center justify-center gap-1.5">
                            <Plus class="w-3 h-3"/> Add medication
                        </button>
                    </div>

                    <!-- Notes + Save -->
                    <div class="px-4 pb-4 space-y-3">
                        <textarea v-model="rxForm.notes" rows="2"
                            placeholder="Additional notes / instructions (e.g. rest for 3 days, drink plenty of fluids)..."
                            class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 resize-none focus:outline-none focus:ring-2 focus:ring-purple-400 bg-white"/>
                        <div class="flex items-center justify-end gap-2">
                            <Button variant="outline" size="sm" class="text-xs" @click="showRxForm = false">
                                Cancel
                            </Button>
                            <Button size="sm" class="text-xs gap-2 text-white" style="background:#8B5CF6;"
                                :disabled="rxForm.processing || !rxForm.items.some(i => i.drug)"
                                @click="saveRx">
                                <Save class="w-3.5 h-3.5"/>
                                {{ rxForm.processing ? 'Saving...' : 'Save Prescription' }}
                            </Button>
                        </div>
                    </div>
                </div>

            </div>
            </div>
        </div>

    </AppLayout>
</template>
