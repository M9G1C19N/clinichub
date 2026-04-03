<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Pill, Plus, Trash2, Save, Search, User, AlertTriangle, Stethoscope } from 'lucide-vue-next'

const props = defineProps({
    visit:    Object,
    patient:  Object,
    doctor:   Object,
    doctors:  Array,
    is_nurse: Boolean,
})

const form = useForm({
    patient_id:       props.patient?.id  ?? '',
    patient_visit_id: props.visit?.id    ?? '',
    doctor_id:        props.doctor?.id   ?? '',
    rx_date:          new Date().toISOString().split('T')[0],
    items: [
        { drug: '', dosage: '', form: '', quantity: '', frequency: '', duration: '', instructions: '' },
    ],
    notes:         props.visit?.diagnosis_hint ?? '',
    is_controlled: false,
})

// Selected doctor object (reactive, for display)
const selectedDoctor = ref(props.doctor ?? null)

function selectDoctor(id) {
    const d = props.doctors?.find(d => d.id === Number(id))
    if (d) selectedDoctor.value = d
    form.doctor_id = id
}

const patientSearch   = ref('')
const patientResults  = ref([])
const selectedPatient = ref(props.patient ?? null)

async function searchPatients() {
    if (patientSearch.value.length < 2) { patientResults.value = []; return }
    try {
        const res  = await fetch(route('queue.search-patient') + '?q=' + encodeURIComponent(patientSearch.value))
        const data = await res.json()
        // QueueController returns plain array, not { patients: [] }
        patientResults.value = Array.isArray(data) ? data : (data.patients ?? [])
    } catch { patientResults.value = [] }
}

function selectPatient(p) {
    selectedPatient.value = p
    form.patient_id       = p.id
    patientResults.value  = []
    patientSearch.value   = ''
}

function addItem() {
    form.items.push({ drug: '', dosage: '', form: '', quantity: '', frequency: '', duration: '', instructions: '' })
}
function removeItem(i) {
    if (form.items.length === 1) return
    form.items.splice(i, 1)
}

const showPicker = ref(false)
const commonDrugs = [
    { drug:'Amoxicillin',     dosage:'500mg',  form:'Capsule', quantity:'21',  frequency:'3x daily',   duration:'7 days',   instructions:'Take with food'          },
    { drug:'Paracetamol',     dosage:'500mg',  form:'Tablet',  quantity:'20',  frequency:'q4h PRN',    duration:'5 days',   instructions:'For fever and pain'       },
    { drug:'Ibuprofen',       dosage:'400mg',  form:'Tablet',  quantity:'15',  frequency:'3x daily',   duration:'5 days',   instructions:'Take after meals'         },
    { drug:'Cetirizine',      dosage:'10mg',   form:'Tablet',  quantity:'7',   frequency:'Once daily', duration:'7 days',   instructions:'At bedtime'               },
    { drug:'Metformin',       dosage:'500mg',  form:'Tablet',  quantity:'60',  frequency:'Twice daily',duration:'30 days',  instructions:'After meals'              },
    { drug:'Amlodipine',      dosage:'5mg',    form:'Tablet',  quantity:'30',  frequency:'Once daily', duration:'30 days',  instructions:'Same time daily'          },
    { drug:'Omeprazole',      dosage:'20mg',   form:'Capsule', quantity:'14',  frequency:'Twice daily',duration:'7 days',   instructions:'Before meals'             },
    { drug:'Azithromycin',    dosage:'500mg',  form:'Tablet',  quantity:'3',   frequency:'Once daily', duration:'3 days',   instructions:'Complete full course'     },
    { drug:'Losartan',        dosage:'50mg',   form:'Tablet',  quantity:'30',  frequency:'Once daily', duration:'30 days',  instructions:'Monitor BP regularly'     },
    { drug:'Mefenamic Acid',  dosage:'500mg',  form:'Capsule', quantity:'20',  frequency:'3x daily',   duration:'5 days',   instructions:'After meals, PRN pain'    },
    { drug:'Co-Amoxiclav',    dosage:'625mg',  form:'Tablet',  quantity:'14',  frequency:'Twice daily',duration:'7 days',   instructions:'With food'                },
    { drug:'Loperamide',      dosage:'2mg',    form:'Capsule', quantity:'6',   frequency:'After loose stool',duration:'PRN',instructions:'Max 8 caps/day'          },
]

function addFromPicker(drug) {
    const empty = form.items.find(m => !m.drug)
    if (empty) Object.assign(empty, drug)
    else form.items.push({ ...drug })
    showPicker.value = false
}

function submit() { form.post(route('prescriptions.store')) }
</script>

<template>
    <AppLayout title="Write Prescription">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <Link :href="route('prescriptions.index')">
                        <Button variant="outline" size="icon" class="h-8 w-8">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </Button>
                    </Link>
                    <div>
                        <h1 class="text-xl font-bold text-slate-800">Write Prescription</h1>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <template v-if="is_nurse">
                                Nurse: {{ doctor.name }}
                                <span v-if="selectedDoctor?.name" class="text-blue-600 font-semibold ml-1">· Rx by Dr. {{ selectedDoctor.name }}</span>
                            </template>
                            <template v-else>
                                Dr. {{ doctor.name }}
                                <span v-if="doctor.prc_number">· PRC {{ doctor.prc_number }}</span>
                                <span v-if="doctor.s2_number" class="text-amber-600 font-semibold">· S2 {{ doctor.s2_number }}</span>
                            </template>
                        </p>
                    </div>
                </div>
                <Button @click="submit" :disabled="form.processing || !form.patient_id || (is_nurse && !form.doctor_id)"
                    class="gap-2 text-white" style="background:#1B4F9B;">
                    <Save class="w-4 h-4"/>
                    {{ form.processing ? 'Saving...' : 'Save & Print' }}
                </Button>
            </div>
        </template>

        <div class="grid grid-cols-3 gap-5">
            <!-- LEFT -->
            <div class="space-y-4">
                <div class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <User class="w-3.5 h-3.5"/> Patient
                    </p>
                    <div v-if="selectedPatient">
                        <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-xl border border-blue-200">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                style="background:#1B4F9B">
                                {{ selectedPatient.full_name?.charAt(0) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-bold text-slate-800 text-sm truncate">{{ selectedPatient.full_name }}</p>
                                <p class="text-xs text-slate-500 font-mono">{{ selectedPatient.patient_code }}</p>
                                <p class="text-xs text-slate-500">{{ selectedPatient.age_sex }}</p>
                            </div>
                        </div>
                        <div v-if="visit" class="mt-2 text-xs text-slate-500 bg-slate-50 px-3 py-2 rounded-lg border">
                            Visit: <strong>{{ visit.visit_date }}</strong>
                            <span v-if="visit.employer_company" class="text-purple-600 font-semibold ml-2">· {{ visit.employer_company }}</span>
                        </div>
                    </div>
                    <div v-else class="space-y-2">
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                            <input v-model="patientSearch" @input="searchPatients"
                                placeholder="Search patient..."
                                class="w-full h-9 pl-9 pr-4 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500"/>
                        </div>
                        <div v-if="patientResults.length > 0" class="border rounded-xl overflow-hidden shadow-lg bg-white">
                            <button v-for="p in patientResults" :key="p.id" @click="selectPatient(p)"
                                class="w-full text-left px-4 py-2.5 hover:bg-slate-50 border-b last:border-b-0 text-sm">
                                <p class="font-semibold text-slate-800">{{ p.full_name }}</p>
                                <p class="text-xs text-slate-400 font-mono">{{ p.patient_code }} · {{ p.age_sex }}</p>
                            </button>
                        </div>
                        <div class="text-xs text-amber-600 flex items-center gap-1.5 bg-amber-50 p-2 rounded-lg border border-amber-200">
                            <AlertTriangle class="w-3.5 h-3.5 flex-shrink-0"/>
                            Best to write Rx from the Consult page — visit context is required.
                        </div>
                    </div>
                </div>

                <!-- Doctor selector — only shown to nurses -->
                <div v-if="is_nurse" class="bg-card rounded-xl border shadow-sm p-4">
                    <p class="text-xs font-bold text-muted-foreground uppercase tracking-widest mb-3 flex items-center gap-1.5">
                        <Stethoscope class="w-3.5 h-3.5"/> Prescribing Doctor
                    </p>
                    <select v-model="form.doctor_id" @change="selectDoctor(form.doctor_id)"
                        class="w-full h-9 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                        <option value="" disabled>— Select doctor —</option>
                        <option v-for="d in doctors" :key="d.id" :value="d.id">
                            {{ d.name }}
                        </option>
                    </select>
                    <div v-if="selectedDoctor?.prc_number" class="mt-2 text-xs text-slate-500 bg-slate-50 px-3 py-2 rounded-lg border space-y-0.5">
                        <p>PRC: <strong>{{ selectedDoctor.prc_number }}</strong></p>
                        <p v-if="selectedDoctor.ptr_number">PTR: <strong>{{ selectedDoctor.ptr_number }}</strong></p>
                        <p v-if="selectedDoctor.s2_number" class="text-amber-600">S2: <strong>{{ selectedDoctor.s2_number }}</strong></p>
                        <p>{{ selectedDoctor.specialization }}</p>
                    </div>
                    <p v-if="!form.doctor_id" class="mt-2 text-xs text-red-500 flex items-center gap-1">
                        <AlertTriangle class="w-3 h-3"/> Please select a doctor.
                    </p>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-3">
                    <div class="space-y-1.5">
                        <Label class="text-xs">Prescription Date</Label>
                        <Input v-model="form.rx_date" type="date"/>
                    </div>
                    <div class="p-3 rounded-xl border"
                        :class="form.is_controlled ? 'bg-amber-50 border-amber-300' : 'bg-slate-50 border-slate-200'">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" v-model="form.is_controlled"
                                class="mt-0.5 w-4 h-4 accent-amber-500 flex-shrink-0"/>
                            <div>
                                <p class="text-sm font-semibold" :class="form.is_controlled ? 'text-amber-800' : 'text-slate-700'">
                                    S2 / Controlled Substance
                                </p>
                                <p class="text-xs mt-0.5" :class="form.is_controlled ? 'text-amber-600' : 'text-slate-400'">
                                    Prints with S2 license number.
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="bg-card rounded-xl border shadow-sm p-4 space-y-1.5">
                    <Label class="text-xs">Notes / Instructions</Label>
                    <Textarea v-model="form.notes" :rows="4"
                        placeholder="e.g. Drink plenty of fluids, rest for 3 days..."
                        class="resize-none text-sm"/>
                </div>
            </div>

            <!-- RIGHT: Items -->
            <div class="col-span-2 space-y-4">
                <div class="bg-card rounded-xl border shadow-sm">
                    <div class="px-5 py-3.5 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Pill class="w-4 h-4 text-purple-600"/>
                            <h3 class="text-xs font-bold text-muted-foreground uppercase tracking-widest">
                                Medications ({{ form.items.length }})
                            </h3>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button type="button" variant="outline" size="sm"
                                class="text-xs h-7 gap-1.5 text-purple-700 border-purple-200"
                                @click="showPicker = !showPicker">
                                <Pill class="w-3 h-3"/> Quick Add
                            </Button>
                            <Button type="button" variant="outline" size="sm"
                                class="text-xs h-7 gap-1.5" @click="addItem">
                                <Plus class="w-3 h-3"/> Add Row
                            </Button>
                        </div>
                    </div>

                    <div v-if="showPicker" class="px-5 py-3 bg-purple-50/60 border-b">
                        <p class="text-xs font-semibold text-purple-700 mb-2">Common drugs:</p>
                        <div class="flex flex-wrap gap-1.5">
                            <button v-for="drug in commonDrugs" :key="drug.drug"
                                type="button" @click="addFromPicker(drug)"
                                class="text-xs px-2.5 py-1 bg-white border border-purple-200 rounded-lg hover:bg-purple-100 transition-colors font-semibold text-purple-800">
                                {{ drug.drug }} {{ drug.dosage }}
                            </button>
                        </div>
                    </div>

                    <div class="p-5 space-y-3">
                        <div v-for="(item, i) in form.items" :key="i"
                            class="border rounded-xl overflow-hidden"
                            :class="item.drug ? 'border-slate-200' : 'border-dashed border-slate-200'">
                            <div class="flex items-center gap-2 px-4 py-2.5 bg-slate-50 border-b">
                                <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black text-white flex-shrink-0"
                                    style="background:#8B5CF6">{{ i + 1 }}</span>
                                <Input v-model="item.drug" placeholder="Drug name (e.g. Amoxicillin)"
                                    class="flex-1 h-8 text-sm font-bold border-0 bg-transparent shadow-none focus-visible:ring-0 px-0"/>
                                <button type="button" @click="removeItem(i)" :disabled="form.items.length === 1"
                                    class="p-1 text-slate-300 hover:text-red-500 transition-colors disabled:opacity-30">
                                    <Trash2 class="w-4 h-4"/>
                                </button>
                            </div>
                            <div class="grid grid-cols-5 gap-3 px-4 py-3">
                                <div class="space-y-1">
                                    <Label class="text-xs text-slate-400">Dosage</Label>
                                    <Input v-model="item.dosage" placeholder="500mg" class="h-8 text-xs"/>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-slate-400">Form</Label>
                                    <Input v-model="item.form" placeholder="Tablet/Cap" class="h-8 text-xs"/>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-slate-400">Quantity</Label>
                                    <Input v-model="item.quantity" placeholder="21" class="h-8 text-xs"/>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-slate-400">Frequency</Label>
                                    <Input v-model="item.frequency" placeholder="3x daily" class="h-8 text-xs"/>
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs text-slate-400">Duration</Label>
                                    <Input v-model="item.duration" placeholder="7 days" class="h-8 text-xs"/>
                                </div>
                                <div class="col-span-5 space-y-1">
                                    <Label class="text-xs text-slate-400">Special Instructions</Label>
                                    <Input v-model="item.instructions" placeholder="e.g. Take with food, avoid sunlight..." class="h-8 text-xs"/>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="addItem"
                            class="w-full py-3 border-2 border-dashed border-slate-200 rounded-xl text-xs text-slate-400
                                   hover:border-blue-300 hover:text-blue-500 transition-colors flex items-center justify-center gap-2">
                            <Plus class="w-3.5 h-3.5"/> Add another medication
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between bg-card rounded-xl border shadow-sm px-5 py-4">
                    <p class="text-xs text-slate-400">Will save and open print preview automatically.</p>
                    <div class="flex items-center gap-2">
                        <Link :href="route('prescriptions.index')">
                            <Button variant="outline">Cancel</Button>
                        </Link>
                        <Button @click="submit"
                            :disabled="form.processing || !form.patient_id || (is_nurse && !form.doctor_id)"
                            class="gap-2 text-white" style="background:#1B4F9B;">
                            <Save class="w-4 h-4"/>
                            {{ form.processing ? 'Saving...' : 'Save & Print' }}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
