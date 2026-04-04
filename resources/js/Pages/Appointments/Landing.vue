<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import {
    Calendar, Clock, User, Phone, Mail, ChevronRight,
    ChevronLeft, CheckCircle2, Stethoscope, FlaskConical,
    ScanLine, TestTube, ShieldCheck, HelpCircle,
    MapPin, PhoneCall, X, Star, Award, Users, Heart,
    ChevronDown, ArrowRight, Menu,
    Building2, Zap, BadgeCheck, Wallet,
    Sunrise, Sun,
} from 'lucide-vue-next'

const props = defineProps({
    photos: { type: Array, default: () => [] },
})

// ── Photo carousel ──────────────────────────────────
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
    if (props.photos.length > 1) slideTimer = setInterval(nextSlide, 5000)
})
onUnmounted(() => clearInterval(slideTimer))

// ── Mobile menu ─────────────────────────────────────
const mobileMenuOpen = ref(false)
function toggleMenu() { mobileMenuOpen.value = !mobileMenuOpen.value }
function closeMenu()  { mobileMenuOpen.value = false }

// ── Services ────────────────────────────────────────
const services = [
    { value: 'general_consultation', label: 'General Consultation', icon: Stethoscope, desc: 'See a licensed physician for any medical concern or follow-up',           color: '#7C3AED', bg: '#F5F3FF' },
    { value: 'physical_exam',        label: 'Physical Examination', icon: ShieldCheck,  desc: 'Pre-employment, annual PE, and medical certificates accepted nationwide', color: '#1D4ED8', bg: '#EFF6FF' },
    { value: 'laboratory',           label: 'Laboratory Tests',     icon: FlaskConical, desc: 'CBC, urinalysis, blood chemistry, hepatitis, and complete workup panels', color: '#B45309', bg: '#FFFBEB' },
    { value: 'xray_utz',             label: 'X-Ray / Ultrasound',   icon: ScanLine,     desc: 'Chest X-ray, bone radiograph, abdominal and pelvic ultrasound',          color: '#C2410C', bg: '#FFF7ED' },
    { value: 'drug_test',            label: 'Drug Test',            icon: TestTube,     desc: 'DOLE-compliant 5-panel drug screening for employment and compliance',     color: '#BE123C', bg: '#FFF1F2' },
]

// ── Trust stats ─────────────────────────────────────
const stats = [
    { label: 'Patients Served',  value: '10,000+', icon: Users  },
    { label: 'Years of Service', value: '17+',     icon: Award  },
    { label: 'Licensed Staff',   value: '20+',     icon: Star   },
    { label: 'Patient Rating',   value: '4.9/5',  icon: Heart  },
]

// ── Why choose us ───────────────────────────────────
const whyUs = [
    { icon: Building2,   color: '#1D4ED8', bg: '#EFF6FF', title: 'Complete Facility',      desc: 'Lab, X-Ray, ultrasound, drug test, and doctor consultations under one roof.' },
    { icon: Zap,         color: '#B45309', bg: '#FFFBEB', title: 'Fast Turnaround',        desc: 'Most results released same day. Digital records available instantly.' },
    { icon: BadgeCheck,  color: '#059669', bg: '#F0FDF4', title: 'Licensed Professionals', desc: 'Our team consists of PRC-licensed doctors, nurses, and medical technologists.' },
    { icon: Wallet,      color: '#7C3AED', bg: '#F5F3FF', title: 'Affordable Rates',       desc: 'Transparent pricing with no hidden fees. Quality care at fair costs.' },
]

// ── Booking modal ───────────────────────────────────
const showBooking = ref(false)
const step = ref(1)

function openBooking(serviceType = '') {
    form.service_type = serviceType
    step.value = serviceType ? 2 : 1
    showBooking.value = true
    document.body.style.overflow = 'hidden'
    closeMenu()
}
function closeBooking() {
    showBooking.value = false
    document.body.style.overflow = ''
}

const selectedService = computed(() => services.find(s => s.value === form.service_type))

const timeSlots = [
    { value: 'morning',   label: 'Morning',   desc: '8:00 AM – 12:00 PM', icon: Sunrise, color: '#B45309' },
    { value: 'afternoon', label: 'Afternoon', desc: '12:00 PM – 5:00 PM', icon: Sun,     color: '#C2410C' },
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
    form.post('/book-appointment', { onError: () => { step.value = 3 } })
}

function formatDate(d) {
    if (!d) return '—'
    return new Date(d + 'T00:00:00').toLocaleDateString('en-PH', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric',
    })
}

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' })
    closeMenu()
}

const stepLabels = ['Select Service', 'Choose Schedule', 'Your Information', 'Review & Confirm']
</script>

<template>
    <Head title="Book an Appointment — St. Peter Diagnostics" />

    <div class="min-h-screen bg-white" style="font-family: 'Inter', system-ui, sans-serif;">

        <!-- ── Sticky Nav ──────────────────────────── -->
        <header class="sticky top-0 z-40 bg-white/96 backdrop-blur-md border-b border-slate-100 shadow-sm">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">

                <!-- Logo -->
                <div class="flex items-center gap-3 min-w-0">
                    <img :src="CLINIC_LOGO" alt="Logo" class="w-9 h-9 object-contain rounded-xl flex-shrink-0"/>
                    <div class="min-w-0">
                        <p class="font-black text-sm leading-tight truncate" style="color:#0F2044">
                            {{ CLINIC_INFO.shortName }}
                        </p>
                        <p class="text-[10px] text-slate-400 hidden sm:block truncate">{{ CLINIC_INFO.subtitle }}</p>
                    </div>
                </div>

                <!-- Desktop nav -->
                <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-500">
                    <button @click="scrollTo('services')" class="hover:text-blue-700 transition-colors">Services</button>
                    <button @click="scrollTo('why-us')"   class="hover:text-blue-700 transition-colors">Why Us</button>
                    <button @click="scrollTo('about')"    class="hover:text-blue-700 transition-colors">About</button>
                    <button @click="scrollTo('contact')"  class="hover:text-blue-700 transition-colors">Contact</button>
                </nav>

                <!-- Right actions -->
                <div class="flex items-center gap-2">
                    <a href="/login" class="text-xs font-semibold text-slate-400 hover:text-slate-700 hidden sm:block transition-colors">
                        Staff Login
                    </a>
                    <button @click="openBooking()"
                        class="flex items-center gap-1.5 text-sm font-bold text-white px-4 py-2 rounded-xl transition-all hover:opacity-90 hover:scale-105 shadow-sm whitespace-nowrap"
                        style="background:linear-gradient(135deg,#1B4F9B,#0EA5E9)">
                        <Calendar class="w-4 h-4 flex-shrink-0"/>
                        <span class="hidden xs:inline">Book Now</span>
                        <span class="xs:hidden">Book</span>
                    </button>
                    <!-- Mobile hamburger -->
                    <button @click="toggleMenu" class="md:hidden p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-colors">
                        <Menu class="w-5 h-5"/>
                    </button>
                </div>
            </div>

            <!-- Mobile nav drawer -->
            <Transition name="mobile-nav">
                <div v-if="mobileMenuOpen"
                    class="md:hidden border-t border-slate-100 bg-white px-4 py-3 space-y-1">
                    <button @click="scrollTo('services')"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Services
                    </button>
                    <button @click="scrollTo('why-us')"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Why Choose Us
                    </button>
                    <button @click="scrollTo('about')"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        About Us
                    </button>
                    <button @click="scrollTo('contact')"
                        class="w-full text-left px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">
                        Contact
                    </button>
                    <a href="/login"
                        class="block px-3 py-2.5 rounded-xl text-sm font-semibold text-slate-400 hover:bg-slate-50 transition-colors">
                        Staff Login
                    </a>
                </div>
            </Transition>
        </header>

        <!-- ── Hero ───────────────────────────────── -->
        <section class="relative overflow-hidden" style="min-height: min(75vh, 600px); background:#0F2044">

            <!-- Slides -->
            <transition-group name="slide-fade" tag="div" class="absolute inset-0">
                <div v-if="photos.length"
                    v-for="(photo, i) in photos" :key="photo.id"
                    v-show="currentSlide === i"
                    class="absolute inset-0">
                    <img :src="photo.url" :alt="photo.caption ?? 'Clinic photo'"
                        class="w-full h-full object-cover"/>
                    <div class="absolute inset-0"
                        style="background:linear-gradient(120deg, rgba(15,32,68,0.92) 0%, rgba(15,32,68,0.65) 50%, rgba(15,32,68,0.35) 100%)"/>
                </div>
                <!-- Fallback -->
                <div v-if="!photos.length" key="fallback" class="absolute inset-0"
                    style="background:linear-gradient(135deg,#0F2044 0%,#1B4F9B 55%,#0EA5E9 100%)">
                    <div class="absolute inset-0 opacity-[0.07]"
                        style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:40px 40px"/>
                </div>
            </transition-group>

            <!-- Decorative circle -->
            <div class="absolute -right-32 -top-32 w-96 h-96 rounded-full opacity-10"
                style="background:radial-gradient(circle,#0EA5E9,transparent 70%)"/>
            <div class="absolute -left-20 bottom-0 w-80 h-80 rounded-full opacity-5"
                style="background:radial-gradient(circle,#7C3AED,transparent 70%)"/>

            <!-- Hero content -->
            <div class="relative z-10 flex items-center h-full" style="min-height: min(75vh, 600px)">
                <div class="max-w-6xl mx-auto px-5 sm:px-6 py-16 w-full">
                    <div class="max-w-2xl">
                        <!-- Badge -->
                        <div class="inline-flex items-center gap-2 mb-5 px-3 py-1.5 rounded-full text-xs font-bold text-white"
                            style="background:rgba(14,165,233,0.25);border:1px solid rgba(14,165,233,0.4)">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"/>
                            Accepting appointments online
                        </div>

                        <!-- Headline -->
                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-[1.1] mb-4">
                            Your Health,<br/>
                            <span style="background:linear-gradient(90deg,#38BDF8,#818CF8);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text">
                                Our Priority
                            </span>
                        </h1>

                        <p class="text-white/75 text-base sm:text-lg mb-8 leading-relaxed max-w-lg">
                            Book your appointment at {{ CLINIC_INFO.shortName }} in minutes.
                            Quality diagnostics and medical care right here in Claver, Surigao del Norte.
                        </p>

                        <!-- CTA Buttons -->
                        <div class="flex flex-col xs:flex-row items-start xs:items-center gap-3">
                            <button @click="openBooking()"
                                class="flex items-center gap-2 font-bold text-base px-7 py-3.5 rounded-2xl text-white shadow-xl transition-all hover:scale-105 active:scale-100"
                                style="background:linear-gradient(135deg,#0EA5E9,#1B4F9B);box-shadow:0 8px 32px rgba(14,165,233,0.35)">
                                <Calendar class="w-5 h-5 flex-shrink-0"/>
                                Book an Appointment
                                <ArrowRight class="w-4 h-4"/>
                            </button>
                            <button @click="scrollTo('services')"
                                class="flex items-center gap-2 text-sm font-semibold text-white/70 hover:text-white transition-colors px-2">
                                View Services <ChevronDown class="w-4 h-4"/>
                            </button>
                        </div>

                        <!-- Trust pills -->
                        <div class="flex flex-wrap gap-2 mt-8">
                            <span v-for="s in stats" :key="s.label"
                                class="flex items-center gap-1.5 text-xs font-semibold text-white/80 px-3 py-1.5 rounded-full"
                                style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.15)">
                                <component :is="s.icon" class="w-3 h-3 text-sky-300"/>
                                {{ s.value }} {{ s.label }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Carousel controls -->
            <div v-if="photos.length > 1"
                class="absolute inset-x-0 top-1/2 -translate-y-1/2 z-20 flex items-center justify-between px-3 pointer-events-none">
                <button @click="prevSlide"
                    class="w-9 h-9 rounded-full bg-black/25 backdrop-blur text-white flex items-center justify-center hover:bg-black/40 transition-colors pointer-events-auto">
                    <ChevronLeft class="w-5 h-5"/>
                </button>
                <button @click="nextSlide"
                    class="w-9 h-9 rounded-full bg-black/25 backdrop-blur text-white flex items-center justify-center hover:bg-black/40 transition-colors pointer-events-auto">
                    <ChevronRight class="w-5 h-5"/>
                </button>
            </div>
            <!-- Dots -->
            <div v-if="photos.length > 1"
                class="absolute bottom-5 left-1/2 -translate-x-1/2 z-20 flex items-center gap-2">
                <button v-for="(_, i) in photos" :key="i" @click="goSlide(i)"
                    class="rounded-full transition-all duration-300"
                    :class="currentSlide === i ? 'w-6 h-2 bg-white' : 'w-2 h-2 bg-white/40 hover:bg-white/60'"/>
            </div>
        </section>

        <!-- ── Services ───────────────────────────── -->
        <section id="services" class="py-16 sm:py-20" style="background:#F8FAFC">
            <div class="max-w-6xl mx-auto px-5 sm:px-6">
                <div class="text-center mb-12">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] mb-3" style="color:#1B4F9B">Our Services</p>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-800 leading-tight">
                        Comprehensive Health Services
                    </h2>
                    <p class="text-slate-500 mt-3 text-base max-w-xl mx-auto leading-relaxed">
                        From routine diagnostics to specialized exams — everything you need under one roof.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <button v-for="svc in services" :key="svc.value"
                        @click="openBooking(svc.value)"
                        class="group text-left bg-white rounded-2xl border border-slate-100 p-5 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200 active:scale-[0.98]">
                        <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform group-hover:scale-110"
                            :style="{ background: svc.bg }">
                            <component :is="svc.icon" class="w-6 h-6" :style="{ color: svc.color }"/>
                        </div>
                        <h3 class="font-bold text-base text-slate-800 mb-1.5">{{ svc.label }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed mb-4">{{ svc.desc }}</p>
                        <div class="flex items-center gap-1.5 text-xs font-bold transition-all group-hover:gap-2.5"
                            :style="{ color: svc.color }">
                            Book Now <ArrowRight class="w-3.5 h-3.5"/>
                        </div>
                    </button>
                </div>
            </div>
        </section>

        <!-- ── Why Choose Us ──────────────────────── -->
        <section id="why-us" class="py-16 sm:py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 sm:px-6">
                <div class="text-center mb-12">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] mb-3" style="color:#1B4F9B">Why Choose Us</p>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-800">
                        Quality Care You Can Trust
                    </h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="w in whyUs" :key="w.title"
                        class="text-center p-6 rounded-2xl border border-slate-100 bg-slate-50 hover:bg-white hover:shadow-md transition-all duration-200">
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4"
                            :style="{ background: w.bg }">
                            <component :is="w.icon" class="w-7 h-7" :style="{ color: w.color }"/>
                        </div>
                        <h3 class="font-bold text-slate-800 mb-2">{{ w.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ w.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── About ──────────────────────────────── -->
        <section id="about" class="py-16 sm:py-20" style="background:#F8FAFC">
            <div class="max-w-6xl mx-auto px-5 sm:px-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] mb-3" style="color:#1B4F9B">About Us</p>
                        <h2 class="text-3xl sm:text-4xl font-black text-slate-800 mb-4 leading-tight">
                            Trusted Healthcare<br/>in Claver Since 2007
                        </h2>
                        <p class="text-slate-600 leading-relaxed mb-4 text-base">
                            {{ CLINIC_INFO.name }} has been serving the community of Claver and surrounding areas for over 17 years. We are committed to providing affordable, accurate, and compassionate healthcare services.
                        </p>
                        <p class="text-slate-600 leading-relaxed mb-6 text-base">
                            Our facility is equipped with modern diagnostic equipment and staffed by licensed medical professionals dedicated to your health and well-being.
                        </p>
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <div class="p-4 rounded-2xl border bg-white shadow-sm text-center">
                                <p class="text-xl font-black mb-1" style="color:#1B4F9B">Mon – Sat</p>
                                <p class="text-xs text-slate-400">Weekdays &amp; Saturdays</p>
                            </div>
                            <div class="p-4 rounded-2xl border bg-white shadow-sm text-center">
                                <p class="text-xl font-black mb-1" style="color:#1B4F9B">8 AM – 5 PM</p>
                                <p class="text-xs text-slate-400">Operating hours</p>
                            </div>
                        </div>
                        <button @click="openBooking()"
                            class="flex items-center gap-2 font-bold px-6 py-3 rounded-2xl text-white shadow-md transition-all hover:scale-105 hover:opacity-95"
                            style="background:linear-gradient(135deg,#1B4F9B,#0EA5E9)">
                            <Calendar class="w-4 h-4"/> Book an Appointment
                        </button>
                    </div>

                    <!-- Photo grid -->
                    <div class="grid grid-cols-2 gap-3">
                        <template v-if="photos.length >= 2">
                            <img v-for="(photo, i) in photos.slice(0,4)" :key="photo.id"
                                :src="photo.url" :alt="photo.caption ?? ''"
                                class="w-full object-cover rounded-2xl"
                                :class="i === 0 ? 'col-span-2 h-48 sm:h-56' : 'h-32 sm:h-40'"/>
                        </template>
                        <div v-else class="col-span-2 h-56 rounded-2xl flex items-center justify-center"
                            style="background:linear-gradient(135deg,#0F2044,#1B4F9B)">
                            <div class="text-center">
                                <img :src="CLINIC_LOGO" class="w-16 h-16 object-contain mx-auto mb-3 opacity-80"/>
                                <p class="text-white/60 text-sm font-semibold">{{ CLINIC_INFO.shortName }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA Banner ─────────────────────────── -->
        <section class="py-16 sm:py-20 relative overflow-hidden"
            style="background:linear-gradient(135deg,#0F2044 0%,#1B4F9B 60%,#0EA5E9 100%)">
            <div class="absolute inset-0 opacity-[0.06]"
                style="background-image:radial-gradient(circle,white 1px,transparent 1px);background-size:36px 36px"/>
            <div class="relative z-10 max-w-2xl mx-auto text-center px-5">
                <h2 class="text-3xl sm:text-4xl font-black text-white mb-3 leading-tight">
                    Ready to Book Your Appointment?
                </h2>
                <p class="text-white/60 mb-8 text-base">It only takes 2 minutes. No account required.</p>
                <button @click="openBooking()"
                    class="inline-flex items-center gap-2.5 text-base font-bold px-8 py-4 rounded-2xl text-white shadow-xl transition-all hover:scale-105 active:scale-100"
                    style="background:rgba(255,255,255,0.15);border:2px solid rgba(255,255,255,0.3);backdrop-filter:blur(10px)">
                    <Calendar class="w-5 h-5"/> Book an Appointment Now
                    <ArrowRight class="w-4 h-4"/>
                </button>
            </div>
        </section>

        <!-- ── Contact ────────────────────────────── -->
        <section id="contact" class="py-16 sm:py-20 bg-white">
            <div class="max-w-6xl mx-auto px-5 sm:px-6">
                <div class="text-center mb-10">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] mb-3" style="color:#1B4F9B">Find Us</p>
                    <h2 class="text-3xl font-black text-slate-800">Contact &amp; Location</h2>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 max-w-3xl mx-auto">
                    <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center mx-auto mb-4">
                            <MapPin class="w-6 h-6 text-blue-600"/>
                        </div>
                        <p class="font-bold text-slate-800 mb-2">Address</p>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ CLINIC_INFO.addressFull }}</p>
                    </div>
                    <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center mx-auto mb-4">
                            <PhoneCall class="w-6 h-6 text-emerald-600"/>
                        </div>
                        <p class="font-bold text-slate-800 mb-2">Phone</p>
                        <p class="text-sm text-slate-600">{{ CLINIC_INFO.phone }}</p>
                        <p class="text-sm text-slate-600 mt-0.5">{{ CLINIC_INFO.phoneSmart }}</p>
                    </div>
                    <div class="text-center p-6 rounded-2xl bg-slate-50 border border-slate-100 hover:shadow-md transition-shadow">
                        <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-4">
                            <Mail class="w-6 h-6 text-purple-600"/>
                        </div>
                        <p class="font-bold text-slate-800 mb-2">Email</p>
                        <p class="text-sm text-slate-500 break-all">{{ CLINIC_INFO.email }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Footer ─────────────────────────────── -->
        <footer style="background:#0F2044">
            <div class="max-w-6xl mx-auto px-5 sm:px-6 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img :src="CLINIC_LOGO" class="w-8 h-8 object-contain rounded-lg opacity-80"/>
                    <p class="text-white/50 text-sm">© {{ new Date().getFullYear() }} {{ CLINIC_INFO.name }}</p>
                </div>
                <div class="flex items-center gap-6 text-xs text-white/30">
                    <button @click="scrollTo('services')" class="hover:text-white/60 transition-colors">Services</button>
                    <button @click="scrollTo('about')"    class="hover:text-white/60 transition-colors">About</button>
                    <button @click="scrollTo('contact')"  class="hover:text-white/60 transition-colors">Contact</button>
                    <a href="/login" class="hover:text-white/60 transition-colors">Staff Login</a>
                </div>
            </div>
        </footer>

        <!-- ── Floating Book Button (mobile) ──────── -->
        <div class="fixed bottom-5 left-1/2 -translate-x-1/2 z-30 md:hidden">
            <button @click="openBooking()"
                class="flex items-center gap-2 font-bold text-sm text-white px-6 py-3.5 rounded-full shadow-2xl transition-all hover:scale-105 active:scale-95"
                style="background:linear-gradient(135deg,#1B4F9B,#0EA5E9);box-shadow:0 8px 24px rgba(14,165,233,0.45)">
                <Calendar class="w-4 h-4"/> Book Appointment
            </button>
        </div>

        <!-- ── Booking Modal ──────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showBooking"
                    class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                    style="background:rgba(0,0,0,0.65);backdrop-filter:blur(6px)"
                    @click.self="closeBooking">

                    <div class="bg-white w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col"
                        style="max-height:92dvh"
                        @click.stop>

                        <!-- Modal header -->
                        <div class="px-5 pt-5 pb-4 border-b shrink-0" style="background:#0F2044">
                            <!-- Mobile drag handle -->
                            <div class="w-10 h-1 rounded-full bg-white/20 mx-auto mb-4 sm:hidden"/>
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-black text-white text-base leading-tight">Book an Appointment</p>
                                    <p class="text-white/50 text-xs mt-0.5">
                                        Step {{ step }} of 4 — {{ stepLabels[step - 1] }}
                                    </p>
                                </div>
                                <button @click="closeBooking"
                                    class="w-8 h-8 rounded-xl bg-white/10 hover:bg-white/20 text-white/60 hover:text-white flex items-center justify-center transition-colors flex-shrink-0 mt-0.5">
                                    <X class="w-4 h-4"/>
                                </button>
                            </div>
                        </div>

                        <!-- Progress bar -->
                        <div class="h-1 bg-slate-100 shrink-0">
                            <div class="h-full transition-all duration-500 rounded-r-full"
                                style="background:linear-gradient(90deg,#1B4F9B,#0EA5E9)"
                                :style="{ width: (step / 4 * 100) + '%' }"/>
                        </div>

                        <!-- Step indicators -->
                        <div class="flex items-center justify-between px-5 py-3 border-b bg-slate-50 shrink-0">
                            <div v-for="n in 4" :key="n"
                                class="flex items-center gap-1.5 text-xs font-semibold transition-colors"
                                :class="n === step ? 'text-blue-600' : n < step ? 'text-emerald-500' : 'text-slate-300'">
                                <div class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-black flex-shrink-0"
                                    :class="n < step ? 'bg-emerald-100 text-emerald-600' : n === step ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-300'">
                                    <CheckCircle2 v-if="n < step" class="w-3 h-3"/>
                                    <span v-else>{{ n }}</span>
                                </div>
                                <span class="hidden sm:inline">{{ stepLabels[n-1].split(' ')[0] }}</span>
                            </div>
                        </div>

                        <!-- Modal body -->
                        <div class="overflow-y-auto flex-1 overscroll-contain">

                            <!-- Step 1: Service -->
                            <div v-if="step === 1" class="p-5">
                                <p class="text-sm font-semibold text-slate-500 mb-4">What would you like to book?</p>
                                <div class="space-y-2">
                                    <button v-for="svc in services" :key="svc.value"
                                        type="button" @click="form.service_type = svc.value; next()"
                                        class="w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 text-left transition-all active:scale-[0.98]"
                                        :style="form.service_type === svc.value
                                            ? { borderColor: svc.color, background: svc.bg }
                                            : { borderColor: '#E2E8F0', background: 'white' }">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                            :style="{ background: svc.bg }">
                                            <component :is="svc.icon" class="w-5 h-5" :style="{ color: svc.color }"/>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-sm text-slate-800">{{ svc.label }}</p>
                                            <p class="text-xs text-slate-400 truncate">{{ svc.desc }}</p>
                                        </div>
                                        <ChevronRight class="w-4 h-4 text-slate-300 shrink-0"/>
                                    </button>
                                    <button type="button" @click="form.service_type = 'other'; next()"
                                        class="w-full flex items-center gap-3 p-3.5 rounded-2xl border-2 border-slate-200 hover:border-slate-300 bg-white text-left transition-all active:scale-[0.98]">
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
                            <div v-if="step === 2" class="p-5 space-y-5">
                                <div v-if="selectedService"
                                    class="flex items-center gap-2.5 p-3 rounded-xl border-2"
                                    :style="{ borderColor: selectedService.color + '40', background: selectedService.bg }">
                                    <component :is="selectedService.icon" class="w-4 h-4 shrink-0"
                                        :style="{ color: selectedService.color }"/>
                                    <span class="text-sm font-bold" :style="{ color: selectedService.color }">
                                        {{ selectedService.label }}
                                    </span>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Preferred Date <span class="text-red-500">*</span>
                                    </label>
                                    <input v-model="form.preferred_date" type="date"
                                        :min="today" :max="maxDate"
                                        class="w-full h-12 px-4 border-2 rounded-2xl text-sm font-semibold focus:outline-none transition-colors"
                                        :class="form.preferred_date ? 'border-blue-400 bg-blue-50 text-blue-700' : 'border-slate-200 text-slate-700'"/>
                                    <p v-if="form.preferred_date" class="text-xs text-blue-600 font-semibold mt-2">
                                        📅 {{ formatDate(form.preferred_date) }}
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Preferred Time
                                        <span class="text-xs font-normal text-slate-400 ml-1">(optional)</span>
                                    </label>
                                    <div class="grid grid-cols-2 gap-3">
                                        <button v-for="slot in timeSlots" :key="slot.value"
                                            type="button"
                                            @click="form.preferred_time = form.preferred_time === slot.value ? '' : slot.value"
                                            class="flex items-center gap-2.5 p-3.5 rounded-2xl border-2 text-left transition-all active:scale-[0.98]"
                                            :class="form.preferred_time === slot.value
                                                ? 'border-blue-400 bg-blue-50'
                                                : 'border-slate-200 hover:border-slate-300 bg-white'">
                                            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                                                :style="{ background: slot.color + '15' }">
                                                <component :is="slot.icon" class="w-5 h-5" :style="{ color: slot.color }"/>
                                            </div>
                                            <div>
                                                <p class="font-bold text-sm text-slate-800">{{ slot.label }}</p>
                                                <p class="text-xs text-slate-400">{{ slot.desc }}</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Patient Info -->
                            <div v-if="step === 3" class="p-5 space-y-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Full Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <User class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                        <input v-model="form.patient_name" type="text" placeholder="Juan Dela Cruz"
                                            class="w-full h-12 pl-10 pr-4 border-2 rounded-2xl text-sm font-medium focus:outline-none focus:border-blue-400 transition-colors"
                                            :class="form.errors.patient_name ? 'border-red-400 bg-red-50' : 'border-slate-200'"/>
                                    </div>
                                    <p v-if="form.errors.patient_name" class="text-xs text-red-600 mt-1.5 font-semibold">{{ form.errors.patient_name }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <Phone class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                        <input v-model="form.patient_phone" type="tel" placeholder="09XXXXXXXXX"
                                            class="w-full h-12 pl-10 pr-4 border-2 rounded-2xl text-sm font-medium focus:outline-none focus:border-blue-400 transition-colors"
                                            :class="form.errors.patient_phone ? 'border-red-400 bg-red-50' : 'border-slate-200'"/>
                                    </div>
                                    <p v-if="form.errors.patient_phone" class="text-xs text-red-600 mt-1.5 font-semibold">{{ form.errors.patient_phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Email
                                        <span class="text-xs font-normal text-slate-400 ml-1">(optional)</span>
                                    </label>
                                    <div class="relative">
                                        <Mail class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                                        <input v-model="form.patient_email" type="email" placeholder="juan@email.com"
                                            class="w-full h-12 pl-10 pr-4 border-2 border-slate-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-blue-400 transition-colors"/>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Gender</label>
                                        <select v-model="form.patient_gender"
                                            class="w-full h-12 px-3 border-2 border-slate-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-blue-400 bg-white">
                                            <option value="">Not specified</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Date of Birth</label>
                                        <input v-model="form.patient_dob" type="date" :max="today"
                                            class="w-full h-12 px-3 border-2 border-slate-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-blue-400"/>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Concern / Chief Complaint
                                        <span class="text-xs font-normal text-slate-400 ml-1">(optional)</span>
                                    </label>
                                    <textarea v-model="form.chief_complaint" rows="3"
                                        placeholder="Describe your concern briefly..."
                                        class="w-full px-4 py-3 border-2 border-slate-200 rounded-2xl text-sm font-medium focus:outline-none focus:border-blue-400 resize-none leading-relaxed"/>
                                </div>
                            </div>

                            <!-- Step 4: Review -->
                            <div v-if="step === 4" class="p-5 space-y-3">
                                <div class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <component :is="selectedService?.icon" class="w-5 h-5 mt-0.5 shrink-0"
                                        :style="{ color: selectedService?.color }"/>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Service</p>
                                        <p class="font-bold text-slate-800 mt-0.5">{{ selectedService?.label }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <Calendar class="w-5 h-5 mt-0.5 shrink-0 text-blue-600"/>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Schedule</p>
                                        <p class="font-bold text-slate-800 mt-0.5">{{ formatDate(form.preferred_date) }}</p>
                                        <p class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                            <component
                                                :is="form.preferred_time === 'morning' ? Sunrise : form.preferred_time === 'afternoon' ? Sun : Clock"
                                                class="w-3 h-3 flex-shrink-0"/>
                                            {{ form.preferred_time === 'morning' ? 'Morning (8 AM – 12 PM)' : form.preferred_time === 'afternoon' ? 'Afternoon (12 PM – 5 PM)' : 'Any available time' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                                    <User class="w-5 h-5 mt-0.5 shrink-0 text-emerald-600"/>
                                    <div>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Patient</p>
                                        <p class="font-bold text-slate-800 mt-0.5">{{ form.patient_name }}</p>
                                        <p class="text-xs text-slate-500">{{ form.patient_phone }}</p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200">
                                    <Clock class="w-4 h-4 text-amber-600 mt-0.5 shrink-0"/>
                                    <p class="text-sm text-amber-700 leading-relaxed">
                                        Your appointment is <strong>pending confirmation</strong>. Our staff will contact you at <strong>{{ form.patient_phone }}</strong> to confirm your schedule.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Modal footer -->
                        <div class="px-5 py-4 border-t bg-white flex items-center justify-between shrink-0 gap-3">
                            <button v-if="step > 1" type="button" @click="back"
                                class="flex items-center gap-1.5 text-sm font-bold text-slate-500 hover:text-slate-800 px-4 py-2.5 rounded-xl hover:bg-slate-100 transition-all">
                                <ChevronLeft class="w-4 h-4"/> Back
                            </button>
                            <div v-else class="flex-shrink-0"/>

                            <button v-if="step < 4" type="button" @click="next"
                                :disabled="!canGoNext"
                                class="flex items-center gap-2 text-sm font-bold text-white px-6 py-2.5 rounded-xl transition-all ml-auto"
                                :class="canGoNext ? 'hover:opacity-90 hover:scale-105' : 'opacity-40 cursor-not-allowed'"
                                style="background:linear-gradient(135deg,#1B4F9B,#0EA5E9)">
                                Continue <ChevronRight class="w-4 h-4"/>
                            </button>
                            <button v-else type="button" @click="submit"
                                :disabled="form.processing"
                                class="flex items-center gap-2 text-sm font-bold text-white px-6 py-2.5 rounded-xl transition-all ml-auto"
                                :class="form.processing ? 'opacity-60 cursor-not-allowed' : 'hover:opacity-90 hover:scale-105'"
                                style="background:linear-gradient(135deg,#059669,#10B981)">
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
.slide-fade-leave-active { transition: opacity 1s ease; }
.slide-fade-enter-from,
.slide-fade-leave-to     { opacity: 0; }

.modal-enter-active,
.modal-leave-active { transition: opacity 0.25s ease; }
.modal-enter-from,
.modal-leave-to     { opacity: 0; }
.modal-enter-active > div,
.modal-leave-active > div { transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.modal-enter-from > div,
.modal-leave-to > div     { transform: translateY(50px) scale(0.97); }

.mobile-nav-enter-active,
.mobile-nav-leave-active { transition: all 0.2s ease; }
.mobile-nav-enter-from,
.mobile-nav-leave-to     { opacity: 0; transform: translateY(-8px); }

@media (max-width: 480px) {
    .xs\:flex-row  { flex-direction: row; }
    .xs\:items-center { align-items: center; }
    .xs\:inline { display: inline; }
    .xs\:hidden { display: none; }
}
</style>
