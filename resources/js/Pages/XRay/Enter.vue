<script setup>
import { useForm, Link } from '@inertiajs/vue3'
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
import { ScanLine, Save, CheckCircle2, Printer } from 'lucide-vue-next'

const props = defineProps({
    visit:          Object,
    patient:        Object,
    imagingRequest: Object,
    currentUser:    Object,
})

const isReleased = props.imagingRequest?.status === 'released'

const form = useForm({
    imaging_type:           props.imagingRequest?.imaging_type          ?? props.visit.primary_imaging ?? 'chest_xray_pa',
    radiographic_findings:  props.imagingRequest?.radiographic_findings ?? '',
    impression:             props.imagingRequest?.impression            ?? '',
    is_provisional:         props.imagingRequest?.is_provisional        ?? false,
    rad_tech_name:          props.imagingRequest?.rad_tech_name         ?? props.currentUser?.name ?? '',
    rad_tech_license:       props.imagingRequest?.rad_tech_license      ?? props.currentUser?.prc_number ?? '',
    radiologist_name:       props.imagingRequest?.radiologist_name      ?? '',
    radiologist_license:    props.imagingRequest?.radiologist_license   ?? '',
    exam_date: props.imagingRequest?.exam_date ?? todayStr(),
    exam_time: props.imagingRequest?.exam_time ?? nowStr(),
    release:                false,
})

// Common impression presets
const commonImpressions = {
    chest_xray_pa:      'NO SIGNIFICANT CHEST FINDINGS.',
    kub:                'NO SIGNIFICANT FINDINGS.',
    ultrasound_abdomen: 'NO SIGNIFICANT ABDOMINAL FINDINGS.',
    ultrasound_ob:      'NORMAL OBSTETRICAL ULTRASOUND.',
    ultrasound_pelvis:  'NO SIGNIFICANT PELVIC FINDINGS.',
    ecg:                'NORMAL SINUS RHYTHM.',
}

function fillNormal() {
    form.impression = commonImpressions[form.imaging_type] ?? 'NO SIGNIFICANT FINDINGS.'
    if (!form.is_provisional) {
        form.radiographic_findings = form.radiographic_findings ||
            'No active infiltrates seen.\nHila and pulmonary vascular markings are within normal limits.\nHeart is not enlarged.\nThe rest of the chest structures are unremarkable.'
    }
}

const imagingTypeOptions = [
    { value: 'chest_xray_pa',      label: 'Chest X-Ray PA View' },
    { value: 'kub',                label: 'KUB' },
    { value: 'ultrasound_abdomen', label: 'Ultrasound - Whole Abdomen' },
    { value: 'ultrasound_ob',      label: 'Ultrasound - OB' },
    { value: 'ultrasound_pelvis',  label: 'Ultrasound - Pelvis' },
    { value: 'ecg',                label: 'ECG' },
    { value: 'other',              label: 'Other' },
]

function saveDraft() {
    form.release = false
    form.post(route('xray.save', props.visit.id))
}

function release() {
    if (!confirm('Release this imaging report? This will notify the doctor.')) return
    form.release = true
    form.post(route('xray.save', props.visit.id))
}
function todayStr() {
    const d = new Date()
    return d.getFullYear() + '-' +
        String(d.getMonth()+1).padStart(2,'0') + '-' +
        String(d.getDate()).padStart(2,'0')
}
function nowStr() {
    const d = new Date()
    return String(d.getHours()).padStart(2,'0') + ':' +
        String(d.getMinutes()).padStart(2,'0')
}
function formatDatePreview(dateStr) {
    if (!dateStr) return ''
    const [y, m, d] = dateStr.split('-').map(Number)
    return new Date(y, m-1, d).toLocaleDateString('en-PH', {
        weekday:'long', year:'numeric', month:'long', day:'numeric'
    })
}
function printReport() {
    window.open(route('xray.print', props.visit.id), '_blank')
}
</script>

<template>
    <AppLayout :title="`X-Ray — ${patient.full_name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('xray.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">Imaging Report Entry</h1>
                            <span v-if="imagingRequest"
                                class="text-xs font-mono font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                {{ imagingRequest.request_number }}
                            </span>
                            <span v-if="isReleased"
                                class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                                Released
                            </span>
                        </div>
                        <p class="text-slate-400 text-xs mt-0.5">
                            {{ patient.full_name }} · {{ patient.patient_code }} · {{ visit.visit_date }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Button variant="outline" size="sm" class="gap-2" @click="printReport">
                        <Printer class="w-4 h-4"/>
                        Print Report
                    </Button>
                    <Button v-if="!isReleased" variant="outline" class="gap-2"
                        @click="saveDraft" :disabled="form.processing">
                        <Save class="w-4 h-4"/>
                        Save Draft
                    </Button>
                    <Button v-if="!isReleased"
                        class="gap-2 text-white" style="background-color:#8B5CF6"
                        @click="release" :disabled="form.processing">
                        <CheckCircle2 class="w-4 h-4"/>
                        Release Report
                    </Button>
                </div>
            </div>
        </template>

        <div class="flex gap-5 items-start">

            <!-- ── LEFT: Patient Info ─────────── -->
            <div class="w-100 flex-shrink-0 space-y-4">

                <!-- Patient -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Patient</p>
                    <div class="space-y-2 text-xs">
                        <div>
                            <p class="font-bold text-slate-800 text-sm">{{ patient.full_name }}</p>
                            <p class="text-muted-foreground font-mono">{{ patient.patient_code }}</p>
                        </div>
                        <Separator/>
                        <div class="flex justify-between w-21">
                            <span class="text-muted-foreground">Age/Sex: </span>
                            <span class="font-semibold">{{ patient.age_sex }}</span>
                        </div>
                        <Separator/>
                        <div v-if="visit.employer_company" class="flex justify-between">
                            <span class="text-muted-foreground">Company</span>
                            <span class="font-semibold text-purple-600 text-right text-xs">{{ visit.employer_company }}</span>
                        </div>
                        <div class="bg-card rounded-xl border shadow-sm p-3 space-y-2">
                            <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Date & Time of Exam</p>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Date</Label>
                                <div class="flex items-center gap-1.5">
                                    <Input v-model="form.exam_date" type="date" class="h-8 text-xs flex-1"/>
                                    <button type="button" @click="form.exam_date = todayStr()"
                                        class="text-xs px-2 py-1 rounded border border-blue-300 text-blue-600 hover:bg-blue-50 whitespace-nowrap">
                                        Today
                                    </button>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Time</Label>
                                <div class="flex items-center gap-1.5">
                                    <Input v-model="form.exam_time" type="time" class="h-8 text-xs flex-1"/>
                                    <button type="button" @click="form.exam_time = nowStr()"
                                        class="text-xs px-2 py-1 rounded border border-blue-300 text-blue-600 hover:bg-blue-50 whitespace-nowrap">
                                        Now
                                    </button>
                                </div>
                            </div>
                            <p class="text-xs text-slate-400">
                                Prints as: <strong class="text-slate-600">{{ formatDatePreview(form.exam_date) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Imaging Type -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Examination Type</p>
                    <Select v-model="form.imaging_type" :disabled="isReleased">
                        <SelectTrigger>
                            <SelectValue/>
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in imagingTypeOptions" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Staff Details -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Staff Details</p>

                    <div class="space-y-1.5">
                        <Label class="text-xs">Radiologic Technologist</Label>
                        <Input v-model="form.rad_tech_name" placeholder="Full name"
                            class="text-xs h-8" :disabled="isReleased"/>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">License No.</Label>
                        <Input v-model="form.rad_tech_license" placeholder="PRC License No."
                            class="text-xs h-8 font-mono" :disabled="isReleased"/>
                    </div>

                    <Separator/>

                    <div class="space-y-1.5">
                        <Label class="text-xs">Radiologist (Noted By)</Label>
                        <Input v-model="form.radiologist_name" placeholder="Full name"
                            class="text-xs h-8" :disabled="isReleased"/>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">License No.</Label>
                        <Input v-model="form.radiologist_license" placeholder="License No."
                            class="text-xs h-8 font-mono" :disabled="isReleased"/>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT: Report Form ────────── -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Provisional toggle -->
                <div class="bg-card rounded-xl border shadow-sm p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-slate-700">Provisional Reading</p>
                        <p class="text-xs text-muted-foreground mt-0.5">
                            Check if official reading will be attached separately
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="form.is_provisional = !form.is_provisional"
                            :disabled="isReleased"
                            :class="['relative inline-flex h-5 w-9 items-center rounded-full transition-colors',
                                form.is_provisional ? 'bg-amber-500' : 'bg-slate-300']">
                            <span :class="['inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow transition-transform',
                                form.is_provisional ? 'translate-x-4' : 'translate-x-1']"/>
                        </button>
                        <span :class="['text-xs font-semibold',
                            form.is_provisional ? 'text-amber-600' : 'text-slate-400']">
                            {{ form.is_provisional ? 'Provisional' : 'Final' }}
                        </span>
                    </div>
                </div>

                <!-- Radiographic Findings -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-purple-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                Radiographic Findings
                            </h3>
                        </div>
                        <Button v-if="!isReleased" variant="outline" size="sm"
                            class="text-xs gap-1.5" @click="fillNormal">
                            ✓ Fill Normal
                        </Button>
                    </div>
                    <div class="p-5">
                        <div v-if="form.is_provisional"
                            class="mb-3 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-xs font-bold text-amber-700">
                                **SEE ATTACHED CXR OFFICIAL READING**
                            </p>
                        </div>
                        <Textarea
                            v-model="form.radiographic_findings"
                            :rows="6"
                            :disabled="isReleased || form.is_provisional"
                            placeholder="Enter radiographic findings narrative..."
                            class="resize-none font-medium"/>
                    </div>
                </div>

                <!-- Impression -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block" style="background:#1B4F9B"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                            Impression
                        </h3>
                    </div>
                    <div class="p-5">
                        <Textarea
                            v-model="form.impression"
                            :rows="3"
                            :disabled="isReleased"
                            placeholder="e.g. NO SIGNIFICANT CHEST FINDINGS."
                            class="resize-none font-bold text-slate-800 uppercase"/>
                    </div>
                </div>

                <!-- Action Bar -->
                <div v-if="!isReleased"
                    class="bg-card rounded-xl border shadow-sm px-5 py-4 flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">
                        Save draft to continue later, or release to notify the doctor.
                    </p>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" class="gap-2" @click="saveDraft"
                            :disabled="form.processing">
                            <Save class="w-4 h-4"/>
                            Save Draft
                        </Button>
                        <Button class="gap-2 text-white" style="background-color:#8B5CF6"
                            @click="release" :disabled="form.processing">
                            <CheckCircle2 class="w-4 h-4"/>
                            Release Report
                        </Button>
                    </div>
                </div>

                <!-- Released notice -->
                <div v-if="isReleased"
                    class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3">
                    <CheckCircle2 class="w-8 h-8 text-emerald-500 flex-shrink-0"/>
                    <div>
                        <p class="text-sm font-bold text-emerald-700">Report Released</p>
                        <p class="text-xs text-emerald-600 mt-0.5">
                            Doctor has been notified. Report is visible in patient profile.
                        </p>
                    </div>
                    <Button variant="outline" class="ml-auto gap-2" @click="printReport">
                        <Printer class="w-4 h-4"/>
                        Print Report
                    </Button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
