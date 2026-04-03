<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import {
    Calendar, Clock, User, Phone, Mail, ChevronRight,
    ChevronLeft, CheckCircle2, Stethoscope, FlaskConical,
    ScanLine, TestTube, ShieldCheck, Baby, HelpCircle,
    MapPin, PhoneCall, X, Star, Award, Users, Heart,
    ChevronDown, ArrowRight,
} from 'lucide-vue-next'

const props = defineProps({
    photos: { type: Array, default: () => [] },
})

// ── Photo carousel ─────────────────────────────────────────────────────────
const currentSlide = ref(0)
let slideTimer = null

function nextSlide() {
    if (!props.photos.length) return
    currentSlide.value = (currentSlide.value + 1) % props.photos.length
}
function prevSlide() {
    if (!props.photos.length) return
    currentSlide.value = (currentSlide.value - 1 + props.photos.length) % props.photos.length
}
function goSlide(i) { currentSlide.value = i }

onMounted(() => {
    if (props.photos.length > 1) {
        slideTimer = setInterval(nextSlide, 5000)
    }
})
onUnmounted(() => clearInterval(slideTimer))

// ── Services ───────────────────────────────────────────────────────────────
const services = [
    { value: 'general_consultation', label: 'General Consultation',  icon: Stethoscope, desc: 'See a licensed physician for any medical concern or follow-up',             color: 'text-purple-600', bg: 'bg-purple-50',  border: 'border-purple-200' },
    { value: 'physical_exam',        label: 'Physical Examination',  icon: ShieldCheck,  desc: 'Pre-employment, annual PE, and medical certificates accepted nationwide',   color: 'text-blue-600',   bg: 'bg-blue-50',    border: 'border-blue-200'   },
    { value: 'laboratory',           label: 'Laboratory Tests',      icon: FlaskConical, desc: 'CBC, urinalysis, blood chemistry, hepatitis, and complete workup panels',   color: 'text-amber-600',  bg: 'bg-amber-50',   border: 'border-amber-200'  },
    { value: 'xray_utz',             label: 'X-Ray / Ultrasound',    icon: ScanLine,     desc: 'Chest X-ray, bone radiograph, abdominal and pelvic ultrasound',            color: 'text-orange-600', bg: 'bg-orange-50',  border: 'border-orange-200' },
    { value: 'drug_test',            label: 'Drug Test',             icon: TestTube,     desc: 'DOLE-compliant 5-panel drug screening for employment and compliance',       color: 'text-rose-600',   bg: 'bg-rose-50',    border: 'border-rose-200'   },
    { value: 'prenatal',             label: 'Prenatal Check-up',     icon: Baby,         desc: 'Compassionate prenatal care and monitoring for expectant mothers',          color: 'text-pink-600',   bg: 'bg-pink-50',    border: 'border-pink-200'   },
]

// ── Trust stats ─────────────────────────────────────────────────────────────
const stats = [
    { label: 'Patients Served',   value: '10,000+', icon: Users  },
    { label: 'Years of Service',  value: '17+',     icon: Award  },
    { label: 'Licensed Staff',    value: '20+',     icon: Star   },
    { label: 'Patient Rating',    value: '4.9★',   icon: Heart  },
]

// ── Booking modal ──────────────────────────────────────────────────────────
const showBooking = ref(false)
const step        = ref(1)

function openBooking(serviceType = '') {
    form.service_type = serviceType
    step.value = serviceType ? 2 : 1
    showBooking.value = true
    document.body.style.overflow = 'hidden'
}
function closeBooking() {
    showBooking.value = false
    document.body.style.overflow = ''
}

const selectedService = computed(() => services.find(s => s.value === form.service_type))

const timeSlots = [
    { value: 'morning',   label: 'Morning',   desc: '8:00 AM – 12:00 PM', icon: '🌅' },
    { value: 'afternoon', label: 'Afternoon', desc: '12:00 PM – 5:00 PM', icon: '☀️' },
]

const today   = new Date().toISOString().split('T')[0]
const maxDate = new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0]

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

function formatDate(d) {
    if (!d) return '—'
    return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    })
}

// Smooth scroll helper
function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
}
</script>

<template>
    <Head title="Book an Appointment — St. Peter Diagnostics" />

    <div class="min-h-screen bg-white font-sans">

        <!-- ── Sticky Nav ──────────────────────────────── -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur border-b shadow-sm">
            <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img :src="CLINIC_LOGO" alt="Logo" class="w-10 h-10 object-contain rounded-xl"/>
                    <div>
                        <p class="font-black text-sm leading-tight" style="color:#0F2044">{{ CLINIC_INFO.shortName }}</p>
                        <p class="text-xs text-slate-400 hidden sm:block">{{ CLINIC_INFO.subtitle }}</p>
                    </div>
                </div>
                <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-600">
                    <button @click="scrollTo('services')" class="hover:text-blue-700 transition-colors">Services</button>
                    <button @click="scrollTo('about')"    class="hover:text-blue-700 transition-colors">About Us</button>
                    <button @click="scrollTo('contact')"  class="hover:text-blue-700 transition-colors">Contact</button>
                </nav>
                <div class="flex items-center gap-3">
                    <a href="/login" class="text-xs font-semibold text-slate-500 hover:text-slate-700 hidden sm:block">Staff Login</a>
                    <button @click="openBooking()"
                        class="flex items-center gap-1.5 text-sm font-bold text-white px-4 py-2 rounded-xl transition-opacity hover:opacity-90"
                        style="background:#1B4F9B">
                        <Calendar class="w-4 h-4"/> Book Appointment
                    </button>
                </div>
            </div>
        </header>

        <!-- ── Hero / Carousel ────────────────────────── -->
        <section class="relative overflow-hidden" style="height: 520px; background:#0F2044">

            <!-- Slides -->
            <transition-group name="slide-fade" tag="div" class="absolute inset-0">
                <div v-if="photos.length"
                    v-for="(photo, i) in photos" :key="photo.id"
                    v-show="currentSlide === i"
                    class="absolute inset-0">
                    <img :src="photo.url" :alt="photo.caption ?? 'Clinic photo'"
                        class="w-full h-full object-cover"/>
                    <div class="absolute inset-0" style="background:linear-gradient(to right, rgba(15,32,68,0.85) 30%, rgba(15,32,68,0.4))"/>
                </div>

                <!-- Fallback when no photos -->
                <div v-if="!photos.length" key="fallback" class="absolute inset-0"
                    style="background:linear-gradient(135deg, #0F2044 0%, #1B4F9B 50%, #0EA5E9 100%)">
                    <div class="absolute inset-0 opacity-10"
                        style="background-image:radial-gradient(circle at 20% 50%, white 1px, transparent 1px),radial-gradient(circle at 80% 20%, white 1px, transparent 1px);background-size:60px 60px"/>
                </div>
            </transition-group>

            <!-- Hero content -->
            <div class="relative z-10 h-full flex items-center">
                <div class="max-w-6xl mx-auto px-6 w-full">
                    <div class="max-w-xl">
                        <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur text-white text-xs font-semibold px-3 py-1.5 rounded-full mb-4">
                            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-400"/> Accepting appointments online
                        </div>
                        <h1 class="text-3xl sm:text-4xl font-black text-white leading-tight mb-3">
                            Your Health,<br/>Our Priority
                        </h1>
                        <p class="text-white/80 text-sm sm:text-base mb-6 leading-relaxed">
                            Book your appointment at {{ CLINIC_INFO.shortName }} in minutes.
                            Quality diagnostics and medical care right here in Claver, Surigao del Norte.
                        </p>
                        <div class="flex items-center gap-3 flex-wrap">
                            <button @click="openBooking()"
                                class="flex items-center gap-2 font-bold text-sm sm:text-base px-6 py-3 rounded-xl text-white transition-all hover:scale-105 shadow-lg"
                                style="background:#0EA5E9">
                                <Calendar class="w-5 h-5"/> Book an Appointment
                                <ArrowRight class="w-4 h-4"/>
                            </button>
                            <button @click="scrollTo('services')"
                                class="flex items-center gap-2 text-sm font-semibold text-white/80 hover:text-white transition-colors">
                                View Services <ChevronDown class="w-4 h-4"/>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carousel controls -->
            <div v-if="photos.length > 1" class="absolute inset-0 z-20 flex items-center justify-between px-4 pointer-events-none">
                <button @click="prevSlide"
                    class="w-10 h-10 rounded-full bg-white/20 backdrop-blur text-white flex items-center justify-center hover:bg-white/30 transition-colors pointer-events-auto">
                    <ChevronLeft class="w-5 h-5"/>
                </button>
                <button @click="nextSlide"
                    class="w-10 h-10 rounded-full bg-white/20 backdrop-blur text-white flex items-center justify-center hover:bg-white/30 transition-colors pointer-events-auto">
                    <ChevronRight class="w-5 h-5"/>
                </button>
            </div>

            <!-- Dots -->
            <div v-if="photos.length > 1" class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
                <button v-for="(_, i) in photos" :key="i" @click="goSlide(i)"
                    class="rounded-full transition-all"
                    :class="currentSlide === i ? 'w-6 h-2 bg-white' : 'w-2 h-2 bg-white/50 hover:bg-white/70'"/>
            </div>

            <!-- Caption overlay -->
            <div v-if="photos.length && photos[currentSlide]?.caption"
                class="absolute bottom-4 right-6 z-20 bg-black/40 backdrop-blur text-white text-xs px-3 py-1.5 rounded-full max-w-xs truncate">
                {{ photos[currentSlide].caption }}
            </div>
        </section>

        <!-- ── Stats Bar ──────────────────────────────── -->
        <section class="border-b" style="background:#1B4F9B">
            <div class="max-w-6xl mx-auto px-4 py-5 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div v-for="s in stats" :key="s.label" class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center shrink-0">
                        <component :is="s.icon" class="w-5 h-5 text-white"/>
                    </div>
                    <div>
                        <p class="text-lg font-black text-white leading-tight">{{ s.value }}</p>
                        <p class="text-xs text-white/60">{{ s.label }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Services ──────────────────────────────── -->
        <section id="services" class="py-16 bg-slate-50">
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-10">
                    <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color:#1B4F9B">Our Services</p>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-800">Comprehensive Health Services</h2>
                    <p class="text-slate-500 mt-2 text-sm max-w-xl mx-auto">
                        From routine diagnostics to specialized exams — everything you need under one roof.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="svc in services" :key="svc.value"
                        class="bg-white rounded-2xl border p-5 hover:shadow-md transition-shadow cursor-pointer group"
                        @click="openBooking(svc.value)">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-3"
                            :class="svc.bg">
                            <component :is="svc.icon" class="w-6 h-6" :class="svc.color"/>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-1">{{ svc.label }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ svc.desc }}</p>
                        <div class="mt-3 flex items-center gap-1 text-xs font-bold text-blue-600 group-hover:gap-2 transition-all">
                            Book Now <ArrowRight class="w-3.5 h-3.5"/>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About ─────────────────────────────────── -->
        <section id="about" class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-4">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color:#1B4F9B">About Us</p>
                        <h2 class="text-2xl sm:text-3xl font-black text-slate-800 mb-4">
                            Trusted Healthcare<br/>in Claver Since 2007
                        </h2>
                        <p class="text-slate-600 leading-relaxed mb-4">
                            {{ CLINIC_INFO.name }} has been serving the community of Claver and
                            surrounding areas for over 17 years. We are committed to providing
                            affordable, accurate, and compassionate healthcare services.
                        </p>
                        <p class="text-slate-600 leading-relaxed mb-6">
                            Our facility is equipped with modern diagnostic equipment and staffed by
                            licensed medical professionals dedicated to your health and well-being.
                        </p>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="p-4 rounded-xl border bg-slate-50">
                                <p class="text-xl font-black" style="color:#1B4F9B">Mon – Sat</p>
                                <p class="text-xs text-slate-500 mt-0.5">Open weekdays &amp; Saturdays</p>
                            </div>
                            <div class="p-4 rounded-xl border bg-slate-50">
                                <p class="text-xl font-black" style="color:#1B4F9B">8 AM – 5 PM</p>
                                <p class="text-xs text-slate-500 mt-0.5">Operating hours</p>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <template v-if="photos.length >= 2">
                            <img v-for="photo in photos.slice(0, 4)" :key="photo.id"
                                :src="photo.url" :alt="photo.caption ?? ''"
                                class="w-full h-36 object-cover rounded-2xl"
                                :class="{ 'col-span-2 h-44': photos.indexOf(photo) === 0 && photos.length === 2 }"/>
                        </template>
                        <div v-else
                            class="col-span-2 h-48 rounded-2xl flex items-center justify-center"
                            style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
                            <div class="text-center">
                                <img :src="CLINIC_LOGO" class="w-16 h-16 object-contain mx-auto mb-2 opacity-80"/>
                                <p class="text-white/60 text-xs">{{ CLINIC_INFO.shortName }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA Banner ─────────────────────────────── -->
        <section class="py-14" style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
            <div class="max-w-2xl mx-auto text-center px-4">
                <h2 class="text-2xl sm:text-3xl font-black text-white mb-3">Ready to Book Your Appointment?</h2>
                <p class="text-white/70 mb-6 text-sm">It only takes 2 minutes. No account required.</p>
                <button @click="openBooking()"
                    class="inline-flex items-center gap-2 text-base font-bold px-8 py-3.5 rounded-2xl text-white shadow-lg transition-all hover:scale-105"
                    style="background:#0EA5E9">
                    <Calendar class="w-5 h-5"/> Book an Appointment Now
                </button>
            </div>
        </section>

        <!-- ── Contact / Location ─────────────────────── -->
        <section id="contact" class="py-16 bg-white">
            <div class="max-w-6xl mx-auto px-4">
                <div class="text-center mb-8">
                    <p class="text-xs font-bold uppercase tracking-widest mb-2" style="color:#1B4F9B">Find Us</p>
                    <h2 class="text-2xl font-black text-slate-800">Contact & Location</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 max-w-3xl mx-auto">
                    <div class="text-center p-5 rounded-2xl bg-slate-50 border">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center mx-auto mb-3">
                            <MapPin class="w-6 h-6 text-blue-600"/>
                        </div>
                        <p class="font-bold text-slate-800 text-sm mb-1">Address</p>
                        <p class="text-xs text-slate-500 leading-relaxed">{{ CLINIC_INFO.addressFull }}</p>
                    </div>
                    <div class="text-center p-5 rounded-2xl bg-slate-50 border">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mx-auto mb-3">
                            <PhoneCall class="w-6 h-6 text-emerald-600"/>
                        </div>
                        <p class="font-bold text-slate-800 text-sm mb-1">Phone</p>
                        <p class="text-xs text-slate-600">{{ CLINIC_INFO.phone }}</p>
                        <p class="text-xs text-slate-600">{{ CLINIC_INFO.phoneSmart }}</p>
                    </div>
                    <div class="text-center p-5 rounded-2xl bg-slate-50 border">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-3">
                            <Mail class="w-6 h-6 text-purple-600"/>
                        </div>
                        <p class="font-bold text-slate-800 text-sm mb-1">Email</p>
                        <p class="text-xs text-slate-500 break-all">{{ CLINIC_INFO.email }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ────────────────────────────────── -->
        <footer class="border-t py-6" style="background:#0F2044">
            <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <img :src="CLINIC_LOGO" class="w-7 h-7 object-contain"/>
                    <p class="text-white/60 text-xs">© {{ new Date().getFullYear() }} {{ CLINIC_INFO.name }}</p>
                </div>
                <a href="/login" class="text-xs text-white/30 hover:text-white/60 transition-colors">Staff Login</a>
            </div>
        </footer>

        <!-- ── Booking Modal ──────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showBooking"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                    style="background:rgba(0,0,0,0.6); backdrop-filter:blur(4px)">

                    <div class="bg-white w-full sm:max-w-lg sm:rounded-2xl shadow-2xl overflow-hidden max-h-screen sm:max-h-[92vh] flex flex-col"
                        @click.stop>

                        <!-- Modal header -->
                        <div class="flex items-center justify-between px-5 py-4 border-b shrink-0"
                            style="background:#0F2044">
                            <div>
                                <p class="font-black text-white text-sm">Book an Appointment</p>
                                <p class="text-white/50 text-xs">
                                    Step {{ step }} of 4 —
                                    {{ ['Select Service', 'Choose Schedule', 'Your Information', 'Review & Confirm'][step - 1] }}
                                </p>
                            </div>
                            <button @click="closeBooking"
                                class="w-8 h-8 rounded-xl bg-white/10 text-white/60 hover:text-white flex items-center justify-center transition-colors">
                                <X class="w-4 h-4"/>
                            </button>
                        </div>

                        <!-- Progress bar -->
                        <div class="h-1 bg-white/10 shrink-0" style="background:#1B4F9B20">
                            <div class="h-full transition-all duration-500"
                                style="background:#0EA5E9"
                                :style="{ width: (step / 4 * 100) + '%' }"/>
                        </div>

                        <!-- Modal body -->
                        <div class="overflow-y-auto flex-1">

                            <!-- Step 1: Service -->
                            <div v-if="step === 1" class="p-5">
                                <p class="text-xs text-slate-400 mb-4">What would you like to book?</p>
                                <div class="grid grid-cols-1 gap-2.5">
                                    <button v-for="svc in services" :key="svc.value"
                                        type="button" @click="form.service_type = svc.value; next()"
                                        :class="[
                                            'flex items-center gap-3 p-3.5 rounded-xl border-2 text-left transition-all',
                                            form.service_type === svc.value
                                                ? `${svc.bg} ${svc.border}`
                                                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                                        ]">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                            :class="form.service_type === svc.value ? svc.bg : 'bg-slate-100'">
                                            <component :is="svc.icon" class="w-5 h-5"
                                                :class="form.service_type === svc.value ? svc.color : 'text-slate-400'"/>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-sm text-slate-800">{{ svc.label }}</p>
                                            <p class="text-xs text-slate-400 truncate">{{ svc.desc }}</p>
                                        </div>
                                        <ChevronRight class="w-4 h-4 text-slate-300 shrink-0"/>
                                    </button>
                                    <button type="button" @click="form.service_type = 'other'; next()"
                                        class="flex items-center gap-3 p-3.5 rounded-xl border-2 border-slate-200 hover:border-slate-300 hover:bg-slate-50 text-left transition-all">
                                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center shrink-0">
                                            <HelpCircle class="w-5 h-5 text-slate-400"/>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-slate-800">Other / Inquiry</p>
                                            <p class="text-xs text-slate-400">Ask us about other services</p>
                                        </div>
                                        <ChevronRight class="w-4 h-4 text-slate-300 shrink-0 ml-auto"/>
                                    </button>
                                </div>
                            </div>

                            <!-- Step 2: Schedule -->
                            <div v-if="step === 2" class="p-5 space-y-4">
                                <div v-if="selectedService" class="flex items-center gap-2 p-3 rounded-xl border"
                                    :class="selectedService.bg">
                                    <component :is="selectedService.icon" class="w-4 h-4 shrink-0" :class="selectedService.color"/>
                                    <span class="text-sm font-bold" :class="selectedService.color">{{ selectedService.label }}</span>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                        Preferred Date <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.preferred_date" type="date"
                                        :min="today" :max="maxDate"
                                        class="w-full h-11 px-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                        :class="form.preferred_date ? 'border-blue-400 bg-blue-50' : 'border-slate-200'"/>
                                    <p v-if="form.preferred_date" class="text-xs text-blue-700 font-semibold mt-1.5">
                                        {{ formatDate(form.preferred_date) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                        Preferred Time <span class="text-xs font-normal text-slate-400">(optional)</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button v-for="slot in timeSlots" :key="slot.value"
                                            type="button"
                                            @click="form.preferred_time = form.preferred_time === slot.value ? '' : slot.value"
                                            :class="[
                                                'flex items-center gap-2.5 p-3.5 rounded-xl border-2 text-left transition-all',
                                                form.preferred_time === slot.value
                                                    ? 'border-blue-400 bg-blue-50'
                                                    : 'border-slate-200 hover:border-slate-300'
                                            ]">
                                            <span class="text-xl">{{ slot.icon }}</span>
                                            <div>
                                                <p class="font-bold text-sm text-slate-800">{{ slot.label }}</p>
                                                <p class="text-xs text-slate-400">{{ slot.desc }}</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Info -->
                            <div v-if="step === 3" class="p-5 space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <User class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                        <input v-model="form.patient_name" type="text" placeholder="Juan Dela Cruz"
                                            class="w-full h-11 pl-9 pr-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                            :class="form.errors.patient_name ? 'border-red-400' : 'border-slate-200'"/>
                                    </div>
                                    <p v-if="form.errors.patient_name" class="text-xs text-red-600 mt-1">{{ form.errors.patient_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <Phone class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                        <input v-model="form.patient_phone" type="tel" placeholder="09XXXXXXXXX"
                                            class="w-full h-11 pl-9 pr-4 border-2 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"
                                            :class="form.errors.patient_phone ? 'border-red-400' : 'border-slate-200'"/>
                                    </div>
                                    <p v-if="form.errors.patient_phone" class="text-xs text-red-600 mt-1">{{ form.errors.patient_phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                        Email <span class="text-xs font-normal text-slate-400">(optional)</span>
                                    </label>
                                    <div class="relative">
                                        <Mail class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                        <input v-model="form.patient_email" type="email" placeholder="juan@email.com"
                                            class="w-full h-11 pl-9 pr-4 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 transition-colors"/>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Gender</label>
                                        <select v-model="form.patient_gender"
                                            class="w-full h-11 px-3 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500">
                                            <option value="">Not specified</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Date of Birth</label>
                                        <input v-model="form.patient_dob" type="date" :max="today"
                                            class="w-full h-11 px-3 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500"/>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                        Concern / Chief Complaint <span class="text-xs font-normal text-slate-400">(optional)</span>
                                    </label>
                                    <textarea v-model="form.chief_complaint" rows="2"
                                        placeholder="Describe your concern..."
                                        class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:outline-none focus:border-blue-500 resize-none"/>
                                </div>
                            </div>

                            <!-- Step 4: Review -->
                            <div v-if="step === 4" class="p-5">
                                <div class="space-y-3 mb-4">
                                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border">
                                        <component :is="selectedService?.icon" class="w-5 h-5 mt-0.5 shrink-0" :class="selectedService?.color"/>
                                        <div>
                                            <p class="text-xs text-slate-400 font-semibold uppercase">Service</p>
                                            <p class="font-bold text-slate-800 text-sm">{{ selectedService?.label }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border">
                                        <Calendar class="w-5 h-5 mt-0.5 shrink-0 text-blue-600"/>
                                        <div>
                                            <p class="text-xs text-slate-400 font-semibold uppercase">Schedule</p>
                                            <p class="font-bold text-slate-800 text-sm">{{ formatDate(form.preferred_date) }}</p>
                                            <p class="text-xs text-slate-500">
                                                {{ form.preferred_time === 'morning' ? '🌅 Morning (8 AM – 12 PM)' : form.preferred_time === 'afternoon' ? '☀️ Afternoon (12 PM – 5 PM)' : 'Any available time' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 border">
                                        <User class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600"/>
                                        <div>
                                            <p class="text-xs text-slate-400 font-semibold uppercase">Patient</p>
                                            <p class="font-bold text-slate-800 text-sm">{{ form.patient_name }}</p>
                                            <p class="text-xs text-slate-500">{{ form.patient_phone }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700">
                                    Your appointment is <strong>pending confirmation</strong>. Our staff will contact you at <strong>{{ form.patient_phone }}</strong>.
                                </div>
                            </div>
                        </div>

                        <!-- Modal footer -->
                        <div class="px-5 py-4 border-t bg-slate-50 flex items-center justify-between shrink-0">
                            <button v-if="step > 1" type="button" @click="back"
                                class="flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                                <ChevronLeft class="w-4 h-4"/> Back
                            </button>
                            <div v-else />

                            <button v-if="step < 4" type="button" @click="next"
                                :disabled="!canGoNext"
                                class="flex items-center gap-1.5 text-sm font-bold text-white px-5 py-2.5 rounded-xl transition-all"
                                :class="canGoNext ? 'hover:opacity-90' : 'opacity-40 cursor-not-allowed'"
                                style="background:#1B4F9B">
                                Continue <ChevronRight class="w-4 h-4"/>
                            </button>
                            <button v-else type="button" @click="submit"
                                :disabled="form.processing"
                                class="flex items-center gap-1.5 text-sm font-bold text-white px-6 py-2.5 rounded-xl transition-all"
                                :class="form.processing ? 'opacity-60 cursor-not-allowed' : 'hover:opacity-90'"
                                style="background:#1B4F9B">
                                <CheckCircle2 class="w-4 h-4"/>
                                {{ form.processing ? 'Submitting...' : 'Confirm Appointment' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: opacity 0.8s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.25s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active > div,
.modal-leave-active > div {
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from > div,
.modal-leave-to > div {
    transform: translateY(40px) scale(0.97);
}
</style>
