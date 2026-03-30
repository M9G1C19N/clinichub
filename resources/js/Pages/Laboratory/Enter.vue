<script setup>
import { ref, computed } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import {
    FlaskConical, Save, CheckCircle2,
    AlertTriangle, Printer,
} from 'lucide-vue-next'

const props = defineProps({
    visit:      Object,
    patient:    Object,
    labRequest: Object,
    tests:      Array,
})

const isReleased = props.labRequest?.status === 'released'

// Build form results array
const form = useForm({
    results: props.tests.map(t => ({
        test_id: t.id,
        value:   t.result_value ?? '',
        remarks: t.remarks ?? '',
    })),
    examined_by_name:    props.labRequest?.examined_by_name    ?? '',
    examined_by_license: props.labRequest?.examined_by_license ?? '',
    noted_by_name:       props.labRequest?.noted_by_name       ?? '',
    noted_by_license:    props.labRequest?.noted_by_license    ?? '',
    release: false,
})

// Group tests by category for display
const categoryLabel = {
    hematology: 'Complete Blood Count',
    chemistry:  'Blood Chemistry',
    urinalysis: 'Urinalysis',
    stool:      'Stool Exam',
    serology:   'Others (Serology)',
    thyroid:    'Thyroid Panel',
    other:      'Other Tests',
}

const categoryColor = {
    hematology: 'text-red-700 bg-red-50 border-red-200',
    chemistry:  'text-blue-700 bg-blue-50 border-blue-200',
    urinalysis: 'text-amber-700 bg-amber-50 border-amber-200',
    stool:      'text-orange-700 bg-orange-50 border-orange-200',
    serology:   'text-purple-700 bg-purple-50 border-purple-200',
    thyroid:    'text-teal-700 bg-teal-50 border-teal-200',
}

const groupedTests = computed(() => {
    const groups = {}
    props.tests.forEach((test, idx) => {
        if (!groups[test.category]) groups[test.category] = []
        groups[test.category].push({ ...test, formIndex: idx })
    })
    return groups
})

// Count abnormal in current entries
const abnormalCount = computed(() =>
    props.tests.filter(t => {
        if (t.is_text_result) return false
        const val = parseFloat(form.results[props.tests.indexOf(t)]?.value)
        return !isNaN(val) && t.result_value && t.is_abnormal
    }).length
)

// Get flag for a result value on the fly
function getFlag(test, value) {
    if (test.is_text_result || !value) return null
    const v = parseFloat(value)
    if (isNaN(v)) return null

    // Parse normal_range string like "135-175"
    const range = test.normal_range
    if (!range || range === '—') return null
    const parts = range.replace(/[<>]/g, '').split('-')
    if (parts.length === 2) {
        const min = parseFloat(parts[0])
        const max = parseFloat(parts[1])
        if (!isNaN(min) && !isNaN(max)) {
            if (v < min) return 'L'
            if (v > max) return 'H'
        }
    }
    return null
}

function saveDraft() {
    form.release = false
    form.post(route('laboratory.save', props.visit.id))
}

function release() {
    if (!confirm('Release results? This will notify the doctor and cannot be undone.')) return
    form.release = true
    form.post(route('laboratory.save', props.visit.id))
}

function printResults() {
    window.print()
}
</script>

<template>
    <AppLayout :title="`Lab — ${patient.full_name}`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('laboratory.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h1 class="text-xl font-bold text-slate-800">Lab Results Entry</h1>
                            <span v-if="labRequest"
                                class="text-xs font-mono font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                {{ labRequest.request_number }}
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
                    <Button variant="outline" size="sm" class="gap-2" @click="printResults">
                        <Printer class="w-4 h-4"/>
                        Print
                    </Button>
                    <Button v-if="!isReleased" variant="outline" class="gap-2"
                        @click="saveDraft" :disabled="form.processing">
                        <Save class="w-4 h-4"/>
                        Save Draft
                    </Button>
                    <Button v-if="!isReleased"
                        class="gap-2 text-white" style="background-color:#3B82F6"
                        @click="release" :disabled="form.processing">
                        <CheckCircle2 class="w-4 h-4"/>
                        Release Results
                    </Button>
                </div>
            </div>
        </template>

        <div class="flex gap-5 items-start">

            <!-- ── LEFT: Patient Info ─────────── -->
            <div class="w-56 flex-shrink-0 space-y-4">

                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Patient</p>
                    <div class="space-y-2 text-xs">
                        <div>
                            <p class="font-bold text-slate-800 text-sm">{{ patient.full_name }}</p>
                            <p class="text-muted-foreground font-mono">{{ patient.patient_code }}</p>
                        </div>
                        <Separator/>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Age/Sex</span>
                            <span class="font-semibold">{{ patient.age_sex }}</span>
                        </div>
                        <div v-if="visit.employer_company" class="flex justify-between">
                            <span class="text-muted-foreground">Company</span>
                            <span class="font-semibold text-purple-600 text-right">{{ visit.employer_company }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Type</span>
                            <span :class="['font-semibold',
                                visit.visit_type === 'pre_employment' ? 'text-purple-600' : 'text-blue-600']">
                                {{ visit.visit_type === 'pre_employment' ? 'Pre-Employment' : 'OPD' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Exam Date</span>
                            <span class="font-semibold">{{ visit.visit_date }}</span>
                        </div>
                    </div>
                </div>

                <!-- Staff Info -->
                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Staff Details</p>

                    <div class="space-y-1.5">
                        <Label class="text-xs">Examined By (Med Tech)</Label>
                        <Input v-model="form.examined_by_name"
                            placeholder="Full name" class="text-xs h-8"
                            :disabled="isReleased"/>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">License No.</Label>
                        <Input v-model="form.examined_by_license"
                            placeholder="PRC License No." class="text-xs h-8 font-mono"
                            :disabled="isReleased"/>
                    </div>

                    <Separator/>

                    <div class="space-y-1.5">
                        <Label class="text-xs">Noted By (Pathologist)</Label>
                        <Input v-model="form.noted_by_name"
                            placeholder="Full name" class="text-xs h-8"
                            :disabled="isReleased"/>
                    </div>
                    <div class="space-y-1.5">
                        <Label class="text-xs">License No.</Label>
                        <Input v-model="form.noted_by_license"
                            placeholder="PRC License No." class="text-xs h-8 font-mono"
                            :disabled="isReleased"/>
                    </div>
                </div>

                <!-- Abnormal summary -->
                <div v-if="labRequest" class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Status</p>
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Status</span>
                            <span :class="['font-bold',
                                labRequest.status === 'released' ? 'text-emerald-600' : 'text-amber-600']">
                                {{ labRequest.status.toUpperCase() }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Abnormal</span>
                            <span :class="['font-bold',
                                tests.some(t => t.is_abnormal) ? 'text-red-600' : 'text-emerald-600']">
                                {{ tests.filter(t => t.is_abnormal).length }} flags
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── RIGHT: Results Form ────────── -->
            <div class="flex-1 min-w-0 space-y-4">

                <!-- Results by category — matching actual document layout -->
                <div v-for="(testGroup, category) in groupedTests" :key="category"
                    class="bg-card rounded-xl border shadow-sm overflow-hidden">

                    <!-- Category header -->
                    <div :class="['px-5 py-3 border-b flex items-center gap-2',
                        categoryColor[category] ?? 'text-slate-700 bg-slate-50 border-slate-200']">
                        <FlaskConical class="w-4 h-4"/>
                        <h3 class="text-sm font-bold uppercase tracking-wide">
                            {{ categoryLabel[category] ?? category }}
                        </h3>
                    </div>

                    <!-- Results table — matching document format -->
                    <table class="w-full">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="text-left px-5 py-2.5 text-xs font-semibold text-slate-500 uppercase w-1/3">
                                    Examination
                                </th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase w-1/4">
                                    Result
                                </th>
                                <th class="text-left px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase">
                                    Reference Range
                                </th>
                                <th class="px-4 py-2.5 text-xs font-semibold text-slate-500 uppercase w-16">
                                    Flag
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="test in testGroup" :key="test.id"
                                :class="['transition-colors',
                                    test.is_abnormal ? 'bg-red-50' : 'hover:bg-slate-50']">

                                <!-- Test name -->
                                <td class="px-5 py-2.5 text-xs font-medium text-slate-700">
                                    {{ test.test_name }}
                                </td>

                                <!-- Result input -->
                                <td class="px-4 py-2">
                                    <Input
                                        v-model="form.results[test.formIndex].value"
                                        :placeholder="test.is_text_result ? 'Enter result...' : '0.00'"
                                        class="h-7 text-xs font-semibold"
                                        :class="getFlag(test, form.results[test.formIndex].value) === 'H'
                                            ? 'border-red-400 bg-red-50 text-red-700'
                                            : getFlag(test, form.results[test.formIndex].value) === 'L'
                                            ? 'border-blue-400 bg-blue-50 text-blue-700' : ''"
                                        :disabled="isReleased"/>
                                </td>

                                <!-- Normal range -->
                                <td class="px-4 py-2.5 text-xs text-slate-500 font-mono">
                                    {{ test.normal_range }}
                                </td>

                                <!-- Abnormal flag -->
                                <td class="px-4 py-2.5 text-center">
                                    <span v-if="getFlag(test, form.results[test.formIndex].value)"
                                        :class="[
                                            'text-xs font-black px-2 py-0.5 rounded font-mono',
                                            getFlag(test, form.results[test.formIndex].value) === 'H'
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-blue-100 text-blue-700'
                                        ]">
                                        {{ getFlag(test, form.results[test.formIndex].value) }}
                                    </span>
                                    <span v-else-if="test.is_abnormal && test.result_value"
                                        :class="['text-xs font-black px-2 py-0.5 rounded font-mono',
                                            test.abnormal_flag === 'H' ? 'bg-red-100 text-red-700' :
                                            test.abnormal_flag === 'C' ? 'bg-red-500 text-white' :
                                                                         'bg-blue-100 text-blue-700']">
                                        {{ test.abnormal_flag }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Remarks -->
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <Label class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 block">
                        Remarks
                    </Label>
                    <textarea
                        v-model="form.results[0]"
                        class="w-full border border-input rounded-lg px-3 py-2 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                        :rows="2"
                        placeholder="General remarks for this lab request..."
                        :disabled="isReleased"/>
                </div>

                <!-- Action bar -->
                <div v-if="!isReleased"
                    class="bg-card rounded-xl border shadow-sm px-5 py-4 flex items-center justify-between">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <AlertTriangle v-if="tests.some(t => t.is_abnormal)" class="w-4 h-4 text-red-500"/>
                        <span v-if="tests.some(t => t.is_abnormal)" class="text-red-600 font-semibold">
                            {{ tests.filter(t => t.is_abnormal).length }} abnormal result(s) flagged
                        </span>
                        <span v-else>Enter results then save draft or release</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="outline" class="gap-2" @click="saveDraft"
                            :disabled="form.processing">
                            <Save class="w-4 h-4"/>
                            Save Draft
                        </Button>
                        <Button class="gap-2 text-white" style="background-color:#3B82F6"
                            @click="release" :disabled="form.processing">
                            <CheckCircle2 class="w-4 h-4"/>
                            Release Results
                        </Button>
                    </div>
                </div>

                <!-- Released notice -->
                <div v-if="isReleased"
                    class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center gap-3">
                    <CheckCircle2 class="w-8 h-8 text-emerald-500 flex-shrink-0"/>
                    <div>
                        <p class="text-sm font-bold text-emerald-700">Results Released</p>
                        <p class="text-xs text-emerald-600 mt-0.5">
                            Doctor has been notified. Results are now visible in patient profile.
                        </p>
                    </div>
                    <Button variant="outline" class="ml-auto gap-2" @click="printResults">
                        <Printer class="w-4 h-4"/>
                        Print Report
                    </Button>
                </div>

            </div>
        </div>

    </AppLayout>
</template>
