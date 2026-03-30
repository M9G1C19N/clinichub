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
import {
    Activity, Heart, Thermometer, Wind,
    Eye, Save, User, CheckCircle2,
} from 'lucide-vue-next'

const props = defineProps({
    visit:   Object,
    patient: Object,
    vitals:  Object,
})

const form = useForm({
    weight_kg:                props.vitals?.weight_kg                ?? '',
    height_cm:                props.vitals?.height_cm                ?? '',
    blood_pressure_systolic:  props.vitals?.blood_pressure_systolic  ?? '',
    blood_pressure_diastolic: props.vitals?.blood_pressure_diastolic ?? '',
    pulse_rate:               props.vitals?.pulse_rate               ?? '',
    respiratory_rate:         props.vitals?.respiratory_rate         ?? '',
    temperature_celsius:      props.vitals?.temperature_celsius      ?? '',
    oxygen_saturation:        props.vitals?.oxygen_saturation        ?? '',
    heart_rate:               props.vitals?.heart_rate               ?? '',
    visual_acuity_right:      props.vitals?.visual_acuity_right      ?? '',
    visual_acuity_left:       props.vitals?.visual_acuity_left       ?? '',
    ishihara_result:          props.vitals?.ishihara_result          ?? '',
    nurse_notes:              props.vitals?.nurse_notes              ?? '',
})

// Live BMI calculation
const bmi = computed(() => {
    const w = parseFloat(form.weight_kg)
    const h = parseFloat(form.height_cm)
    if (!w || !h || h === 0) return null
    const heightM = h / 100
    return (w / (heightM * heightM)).toFixed(1)
})

const bmiCategory = computed(() => {
    if (!bmi.value) return null
    const b = parseFloat(bmi.value)
    if (b < 18.5) return { label: 'Underweight', color: 'text-blue-600'    }
    if (b < 25.0) return { label: 'Normal',      color: 'text-emerald-600' }
    if (b < 30.0) return { label: 'Overweight',  color: 'text-amber-600'   }
    return                { label: 'Obese',       color: 'text-red-600'     }
})

// BP assessment
const bpStatus = computed(() => {
    const s = parseInt(form.blood_pressure_systolic)
    const d = parseInt(form.blood_pressure_diastolic)
    if (!s || !d) return null
    if (s < 90  || d < 60)  return { label: 'Low BP',       color: 'text-blue-600'  }
    if (s <= 120 && d <= 80) return { label: 'Normal',       color: 'text-emerald-600' }
    if (s <= 130 && d <= 80) return { label: 'Elevated',     color: 'text-amber-500' }
    if (s <= 140 || d <= 90) return { label: 'High Stage 1', color: 'text-orange-600' }
    return                          { label: 'High Stage 2', color: 'text-red-600'   }
})

const isPreEmployment = props.visit.visit_type === 'pre_employment'

function submit() {
    form.post(route('nurse.vitals.store', props.visit.id))
}
</script>

<template>
    <AppLayout title="Record Vitals">
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('nurse.index')">
                    <Button variant="outline" size="icon" class="h-8 w-8">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </Button>
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Record Vitals</h1>
                    <p class="text-slate-400 text-xs mt-0.5">{{ visit.visit_date }}</p>
                </div>
            </div>
        </template>

        <form @submit.prevent="submit">
            <div class="flex gap-5 items-start">

                <!-- ── LEFT: Patient + Summary ──── -->
                <div class="w-64 flex-shrink-0 space-y-4">

                    <!-- Patient card -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Patient</p>
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold"
                                style="background:#1B4F9B">
                                {{ patient.full_name.charAt(0) }}
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
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Visit Type</span>
                                <span :class="['font-semibold', visit.visit_type === 'pre_employment' ? 'text-purple-600' : 'text-blue-600']">
                                    {{ visit.visit_type === 'pre_employment' ? 'Pre-Employment' : 'OPD' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Live BMI card -->
                    <div class="bg-card rounded-xl border shadow-sm p-4">
                        <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3">Live Calculations</p>

                        <div class="text-center py-3">
                            <p class="text-xs text-muted-foreground mb-1">BMI</p>
                            <p :class="['text-4xl font-black', bmiCategory?.color ?? 'text-slate-300']">
                                {{ bmi ?? '—' }}
                            </p>
                            <p v-if="bmiCategory" :class="['text-xs font-semibold mt-1', bmiCategory.color]">
                                {{ bmiCategory.label }}
                            </p>
                        </div>

                        <Separator class="my-3"/>

                        <div class="text-center">
                            <p class="text-xs text-muted-foreground mb-1">Blood Pressure</p>
                            <p class="text-lg font-black text-slate-800">
                                {{ form.blood_pressure_systolic || '—' }}/{{ form.blood_pressure_diastolic || '—' }}
                                <span class="text-xs font-normal text-muted-foreground">mmHg</span>
                            </p>
                            <p v-if="bpStatus" :class="['text-xs font-semibold mt-0.5', bpStatus.color]">
                                {{ bpStatus.label }}
                            </p>
                        </div>
                    </div>

                    <!-- Previous vitals if updating -->
                    <div v-if="vitals" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <CheckCircle2 class="w-4 h-4 text-emerald-600"/>
                            <p class="text-xs font-bold text-emerald-700">Vitals on File</p>
                        </div>
                        <p class="text-xs text-emerald-600">Recorded: {{ vitals.recorded_at }}</p>
                        <p class="text-xs text-emerald-600">By: {{ vitals.recorded_by }}</p>
                        <p class="text-xs text-emerald-500 mt-1">Submitting will update existing record.</p>
                    </div>

                </div>

                <!-- ── RIGHT: Vitals Form ────────── -->
                <div class="flex-1 min-w-0 space-y-4">

                    <!-- Physical Measurements -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <Activity class="w-4 h-4 text-muted-foreground"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Physical Measurements</h3>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">

                            <div class="space-y-1.5">
                                <Label class="text-xs">Weight (kg)</Label>
                                <Input v-model="form.weight_kg" type="number"
                                    step="0.1" min="1" max="300"
                                    placeholder="e.g. 65.5"
                                    :class="form.errors.weight_kg ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.weight_kg" class="text-xs text-red-500">{{ form.errors.weight_kg }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Height (cm)</Label>
                                <Input v-model="form.height_cm" type="number"
                                    step="0.1" min="50" max="250"
                                    placeholder="e.g. 165"
                                    :class="form.errors.height_cm ? 'border-red-400' : ''"/>
                                <p v-if="form.errors.height_cm" class="text-xs text-red-500">{{ form.errors.height_cm }}</p>
                            </div>

                            <!-- BMI display -->
                            <div class="space-y-1.5">
                                <Label class="text-xs">BMI (auto-calculated)</Label>
                                <div :class="['px-3 py-2.5 border rounded-xl text-sm font-bold',
                                    bmiCategory ? bmiCategory.color : 'text-slate-400',
                                    'bg-slate-50 border-slate-200']">
                                    {{ bmi ? `${bmi} — ${bmiCategory?.label}` : 'Enter weight & height' }}
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Cardiovascular -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <Heart class="w-4 h-4 text-red-400"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Cardiovascular & Respiratory</h3>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">

                            <!-- Blood Pressure -->
                            <div class="col-span-2 space-y-1.5">
                                <Label class="text-xs">Blood Pressure (mmHg)</Label>
                                <div class="flex items-center gap-2">
                                    <Input v-model="form.blood_pressure_systolic"
                                        type="number" min="60" max="250"
                                        placeholder="Systolic"
                                        :class="form.errors.blood_pressure_systolic ? 'border-red-400' : ''"/>
                                    <span class="text-slate-400 font-bold text-lg flex-shrink-0">/</span>
                                    <Input v-model="form.blood_pressure_diastolic"
                                        type="number" min="40" max="150"
                                        placeholder="Diastolic"
                                        :class="form.errors.blood_pressure_diastolic ? 'border-red-400' : ''"/>
                                </div>
                                <p v-if="bpStatus" :class="['text-xs font-semibold', bpStatus.color]">
                                    {{ bpStatus.label }}
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Pulse Rate (bpm)</Label>
                                <Input v-model="form.pulse_rate"
                                    type="number" min="30" max="250"
                                    placeholder="e.g. 72"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Heart Rate (bpm)</Label>
                                <Input v-model="form.heart_rate"
                                    type="number" min="30" max="250"
                                    placeholder="e.g. 72"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Respiratory Rate (/min)</Label>
                                <Input v-model="form.respiratory_rate"
                                    type="number" min="5" max="60"
                                    placeholder="e.g. 16"/>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">SpO₂ (%)</Label>
                                <Input v-model="form.oxygen_saturation"
                                    type="number" min="50" max="100"
                                    placeholder="e.g. 98"/>
                            </div>

                        </div>
                    </div>

                    <!-- Temperature -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <Thermometer class="w-4 h-4 text-orange-400"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Temperature</h3>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">
                            <div class="space-y-1.5">
                                <Label class="text-xs">Temperature (°C)</Label>
                                <Input v-model="form.temperature_celsius"
                                    type="number" step="0.1" min="30" max="45"
                                    placeholder="e.g. 36.6"/>
                                <p v-if="form.temperature_celsius"
                                    :class="['text-xs font-semibold', parseFloat(form.temperature_celsius) >= 37.5 ? 'text-red-600' : 'text-emerald-600']">
                                    {{ parseFloat(form.temperature_celsius) >= 38.0 ? '🔴 High Fever' :
                                       parseFloat(form.temperature_celsius) >= 37.5 ? '🟡 Fever' :
                                       parseFloat(form.temperature_celsius) >= 36.1 ? '🟢 Normal' : '🔵 Low' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Visual Acuity — Pre-Employment only -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <Eye class="w-4 h-4 text-blue-400"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                Visual Acuity & Ishihara
                            </h3>
                            <span v-if="!isPreEmployment"
                                class="text-xs text-muted-foreground">(Pre-Employment)</span>
                        </div>
                        <div class="p-5 grid grid-cols-3 gap-4">

                            <div class="space-y-1.5">
                                <Label class="text-xs">Right Eye (OD)</Label>
                                <Select v-model="form.visual_acuity_right">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="v in ['20/20','20/25','20/30','20/40','20/50','20/60','20/80','20/100','20/200','CF','HM','LP','NLP']"
                                            :key="v" :value="v">{{ v }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Left Eye (OS)</Label>
                                <Select v-model="form.visual_acuity_left">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="v in ['20/20','20/25','20/30','20/40','20/50','20/60','20/80','20/100','20/200','CF','HM','LP','NLP']"
                                            :key="v" :value="v">{{ v }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="space-y-1.5">
                                <Label class="text-xs">Ishihara Test</Label>
                                <Select v-model="form.ishihara_result">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select result"/>
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Normal">Normal</SelectItem>
                                        <SelectItem value="Color Deficiency">Color Deficiency</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                        </div>
                    </div>

                    <!-- Nurse Notes -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <Wind class="w-4 h-4 text-slate-400"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Nurse Notes</h3>
                            <span class="text-xs text-muted-foreground">(Optional)</span>
                        </div>
                        <div class="p-5">
                            <Textarea v-model="form.nurse_notes" :rows="3"
                                placeholder="Additional observations, patient complaints, or notes for the doctor..."
                                class="resize-none"/>
                        </div>
                    </div>

                    <!-- Action Bar -->
                    <div class="bg-card rounded-xl border shadow-sm px-5 py-3.5 flex items-center justify-between">
                        <p class="text-xs text-muted-foreground">
                            BMI is auto-calculated from weight and height.
                        </p>
                        <div class="flex items-center gap-2">
                            <Link :href="route('nurse.index')">
                                <Button type="button" variant="outline">Cancel</Button>
                            </Link>
                            <Button type="submit" :disabled="form.processing"
                                style="background-color:#1B4F9B" class="gap-2">
                                <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <Save v-else class="w-4 h-4"/>
                                {{ form.processing ? 'Saving...' : 'Save Vitals' }}
                            </Button>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </AppLayout>
</template>
