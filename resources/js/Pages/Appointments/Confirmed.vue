<script setup>
import { Head } from '@inertiajs/vue3'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
import { CheckCircle2, Calendar, Phone, Clock, MapPin } from 'lucide-vue-next'

const props = defineProps({
    appointment: Object,
})
</script>

<template>
    <Head title="Appointment Confirmed" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-slate-100 flex flex-col">

        <!-- Header -->
        <header class="bg-white border-b shadow-sm">
            <div class="max-w-4xl mx-auto px-4 py-3 flex items-center gap-3">
                <img :src="CLINIC_LOGO" alt="Logo" class="w-10 h-10 object-contain rounded-lg"/>
                <div>
                    <p class="font-black text-sm leading-tight" style="color:#0F2044">{{ CLINIC_INFO.shortName }}</p>
                    <p class="text-xs text-slate-400">{{ CLINIC_INFO.subtitle }}</p>
                </div>
            </div>
        </header>

        <div class="flex-1 flex items-center justify-center px-4 py-10">
            <div class="max-w-lg w-full">

                <!-- Success Icon -->
                <div class="text-center mb-6">
                    <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <CheckCircle2 class="w-10 h-10 text-emerald-500"/>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800">Appointment Submitted!</h1>
                    <p class="text-slate-500 mt-1 text-sm">
                        Your booking request has been received. We'll contact you to confirm.
                    </p>
                </div>

                <!-- Appointment Card -->
                <div class="bg-white rounded-2xl shadow-lg border overflow-hidden">
                    <!-- Number Banner -->
                    <div class="px-6 py-4 text-white text-center" style="background:#0F2044">
                        <p class="text-xs font-semibold text-white/60 uppercase tracking-widest">Reference Number</p>
                        <p class="text-2xl font-black font-mono tracking-widest mt-1">
                            {{ appointment.appointment_number }}
                        </p>
                        <p class="text-xs text-white/60 mt-1">Save this for your records</p>
                    </div>

                    <!-- Details -->
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center shrink-0">
                                <Calendar class="w-4 h-4 text-blue-600"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase">Service</p>
                                <p class="font-bold text-slate-800">{{ appointment.service_label }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center shrink-0">
                                <Clock class="w-4 h-4 text-purple-600"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase">Preferred Schedule</p>
                                <p class="font-bold text-slate-800">{{ appointment.preferred_date }}</p>
                                <p class="text-sm text-slate-500">{{ appointment.preferred_time }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center shrink-0">
                                <Phone class="w-4 h-4 text-emerald-600"/>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 font-semibold uppercase">Patient</p>
                                <p class="font-bold text-slate-800">{{ appointment.patient_name }}</p>
                            </div>
                        </div>

                        <div v-if="appointment.chief_complaint" class="p-3 bg-slate-50 rounded-xl border text-sm text-slate-600">
                            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Your Concern</p>
                            {{ appointment.chief_complaint }}
                        </div>
                    </div>

                    <!-- Notice -->
                    <div class="px-6 pb-5">
                        <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-xs text-amber-700 font-semibold leading-relaxed">
                                Status: <strong>Pending Confirmation</strong> — Our staff will call or message you to
                                confirm your exact appointment time. Please be reachable on your provided contact number.
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="px-6 pb-6 flex flex-col sm:flex-row gap-3">
                        <a href="/book-appointment"
                            class="flex-1 text-center text-sm font-bold py-2.5 rounded-xl border-2 border-slate-200 text-slate-600 hover:border-slate-300 transition-colors">
                            Book Another
                        </a>
                        <a href="/book-appointment"
                            class="flex-1 text-center text-sm font-bold py-2.5 rounded-xl text-white transition-colors hover:opacity-90"
                            style="background:#1B4F9B">
                            Back to Home
                        </a>
                    </div>
                </div>

                <!-- Clinic Contact -->
                <div class="mt-5 text-center space-y-1 text-xs text-slate-400">
                    <p class="flex items-center justify-center gap-1.5">
                        <MapPin class="w-3.5 h-3.5"/>{{ CLINIC_INFO.address }}
                    </p>
                    <p>{{ CLINIC_INFO.phone }} &bull; {{ CLINIC_INFO.phoneSmart }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
