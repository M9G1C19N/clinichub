<script setup>
import { computed } from 'vue'
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
import { TestTube, Save, CheckCircle2, Printer } from 'lucide-vue-next'

const props = defineProps({
    visit:       Object,
    patient:     Object,
    drugTest:    Object,
    currentUser: Object,
    drugsDefault: Array,
})

const isReleased = props.drugTest?.status === 'released'

const form = useForm({
    test_purpose:        props.drugTest?.test_purpose        ?? 'pre_employment',
    drugs_to_test:       props.drugTest?.drugs_to_test       ?? props.drugsDefault ?? ['thc_met'],
    specimen_type:       props.drugTest?.specimen_type       ?? 'urine',
    specimen_time:       props.drugTest?.specimen_time       ?? new Date().toTimeString().slice(0,5),
    temp_in_range:       props.drugTest?.temp_in_range       ?? true,
    specimen_volume:     props.drugTest?.specimen_volume     ?? '',
    specimen_appearance: props.drugTest?.specimen_appearance ?? 'Yellow',
    specimen_sampling:   props.drugTest?.specimen_sampling   ?? 'single',
    specimen_collection: props.drugTest?.specimen_collection ?? 'unobserved',
    collector_name:      props.drugTest?.collector_name      ?? props.currentUser?.name ?? '',
    collector_license:   props.drugTest?.collector_license   ?? props.currentUser?.prc_number ?? '',
    head_of_lab_name:    props.drugTest?.head_of_lab_name    ?? '',
    head_of_lab_license: props.drugTest?.head_of_lab_license ?? '',
    company:             props.drugTest?.company             ?? props.visit.employer_company ?? '',
    remarks:             props.drugTest?.remarks             ?? '',
    result:              props.drugTest?.result              ?? '',
    result_remarks:      props.drugTest?.result_remarks      ?? '',
    certification_signed: props.drugTest?.certification_signed ?? false,
    certification_date:  props.drugTest?.certification_date  ?? new Date().toISOString().slice(0,10),
    release: false,
})

const drugsOptions = [
    { value: 'thc',              label: 'THC only' },
    { value: 'met',              label: 'MET only' },
    { value: 'thc_met',          label: 'THC & MET' },
    { value: 'thc_coc_pcp_opi_amp', label: 'THC, COC, PCP, OPI, AMP' },
]

const purposeOptions = [
    { value: 'pre_employment',      label: 'Pre-employment' },
    { value: 'return_to_duty',      label: 'Return to Duty' },
    { value: 'random',              label: 'Random' },
    { value: 'reasonable_suspicion',label: 'Reasonable Suspicion/Cause' },
    { value: 'follow_up',           label: 'Follow-up' },
    { value: 'post_accident',       label: 'Post-accident' },
    { value: 'mandatory',           label: 'Mandatory' },
]

const resultOptions = [
    { value: 'negative',    label: 'NEGATIVE',    color: '#15803d' },
    { value: 'positive',    label: 'POSITIVE',    color: '#dc2626' },
    { value: 'cancelled',   label: 'Test Cancelled', color:'#64748b' },
    { value: 'refusal',     label: 'Refusal',     color: '#c2410c' },
    { value: 'diluted',     label: 'Diluted',     color: '#a16207' },
    { value: 'substituted', label: 'Substituted', color: '#7e22ce' },
    { value: 'adulterated', label: 'Adulterated', color: '#be123c' },
]

const isDrugsSelected = (val) => form.drugs_to_test.includes(val)
function toggleDrug(val) {
    if (isReleased) return
    const idx = form.drugs_to_test.indexOf(val)
    if (idx === -1) form.drugs_to_test.push(val)
    else form.drugs_to_test.splice(idx, 1)
}

function saveDraft() {
    form.release = false
    form.post(route('drug-test.save', props.visit.id))
}

function release() {
    if (!form.result) { alert('Please select a test result before releasing.'); return }
    if (!confirm('Release this drug test result?')) return
    form.release = true
    form.post(route('drug-test.save', props.visit.id))
}

function printReport() {
    window.open(route('drug-test.print', props.visit.id), '_blank')
}
</script>

<template>
    <AppLayout :title="`Drug Test — ${patient.full_name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('drug-test.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">Drug Test Entry</h1>
                            <span v-if="drugTest"
                                class="text-xs font-mono font-bold px-2 py-0.5 rounded border"
                                style="background:#fff1f2; color:#be123c; border-color:#fecdd3;">
                                {{ drugTest.code_number }}
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
                        <Printer class="w-4 h-4"/> Print
                    </Button>
                    <Button v-if="!isReleased" variant="outline" class="gap-2"
                        @click="saveDraft" :disabled="form.processing">
                        <Save class="w-4 h-4"/> Save Draft
                    </Button>
                    <Button v-if="!isReleased"
                        class="gap-2 text-white" style="background-color:#e11d48"
                        @click="release" :disabled="form.processing">
                        <CheckCircle2 class="w-4 h-4"/> Release Result
                    </Button>
                </div>
            </div>
        </template>

        <div class="flex gap-5 items-start">

            <!-- LEFT PANEL -->
            <div class="w-64 flex-shrink-0 space-y-4">

                <!-- Patient -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Patient</p>
                    <div class="space-y-1.5 text-xs">
                        <p class="font-bold text-slate-800 text-sm">{{ patient.full_name }}</p>
                        <p class="text-muted-foreground font-mono">{{ patient.patient_code }}</p>
                        <Separator/>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Age/Sex</span>
                            <span class="font-semibold">{{ patient.age_sex }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Birthdate</span>
                            <span class="font-semibold">{{ patient.birthdate }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Civil Status</span>
                            <span class="font-semibold capitalize">{{ patient.civil_status || '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Visit Date</span>
                            <span class="font-semibold">{{ visit.visit_date }}</span>
                        </div>
                    </div>
                </div>

                <!-- Company -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-2">
                    <Label class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Company</Label>
                    <Input v-model="form.company" placeholder="Employer company" class="text-xs h-8"
                        :disabled="isReleased"/>
                </div>

                <!-- Test Purpose -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Test Info</p>
                    <div class="space-y-1.5">
                        <Label class="text-xs">Purpose</Label>
                        <Select v-model="form.test_purpose" :disabled="isReleased">
                            <SelectTrigger class="h-8 text-xs">
                                <SelectValue/>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="o in purposeOptions" :key="o.value" :value="o.value">
                                    {{ o.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <!-- Drugs to test — checkboxes -->
                    <div class="space-y-1.5">
                        <Label class="text-xs">Drugs to Test</Label>
                        <div class="space-y-1.5">
                            <label v-for="opt in drugsOptions" :key="opt.value"
                                class="flex items-center gap-2 cursor-pointer"
                                :class="isReleased ? 'opacity-50 cursor-not-allowed' : ''">
                                <input type="checkbox"
                                    :checked="isDrugsSelected(opt.value)"
                                    @change="toggleDrug(opt.value)"
                                    :disabled="isReleased"
                                    class="rounded border-slate-300 text-rose-600"/>
                                <span class="text-xs font-semibold">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Staff -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Collector</p>
                    <div class="space-y-1.5">
                        <Label class="text-xs">Name</Label>
                        <Input v-model="form.collector_name" class="text-xs h-8" :disabled="isReleased"/>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">License No.</Label>
                        <Input v-model="form.collector_license" class="text-xs h-8 font-mono" :disabled="isReleased"/>
                    </div>
                    <Separator/>
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Head of Lab</p>
                    <div class="space-y-1.5">
                        <Label class="text-xs">Name</Label>
                        <Input v-model="form.head_of_lab_name" class="text-xs h-8" :disabled="isReleased"/>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">License No.</Label>
                        <Input v-model="form.head_of_lab_license" class="text-xs h-8 font-mono" :disabled="isReleased"/>
                    </div>
                </div>

            </div>

            <!-- RIGHT PANEL -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Specimen Collection -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block bg-rose-500"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                            Specimen Collection
                        </h3>
                    </div>
                    <div class="p-5 grid grid-cols-2 gap-4">

                        <div class="space-y-1.5">
                            <Label class="text-xs">Specimen Type</Label>
                            <Select v-model="form.specimen_type" :disabled="isReleased">
                                <SelectTrigger class="h-8 text-xs"><SelectValue/></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="urine">Urine</SelectItem>
                                    <SelectItem value="blood">Blood</SelectItem>
                                    <SelectItem value="other">Other</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Time of Collection</Label>
                            <Input v-model="form.specimen_time" type="time"
                                class="text-xs h-8" :disabled="isReleased"/>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Volume (mL)</Label>
                            <Input v-model="form.specimen_volume" placeholder="e.g. 40"
                                class="text-xs h-8" :disabled="isReleased"/>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Physical Appearance (Color)</Label>
                            <Input v-model="form.specimen_appearance" placeholder="e.g. Yellow"
                                class="text-xs h-8" :disabled="isReleased"/>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Sampling</Label>
                            <Select v-model="form.specimen_sampling" :disabled="isReleased">
                                <SelectTrigger class="h-8 text-xs"><SelectValue/></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="single">Single</SelectItem>
                                    <SelectItem value="split">Split</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Collection Method</Label>
                            <Select v-model="form.specimen_collection" :disabled="isReleased">
                                <SelectTrigger class="h-8 text-xs"><SelectValue/></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="observed">Observed</SelectItem>
                                    <SelectItem value="unobserved">Unobserved</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Temperature -->
                        <div class="col-span-2 flex items-center gap-3 p-3 bg-slate-50 rounded-xl border">
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-slate-700">
                                    Temperature within 32°C–38°C?
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">Read within 4 minutes of collection</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" :value="true" v-model="form.temp_in_range"
                                        :disabled="isReleased" class="text-rose-600"/>
                                    <span class="text-xs font-semibold text-emerald-600">Yes</span>
                                </label>
                                <label class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" :value="false" v-model="form.temp_in_range"
                                        :disabled="isReleased" class="text-rose-600"/>
                                    <span class="text-xs font-semibold text-red-600">No</span>
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Remarks -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-2">
                    <Label class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Remarks</Label>
                    <Input v-model="form.remarks" placeholder="Additional observations..." class="text-xs h-8"
                        :disabled="isReleased"/>
                </div>

                <!-- Test Result -->
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center gap-2">
                        <span class="w-1 h-4 rounded-full inline-block" style="background:#e11d48"></span>
                        <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                            Test Result
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Result selector — large buttons -->
                        <div class="grid grid-cols-4 gap-2">
                            <button v-for="opt in resultOptions" :key="opt.value"
                                type="button"
                                @click="!isReleased && (form.result = opt.value)"
                                :disabled="isReleased"
                                :class="['px-3 py-2 rounded-xl border-2 text-xs font-bold transition-all',
                                    form.result === opt.value ? 'shadow-md scale-105' : 'border-slate-200 text-slate-500 bg-slate-50 hover:border-slate-300']"
                                :style="form.result === opt.value ? {
                                    borderColor: opt.color,
                                    color: opt.color,
                                    backgroundColor: opt.color + '10',
                                } : {}">
                                {{ opt.label }}
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <Label class="text-xs">Result Remarks</Label>
                            <Textarea v-model="form.result_remarks" :rows="2"
                                placeholder="Additional result notes..."
                                class="resize-none text-xs"
                                :disabled="isReleased"/>
                        </div>
                    </div>
                </div>

                <!-- Patient Certification -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <div class="flex items-center gap-3">
                        <label class="flex items-start gap-3 cursor-pointer flex-1">
                            <input type="checkbox" v-model="form.certification_signed"
                                :disabled="isReleased"
                                class="mt-0.5 rounded border-slate-300 text-rose-600"/>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Patient Self-Certification Signed</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Patient has signed the 6-month drug-free declaration
                                </p>
                            </div>
                        </label>
                        <div v-if="form.certification_signed" class="space-y-1.5">
                            <Label class="text-xs">Date Signed</Label>
                            <Input v-model="form.certification_date" type="date"
                                class="text-xs h-8 w-36" :disabled="isReleased"/>
                        </div>
                    </div>
                </div>

                <!-- Action Bar -->
                <div v-if="!isReleased"
                    class="bg-card rounded-xl border shadow-sm px-5 py-4 flex items-center justify-between">
                    <p class="text-xs text-muted-foreground">
                        Select a result before releasing. Save draft to continue later.
                    </p>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" class="gap-2" @click="saveDraft" :disabled="form.processing">
                            <Save class="w-4 h-4"/> Save Draft
                        </Button>
                        <Button class="gap-2 text-white" style="background-color:#e11d48"
                            @click="release" :disabled="form.processing || !form.result">
                            <CheckCircle2 class="w-4 h-4"/> Release Result
                        </Button>
                    </div>
                </div>

                <!-- Released notice -->
                <div v-if="isReleased"
                    class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3">
                    <CheckCircle2 class="w-8 h-8 text-emerald-500 flex-shrink-0"/>
                    <div>
                        <p class="text-sm font-bold text-emerald-700">Result Released</p>
                        <p class="text-xs text-emerald-600 mt-0.5">
                            Result is linked to patient profile and visible to the doctor.
                        </p>
                    </div>
                    <Button variant="outline" class="ml-auto gap-2" @click="printReport">
                        <Printer class="w-4 h-4"/> Print Certificate
                    </Button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
