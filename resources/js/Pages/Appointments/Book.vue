<script setup>
import { ref, computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import {
    Calendar, Clock, User, Phone, Mail, ChevronRight,
    ChevronLeft, CheckCircle2, Stethoscope, FlaskConical,
    ScanLine, TestTube, ShieldCheck, Baby, HelpCircle,
    MapPin, PhoneCall,
} from 'lucide-vue-next'

// ── Steps ─────────────────────────────────────────────────────────────────
const step = ref(1) // 1: service, 2: schedule, 3: info, 4: review

// ── Services ──────────────────────────────────────────────────────────────
const services = [
    { value: 'general_consultation', label: 'General Consultation',   icon: Stethoscope, desc: 'See a physician for any medical concern', color: 'text-purple-600', bg: 'bg-purple-50', border: 'border-purple-200' },
    { value: 'physical_exam',        label: 'Physical Examination',   icon: ShieldCheck,  desc: 'Pre-employment, annual, medical certificates', color: 'text-blue-600',   bg: 'bg-blue-50',   border: 'border-blue-200'   },
    { value: 'laboratory',           label: 'Laboratory Tests',       icon: FlaskConical, desc: 'CBC, urinalysis, blood chemistry & more',     color: 'text-amber-600',  bg: 'bg-amber-50',  border: 'border-amber-200'  },
    { value: 'xray_utz',             label: 'X-Ray / Ultrasound',     icon: ScanLine,     desc: 'Chest X-ray, bone studies, UTZ imaging',      color: 'text-orange-600', bg: 'bg-orange-50', border: 'border-orange-200' },
    { value: 'drug_test',            label: 'Drug Test',              icon: TestTube,     desc: 'DOLE-compliant workplace drug screening',     color: 'text-rose-600',   bg: 'bg-rose-50',   border: 'border-rose-200'   },
    { value: 'prenatal',             label: 'Prenatal Check-up',      icon: Baby,         desc: 'Prenatal consultation and monitoring',        color: 'text-pink-600',   bg: 'bg-pink-50',   border: 'border-pink-200'   },
    { value: 'other',                label: 'Other / Inquiry',        icon: HelpCircle,   desc: 'Ask us about other services we offer',       color: 'text-slate-600',  bg: 'bg-slate-50',  border: 'border-slate-200'  },
]

const selectedService = computed(() => services.find(s => s.value === form.service_type))

// ── Time slots ─────────────────────────────────────────────────────────────
const timeSlots = [
    { value: 'morning',   label: 'Morning',   desc: '8:00 AM – 12:00 PM', icon: '🌅' },
    { value: 'afternoon', label: 'Afternoon', desc: '12:00 PM – 5:00 PM', icon: '☀️' },
]

// ── Min date (today) ───────────────────────────────────────────────────────
const today = new Date().toISOString().split('T')[0]
// Max date: 30 days from now
const maxDate = new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0]

// ── Form ───────────────────────────────────────────────────────────────────
const form = useForm({
    service_type:    '',
    preferred_date:  '',
    preferred_time:  '',
    patient_name:    '',
    patient_email:   '',
    patient_phone:   '',
    patient_gender:  '',
    patient_dob:     '',
    chief_complaint: '',
})

// ── Navigation ─────────────────────────────────────────────────────────────
const canGoNext = computed(() => {
    if (step.value === 1) return !!form.service_type
    if (step.value === 2) return !!form.preferred_date
    if (step.value === 3) return !!form.patient_name && !!form.patient_phone
    return true
})

function next() { if (canGoNext.value && step.value < 4) step.value++ }
function back() { if (step.value > 1) step.value-- }

function submit() {
    form.post('/book-appointment', {
        onError: () => { step.value = 3 },
    })
}

// ── Date formatting ────────────────────────────────────────────────────────
function formatDate(d) {
    if (!d) return '—'
    return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    })
}
</script>

<template>
    <Head title="Book an Appointment" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100">

        <!-- ── Top Bar ───────────────────────────────── -->
        <header class="bg-white border-b shadow-sm sticky top-0 z-20">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img :src="CLINIC_LOGO" alt="Logo" class="w-10 h-10 object-contain rounded-lg"/>
                    <div>
                        <p class="font-black text-sm leading-tight" style="color:#0F2044">
                            {{ CLINIC_INFO.shortName }}
                        </p>
                        <p class="text-xs text-slate-400">{{ CLINIC_INFO.subtitle }}</p>
                    </div>
                </div>
                <a href="/login" class="text-xs font-semibold text-blue-700 hover:underline hidden sm:block">
                    Staff Login
                </a>
            </div>
        </header>

        <div class="max-w-2xl mx-auto px-4 py-8">

            <!-- ── Progress Steps ─────────────────────── -->
            <div class="flex items-center justify-center gap-0 mb-8">
                <template v-for="(label, i) in ['Service', 'Schedule', 'Your Info', 'Review']" :key="i">
                    <div class="flex items-center gap-1.5">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all"
                            :class="step > i + 1
                                ? 'bg-emerald-500 text-white'
                                : step === i + 1
                                    ? 'text-white'
                                    : 'bg-slate-200 text-slate-400'"
                            :style="step === i + 1 ? 'background:#1B4F9B' : ''">
                            <CheckCircle2 v-if="step > i + 1" class="w-4 h-4"/>
                            <span v-else>{{ i + 1 }}</span>
                        </div>
                        <span class="text-xs font-semibold hidden sm:block"
                            :class="step === i + 1 ? 'text-slate-700' : 'text-slate-400'">
                            {{ label }}
                        </span>
                    </div>
                    <div v-if="i < 3" class="w-8 sm:w-12 h-0.5 mx-1"
                        :class="step > i + 1 ? 'bg-emerald-400' : 'bg-slate-200'"/>
                </template>
            </div>

            <!-- ── Card ──────────────────────────────────── -->
            <div class="bg-white rounded-2xl shadow-lg border overflow-hidden">

                <!-- ── Step 1: Service ──────────────────── -->
                <div v-if="step === 1" class="p-6">
                    <h2 class="text-xl font-black text-slate-800 mb-1">What brings you in?</h2>
                    <p class="text-sm text-slate-400 mb-5">Select the service you'd like to book</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button v-for="svc in services" :key="svc.value"
                            type="button"
                            @click="form.service_type = svc.value"
                            :class="[
                                'flex items-start gap-3 p-4 rounded-xl border-2 text-left transition-all',
                                form.service_type === svc.value
                                    ? `${svc.bg} ${svc.border}`
                                    : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                            ]">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                                :class="form.service_type === svc.value ? svc.bg : 'bg-slate-100'">
                                <component :is="svc.icon" class="w-5 h-5"
                                    :class="form.service_type === svc.value ? svc.color : 'text-slate-400'"/>
                            </div>
                            <div>
                                <p class="font-bold text-sm text-slate-800">{{ svc.label }}</p>
                                <p class="text-xs text-slate-500 mt-0.5">{{ svc.desc }}</p>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- ── Step 2: Schedule ─────────────────── -->
                <div v-if="step === 2" class="p-6">
                    <h2 class="text-xl font-black text-slate-800 mb-1">When would you like to come in?</h2>
                    <p class="text-sm text-slate-400 mb-5">Pick your preferred date and time</p>

                    <!-- Date picker -->
                    <div class="mb-5">
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Preferred Date <span class="text-red-500">*</span>
                        </label>
                        <input v-model="form.preferred_date"
                            type="date"
                            :min="today" :max="maxDate"
                            class="w-full h-11 px-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"
                            :class="form.preferred_date ? 'border-blue-400 bg-blue-50' : 'border-slate-200'"/>
                        <p v-if="form.preferred_date" class="text-xs text-blue-700 font-semibold mt-1.5">
                            {{ formatDate(form.preferred_date) }}
                        </p>
                    </div>

                    <!-- Time slot -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Preferred Time <span class="text-slate-400 font-normal text-xs">(optional)</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button v-for="slot in timeSlots" :key="slot.value"
                                type="button"
                                @click="form.preferred_time = form.preferred_time === slot.value ? '' : slot.value"
                                :class="[
                                    'flex items-center gap-3 p-4 rounded-xl border-2 text-left transition-all',
                                    form.preferred_time === slot.value
                                        ? 'border-blue-400 bg-blue-50'
                                        : 'border-slate-200 hover:border-slate-300'
                                ]">
                                <span class="text-2xl">{{ slot.icon }}</span>
                                <div>
                                    <p class="font-bold text-sm text-slate-800">{{ slot.label }}</p>
                                    <p class="text-xs text-slate-400">{{ slot.desc }}</p>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Step 3: Patient Info ──────────────── -->
                <div v-if="step === 3" class="p-6">
                    <h2 class="text-xl font-black text-slate-800 mb-1">Your Information</h2>
                    <p class="text-sm text-slate-400 mb-5">Tell us a bit about yourself</p>

                    <div class="space-y-4">
                        <!-- Full name -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                <input v-model="form.patient_name"
                                    type="text" placeholder="Juan Dela Cruz"
                                    class="w-full h-11 pl-9 pr-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                    :class="form.errors.patient_name ? 'border-red-400' : 'border-slate-200'"/>
                            </div>
                            <p v-if="form.errors.patient_name" class="text-xs text-red-600 mt-1">{{ form.errors.patient_name }}</p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Phone Number <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                <input v-model="form.patient_phone"
                                    type="tel" placeholder="09XXXXXXXXX"
                                    class="w-full h-11 pl-9 pr-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                    :class="form.errors.patient_phone ? 'border-red-400' : 'border-slate-200'"/>
                            </div>
                            <p v-if="form.errors.patient_phone" class="text-xs text-red-600 mt-1">{{ form.errors.patient_phone }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Email Address <span class="text-slate-400 font-normal text-xs">(optional)</span>
                            </label>
                            <div class="relative">
                                <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                <input v-model="form.patient_email"
                                    type="email" placeholder="juan@email.com"
                                    class="w-full h-11 pl-9 pr-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors border-slate-200"/>
                            </div>
                        </div>

                        <!-- Gender + DOB -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Gender</label>
                                <select v-model="form.patient_gender"
                                    class="w-full h-11 px-3 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors">
                                    <option value="">Not specified</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Date of Birth</label>
                                <input v-model="form.patient_dob"
                                    type="date" :max="today"
                                    class="w-full h-11 px-3 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"/>
                            </div>
                        </div>

                        <!-- Chief complaint -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Reason / Chief Complaint <span class="text-slate-400 font-normal text-xs">(optional)</span>
                            </label>
                            <textarea v-model="form.chief_complaint"
                                rows="3" placeholder="Describe your concern or reason for visit..."
                                class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors resize-none"/>
                        </div>
                    </div>
                </div>

                <!-- ── Step 4: Review ────────────────────── -->
                <div v-if="step === 4" class="p-6">
                    <h2 class="text-xl font-black text-slate-800 mb-1">Review Your Appointment</h2>
                    <p class="text-sm text-slate-400 mb-5">Please confirm your details before submitting</p>

                    <div class="bg-slate-50 rounded-xl p-5 space-y-4 border">
                        <!-- Service -->
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
                                :class="selectedService?.bg">
                                <component :is="selectedService?.icon" class="w-4 h-4" :class="selectedService?.color"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Service</p>
                                <p class="font-bold text-slate-800">{{ selectedService?.label }}</p>
                            </div>
                        </div>

                        <!-- Date & Time -->
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-blue-100 flex items-center justify-center flex-shrink-0">
                                <Calendar class="w-4 h-4 text-blue-600"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Schedule</p>
                                <p class="font-bold text-slate-800">{{ formatDate(form.preferred_date) }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ form.preferred_time === 'morning' ? '🌅 Morning (8 AM – 12 PM)' : form.preferred_time === 'afternoon' ? '☀️ Afternoon (12 PM – 5 PM)' : 'Any available time' }}
                                </p>
                            </div>
                        </div>

                        <!-- Patient -->
                        <div class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-emerald-100 flex items-center justify-center flex-shrink-0">
                                <User class="w-4 h-4 text-emerald-600"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Patient</p>
                                <p class="font-bold text-slate-800">{{ form.patient_name }}</p>
                                <p class="text-sm text-slate-500">{{ form.patient_phone }}</p>
                                <p v-if="form.patient_email" class="text-sm text-slate-500">{{ form.patient_email }}</p>
                            </div>
                        </div>

                        <!-- Chief complaint -->
                        <div v-if="form.chief_complaint" class="flex items-start gap-3">
                            <div class="w-9 h-9 rounded-lg bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <Stethoscope class="w-4 h-4 text-purple-600"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Concern</p>
                                <p class="text-sm text-slate-700">{{ form.chief_complaint }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notice -->
                    <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <p class="text-xs text-amber-700 font-semibold">
                            Your appointment is pending confirmation. Our staff will contact you at
                            <strong>{{ form.patient_phone }}</strong> to confirm your schedule.
                        </p>
                    </div>
                </div>

                <!-- ── Navigation Footer ─────────────────── -->
                <div class="px-6 py-4 border-t bg-slate-50 flex items-center justify-between">
                    <button v-if="step > 1"
                        type="button" @click="back"
                        class="flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                        <ChevronLeft class="w-4 h-4"/> Back
                    </button>
                    <div v-else />

                    <button v-if="step < 4"
                        type="button" @click="next"
                        :disabled="!canGoNext"
                        class="flex items-center gap-1.5 text-sm font-bold text-white px-6 py-2.5 rounded-xl transition-all"
                        :class="canGoNext ? 'hover:opacity-90' : 'opacity-40 cursor-not-allowed'"
                        style="background:#1B4F9B">
                        Continue <ChevronRight class="w-4 h-4"/>
                    </button>

                    <button v-else
                        type="button" @click="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-1.5 text-sm font-bold text-white px-8 py-2.5 rounded-xl transition-all"
                        :class="form.processing ? 'opacity-60 cursor-not-allowed' : 'hover:opacity-90'"
                        style="background:#1B4F9B">
                        <CheckCircle2 class="w-4 h-4"/>
                        {{ form.processing ? 'Submitting...' : 'Confirm Appointment' }}
                    </button>
                </div>
            </div>

            <!-- ── Clinic Info Footer ──────────────────── -->
            <div class="mt-6 text-center space-y-1">
                <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><MapPin class="w-3.5 h-3.5"/> {{ CLINIC_INFO.address }}</span>
                </div>
                <div class="flex items-center justify-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1"><PhoneCall class="w-3.5 h-3.5"/> {{ CLINIC_INFO.phone }}</span>
                    <span>&bull;</span>
                    <span>{{ CLINIC_INFO.phoneSmart }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
