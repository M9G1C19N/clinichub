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
import { IS_PE_TYPE } from '@/config/visitTypes.js'
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
    visual_acuity_right:      props.vitals?.visual_acuity_right      ?? '',
    visual_acuity_left:       props.vitals?.visual_acuity_left       ?? '',
    ishihara_result:          props.vitals?.ishihara_result          ?? '',
    nurse_notes:              props.vitals?.nurse_notes              ?? '',
    // Medical History
    present_symptoms:        props.vitals?.present_symptoms        ?? '',
    past_illnesses_flags:    props.vitals?.past_illnesses_flags    ?? [],
    past_illnesses_others:   props.vitals?.past_illnesses_others   ?? '',
    past_illnesses_remarks:  props.vitals?.past_illnesses_remarks  ?? '',
    family_history:          props.vitals?.family_history          ?? '',
    accidents_injuries:      props.vitals?.accidents_injuries      ?? 'UNREMARKABLE',
    surgical_history_detail: props.vitals?.surgical_history_detail ?? 'UNREMARKABLE',
    allergies_flags:         props.vitals?.allergies_flags         ?? ['none'],
    allergies_others:        props.vitals?.allergies_others        ?? '',
    menstrual_cycle:         props.vitals?.menstrual_cycle         ?? '',
    lmp:                     props.vitals?.lmp                     ?? 'N/A',
    ob_gravida:              props.vitals?.ob_gravida              ?? '',
    ob_para:                 props.vitals?.ob_para                 ?? '',
    ob_nulligravida:         props.vitals?.ob_nulligravida         ?? false,
    tobacco_use:             props.vitals?.tobacco_use             ?? 'never',
    tobacco_use_details:     props.vitals?.tobacco_use_details     ?? '',
    alcohol_use:             props.vitals?.alcohol_use             ?? 'never',
    alcohol_use_details:     props.vitals?.alcohol_use_details     ?? '',
    pe_remarks:              props.vitals?.pe_remarks              ?? '',
    // Additional vitals
    conversational_hearing:              props.vitals?.conversational_hearing              ?? 'Normal',
    visual_acuity_right_corrected:       props.vitals?.visual_acuity_right_corrected       ?? '',
    visual_acuity_left_corrected:        props.vitals?.visual_acuity_left_corrected        ?? '',
    visual_acuity_near_right:            props.vitals?.visual_acuity_near_right            ?? '',
    visual_acuity_near_left:             props.vitals?.visual_acuity_near_left             ?? '',
    visual_acuity_near_right_corrected:  props.vitals?.visual_acuity_near_right_corrected  ?? '',
    visual_acuity_near_left_corrected:   props.vitals?.visual_acuity_near_left_corrected   ?? '',
    color_vision_result:                 props.vitals?.color_vision_result                 ?? '',
    pe_findings_normal:  props.vitals?.pe_findings_normal  ?? {},
    pe_findings_details: Object.fromEntries(
        ['eyes','nose_sinuses','neck_thyroid','mouth_throat','chest_breast','lungs',
         'heart','abdomen','back','anus','genitals','extremities','skin']
        .map(k => [k, props.vitals?.pe_findings_details?.[k] ?? ''])
    ),
    pe_findings_remarks: props.vitals?.pe_findings_remarks ?? '',
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

// PE system labels matching the actual form
const peSystems = [
    { key: 'eyes',          label: '1. Eyes'          },
    { key: 'nose_sinuses',  label: '2. Nose/Sinuses'  },
    { key: 'neck_thyroid',  label: '4. Neck/Thyroid'  },
    { key: 'mouth_throat',  label: '3. Mouth/Throat'  },
    { key: 'chest_breast',  label: '5. Chest/Breast'  },
    { key: 'lungs',         label: '6. Lungs'         },
    { key: 'heart',         label: '7. Heart'         },
    { key: 'abdomen',       label: '8. Abdomen'       },
    { key: 'back',          label: '9. Back'          },
    { key: 'anus',          label: '10. Anus'         },
    { key: 'genitals',      label: '11. Genitals'     },
    { key: 'extremities',   label: '12. Extremities'  },
    { key: 'skin',          label: '13. Skin'         },
]

const pastIllnessList = [
    { key: 'tuberculosis',    label: '1. Tuberculosis'          },
    { key: 'asthma',          label: '2. Asthma'                },
    { key: 'hypertension',    label: '3. Hypertension'          },
    { key: 'heart_disease',   label: '4. Heart Disease/AMI'     },
    { key: 'cva_stroke',      label: '5. CVA/Stroke'            },
    { key: 'diabetes',        label: '6. Diabetes'              },
    { key: 'kidney',          label: '7. Kidney Disease'        },
    { key: 'liver',           label: '8. Liver Disease'         },
    { key: 'fainting',        label: '9. Fainting/Seizure'      },
    { key: 'headaches',       label: '10. Headaches/Migraine'   },
    { key: 'mental',          label: '11. Mental Disorder'      },
    { key: 'std',             label: '12. Sexually Transmitted' },
    { key: 'malaria',         label: '13. Malaria/Typhoid'      },
    { key: 'hernia',          label: '14. Hernia'               },
    { key: 'hemorrhoids',     label: '15. Hemorrhoids'          },
]

function toggleIllness(key) {
    const arr = form.past_illnesses_flags
    const idx = arr.indexOf(key)
    idx === -1 ? arr.push(key) : arr.splice(idx, 1)
}

function hasIllness(key) {
    return form.past_illnesses_flags.includes(key)
}

function setPeNormal(key, val) {
    form.pe_findings_normal = { ...form.pe_findings_normal, [key]: val }
}

function markAllPeNormal() {
    const all = {}
    peSystems.forEach(s => { all[s.key] = true })
    form.pe_findings_normal = all
}

function setPeDetail(key, val) {
    form.pe_findings_details[key] = val
}

const isPeNormal = (key) => form.pe_findings_normal[key] === true
const isPeAbnormal = (key) => form.pe_findings_normal[key] === false

const isPreEmployment = IS_PE_TYPE(props.visit.visit_type)

function submit() {
    form.transform(data => ({
        ...data,
        pe_findings_normal:  { ...data.pe_findings_normal },
        pe_findings_details: { ...data.pe_findings_details },
    })).post(route('nurse.vitals.store', props.visit.id))
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
                        <div class="p-5 space-y-4">
                            <!-- Column headers -->
                            <div class="grid grid-cols-3 gap-4 text-xs font-semibold text-muted-foreground">
                                <div></div>
                                <div>Uncorrected</div>
                                <div>Corrected</div>
                            </div>
                            <!-- Distant Vision OD -->
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <Label class="text-xs">1. Distant OD (Right)</Label>
                                <Select v-model="form.visual_acuity_right" class="w-full">
                                    <SelectTrigger class="w-full"><SelectValue placeholder="Select"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="v in ['20/20','20/25','20/30','20/40','20/50','20/60','20/80','20/100','20/200','CF','HM','LP','NLP']" :key="v" :value="v">{{ v }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select v-model="form.visual_acuity_right_corrected" class="w-full">
                                    <SelectTrigger class="w-full"><SelectValue placeholder="Select"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="v in ['20/20','20/25','20/30','20/40','20/50','20/60','20/80','20/100','20/200','CF','HM','LP','NLP']" :key="v" :value="v">{{ v }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <!-- Distant Vision OS -->
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <Label class="text-xs">1. Distant OS (Left)</Label>
                                <Select v-model="form.visual_acuity_left" class="w-full">
                                    <SelectTrigger class="w-full"><SelectValue placeholder="Select"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="v in ['20/20','20/25','20/30','20/40','20/50','20/60','20/80','20/100','20/200','CF','HM','LP','NLP']" :key="v" :value="v">{{ v }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select v-model="form.visual_acuity_left_corrected" class="w-full">
                                    <SelectTrigger class="w-full"><SelectValue placeholder="Select"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="v in ['20/20','20/25','20/30','20/40','20/50','20/60','20/80','20/100','20/200','CF','HM','LP','NLP']" :key="v" :value="v">{{ v }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <!-- Near Vision OD -->
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <Label class="text-xs">2. Near OD (Right)</Label>
                                <Input v-model="form.visual_acuity_near_right" class="h-8 text-xs" placeholder="e.g. 20/20"/>
                                <Input v-model="form.visual_acuity_near_right_corrected" class="h-8 text-xs" placeholder="e.g. 20/20"/>
                            </div>
                            <!-- Near Vision OS -->
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <Label class="text-xs">2. Near OS (Left)</Label>
                                <Input v-model="form.visual_acuity_near_left" class="h-8 text-xs" placeholder="e.g. 20/20"/>
                                <Input v-model="form.visual_acuity_near_left_corrected" class="h-8 text-xs" placeholder="e.g. 20/20"/>
                            </div>
                            <!-- Ishihara Test -->
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <Label class="text-xs">3. Ishihara Test</Label>
                                <Select v-model="form.ishihara_result" class="w-full">
                                    <SelectTrigger class="w-full"><SelectValue placeholder="Select result"/></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Normal">Normal</SelectItem>
                                        <SelectItem value="Color Deficiency">Color Deficiency</SelectItem>
                                    </SelectContent>
                                </Select>
                                <div></div>
                            </div>
                            <!-- Color Vision Result -->
                            <div class="grid grid-cols-3 gap-4 items-center">
                                <Label class="text-xs">4. Color Vision Result</Label>
                                <Input v-model="form.color_vision_result" class="h-8 text-xs" placeholder="e.g. Normal, Defective"/>
                                <div></div>
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
                    <!-- ── I. MEDICAL HISTORY ─────────────────── -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-amber-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                I. Medical History
                            </h3>
                        </div>
                        <div class="p-5 space-y-5">

                            <!-- A. Present Symptoms -->
                            <div class="space-y-1.5">
                                <Label class="text-xs font-bold">A. Present Symptoms / Complaints</Label>
                                <Input v-model="form.present_symptoms" class="h-8 text-xs"
                                    placeholder="e.g. Nosebleed, Headache, None"/>
                            </div>

                            <!-- B. Past Illnesses -->
                            <div class="space-y-2">
                                <Label class="text-xs font-bold">B. Past Illnesses / Hospitalizations</Label>
                                <div class="grid grid-cols-3 gap-1.5">
                                    <label v-for="ill in pastIllnessList" :key="ill.key"
                                        class="flex items-center gap-2 cursor-pointer p-2 rounded-lg hover:bg-slate-50 border border-transparent"
                                        :class="hasIllness(ill.key) ? 'bg-red-50 border-red-200' : ''">
                                        <input type="checkbox"
                                            :checked="hasIllness(ill.key)"
                                            @change="toggleIllness(ill.key)"
                                            class="rounded border-slate-300 text-red-600 w-3.5 h-3.5"/>
                                        <span class="text-xs" :class="hasIllness(ill.key) ? 'text-red-700 font-semibold' : 'text-slate-600'">
                                            {{ ill.label }}
                                        </span>
                                    </label>
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    <span class="text-xs text-slate-600 whitespace-nowrap">16. Others:</span>
                                    <Input v-model="form.past_illnesses_others" class="h-7 text-xs flex-1"
                                        placeholder="Specify other illnesses..."/>
                                </div>
                            </div>

                            <!-- C-E. Family, Accidents, Surgical -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-bold">C. Family Medical History</Label>
                                    <Input v-model="form.family_history" class="h-8 text-xs"
                                        placeholder="e.g. DM Type II Father"/>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-bold">D. Accidents / Injuries</Label>
                                    <Input v-model="form.accidents_injuries" class="h-8 text-xs"
                                        placeholder="UNREMARKABLE"/>
                                </div>
                                <div class="space-y-1.5">
                                    <Label class="text-xs font-bold">E. Surgical History</Label>
                                    <Input v-model="form.surgical_history_detail" class="h-8 text-xs"
                                        placeholder="UNREMARKABLE"/>
                                </div>
                            </div>

                            <!-- F. Allergies -->
                            <div class="space-y-2">
                                <Label class="text-xs font-bold">F. Allergies</Label>
                                <div class="flex items-center gap-5">
                                    <label v-for="opt in [{val:'none',label:'None'},{val:'food',label:'Food'},{val:'drug',label:'Drug'},{val:'others',label:'Others'}]"
                                        :key="opt.val" class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox"
                                            :checked="form.allergies_flags.includes(opt.val)"
                                            @change="() => {
                                                const idx = form.allergies_flags.indexOf(opt.val)
                                                idx === -1 ? form.allergies_flags.push(opt.val) : form.allergies_flags.splice(idx,1)
                                            }"
                                            class="rounded border-slate-300 text-blue-600 w-3.5 h-3.5"/>
                                        <span class="text-xs text-slate-600">{{ opt.label }}</span>
                                    </label>
                                    <Input v-model="form.allergies_others" class="h-7 text-xs flex-1"
                                        placeholder="Specify if Others..."/>
                                </div>
                            </div>

                            <!-- G. Menstrual + H. OB History — for female -->
                            <div v-if="patient.sex === 'female'" class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold">G. Menstrual History</Label>
                                    <div class="flex flex-wrap gap-3">
                                        <label v-for="opt in [{val:'regular',label:'Regular'},{val:'irregular',label:'Irregular'},{val:'menopause',label:'Menopause'},{val:'postmenopausal',label:'Postmenopausal'}]"
                                            :key="opt.val" class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" :value="opt.val" v-model="form.menstrual_cycle"
                                                class="border-slate-300 text-blue-600 w-3.5 h-3.5"/>
                                            <span class="text-xs text-slate-600">{{ opt.label }}</span>
                                        </label>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <Label class="text-xs text-muted-foreground whitespace-nowrap">LMP & Duration:</Label>
                                        <Input v-model="form.lmp" class="h-7 text-xs" placeholder="N/A"/>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold">H. OB History</Label>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="checkbox" v-model="form.ob_nulligravida"
                                                class="rounded border-slate-300 text-blue-600 w-3.5 h-3.5"/>
                                            <span class="text-xs">Nulligravida</span>
                                        </label>
                                        <div class="flex items-center gap-1.5">
                                            <Label class="text-xs">Gravida:</Label>
                                            <Input v-model="form.ob_gravida" class="h-7 text-xs w-16" placeholder="N/A"/>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <Label class="text-xs">Para:</Label>
                                            <Input v-model="form.ob_para" class="h-7 text-xs w-16" placeholder="N/A"/>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- I. Personal/Social History -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold">I. Tobacco Use</Label>
                                    <div class="flex gap-4">
                                        <label v-for="opt in [{val:'current',label:'Current'},{val:'former',label:'Former'},{val:'never',label:'Never'}]"
                                            :key="opt.val" class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" :value="opt.val" v-model="form.tobacco_use"
                                                class="border-slate-300 text-blue-600 w-3.5 h-3.5"/>
                                            <span class="text-xs text-slate-600">{{ opt.label }}</span>
                                        </label>
                                    </div>
                                    <Input v-model="form.tobacco_use_details" class="h-7 text-xs"
                                        placeholder="Details (e.g. 1 pack/day for 5 years)"/>
                                </div>
                                <div class="space-y-2">
                                    <Label class="text-xs font-bold">Alcohol Use</Label>
                                    <div class="flex gap-4">
                                        <label v-for="opt in [{val:'current',label:'Current'},{val:'former',label:'Former'},{val:'never',label:'Never'}]"
                                            :key="opt.val" class="flex items-center gap-1.5 cursor-pointer">
                                            <input type="radio" :value="opt.val" v-model="form.alcohol_use"
                                                class="border-slate-300 text-blue-600 w-3.5 h-3.5"/>
                                            <span class="text-xs text-slate-600">{{ opt.label }}</span>
                                        </label>
                                    </div>
                                    <Input v-model="form.alcohol_use_details" class="h-7 text-xs"
                                        placeholder="Details (e.g. occasional, socially)"/>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ── Remarks (Nurse) ──────────────────────── -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center gap-2">
                            <span class="w-1 h-4 rounded-full inline-block bg-sky-500"></span>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">Remarks</h3>
                        </div>
                        <div class="p-5">
                            <Textarea v-model="form.pe_remarks" :rows="3"
                                class="resize-none text-xs"
                                placeholder="General remarks to be printed on the medical exam report..."/>
                        </div>
                    </div>

                    <!-- ── II. PHYSICAL EXAMINATION FINDINGS ──── -->
                    <div class="bg-card rounded-xl border shadow-sm">
                        <div class="px-5 py-3.5 border-b flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-1 h-4 rounded-full inline-block bg-emerald-500"></span>
                                <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                    PE Findings — Normal / Abnormal
                                </h3>
                            </div>
                            <Button type="button" variant="outline" size="sm"
                                class="text-xs h-7 gap-1.5 text-emerald-700 border-emerald-300 hover:bg-emerald-50"
                                @click="markAllPeNormal">
                                <CheckCircle2 class="w-3.5 h-3.5"/>
                                Mark All Normal
                            </Button>
                        </div>
                        <div class="p-5">
                            <div class="grid grid-cols-2 gap-2 mb-4">
                                <div v-for="sys in peSystems" :key="sys.key"
                                    class="flex flex-col gap-1.5 p-2 rounded-lg border text-xs transition-colors"
                                    :class="isPeNormal(sys.key) ? 'bg-emerald-50 border-emerald-200'
                                        : isPeAbnormal(sys.key) ? 'bg-red-50 border-red-200'
                                        : 'bg-slate-50 border-slate-200'">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-700">{{ sys.label }}</span>
                                        <div class="flex items-center gap-2">
                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="radio"
                                                    :checked="isPeNormal(sys.key)"
                                                    @change="() => setPeNormal(sys.key, true)"
                                                    class="w-3 h-3 text-emerald-600 border-slate-300"/>
                                                <span :class="isPeNormal(sys.key) ? 'text-emerald-700 font-bold' : 'text-slate-500'">
                                                    Normal
                                                </span>
                                            </label>
                                            <label class="flex items-center gap-1 cursor-pointer">
                                                <input type="radio"
                                                    :checked="isPeAbnormal(sys.key)"
                                                    @change="() => setPeNormal(sys.key, false)"
                                                    class="w-3 h-3 text-red-600 border-slate-300"/>
                                                <span :class="isPeAbnormal(sys.key) ? 'text-red-700 font-bold' : 'text-slate-500'">
                                                    Abnormal
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Per-system text shown when abnormal -->
                                    <Input v-if="isPeAbnormal(sys.key)"
                                        v-model="form.pe_findings_details[sys.key]"
                                        class="h-7 text-xs border-red-300 bg-white"
                                        placeholder="Describe specific findings..."/>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <Label class="text-xs">Other PE Findings</Label>
                                <Textarea v-model="form.pe_findings_remarks" :rows="2"
                                    class="resize-none text-xs"
                                    placeholder="Other findings not listed above..."/>
                            </div>
                        </div>
                    </div>

                    <!-- Additional vitals for the form -->
                    <div class="bg-card rounded-xl border shadow-sm p-5">
                        <div class="space-y-1.5">
                            <Label class="text-xs font-semibold">Conversational Hearing</Label>
                            <div class="flex gap-4">
                                <label v-for="opt in ['Normal','Defective']" :key="opt"
                                    class="flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" :value="opt" v-model="form.conversational_hearing"
                                        class="border-slate-300 text-blue-600 w-3.5 h-3.5"/>
                                    <span class="text-xs">{{ opt }}</span>
                                </label>
                            </div>
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
