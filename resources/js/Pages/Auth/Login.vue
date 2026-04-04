<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import ProgressBar from '@/Components/Layout/ProgressBar.vue'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'

const props = defineProps({
    photos: { type: Array, default: () => [] },
})

// ── Slideshow ──────────────────────────────────────────
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

// ── Login form ─────────────────────────────────────────
const showPassword = ref(false)
const form = useForm({ email: '', password: '', remember: false })
const submit = () => form.post(route('login.post'), { onFinish: () => form.reset('password') })
</script>

<template>
    <div class="min-h-screen flex bg-white">
        <ProgressBar />

        <!-- ═══════════════════════════════════════════════
             LEFT — full-height photo slideshow
        ════════════════════════════════════════════════ -->
        <div class="hidden lg:flex lg:w-[58%] relative overflow-hidden flex-col"
            style="background:#0F2044;">

            <!-- ── Slides ── -->
            <transition-group name="slide-fade" tag="div" class="absolute inset-0">
                <template v-if="photos.length">
                    <div v-for="(photo, i) in photos" :key="photo.id"
                        v-show="currentSlide === i"
                        class="absolute inset-0">
                        <img :src="photo.url" :alt="photo.caption ?? 'Clinic photo'"
                            class="w-full h-full object-cover" />
                        <!-- Gradient: strong on left for text legibility, fades on right -->
                        <div class="absolute inset-0"
                            style="background: linear-gradient(110deg,
                                rgba(10,22,50,0.95) 0%,
                                rgba(15,32,68,0.80) 35%,
                                rgba(15,32,68,0.45) 65%,
                                rgba(15,32,68,0.15) 100%)" />
                    </div>
                </template>
                <!-- Fallback -->
                <div v-if="!photos.length" key="fallback" class="absolute inset-0"
                    style="background: linear-gradient(135deg, #0a1632 0%, #0F2044 40%, #1B4F9B 75%, #1d6aba 100%)">
                    <div class="absolute inset-0"
                        style="background-image: radial-gradient(ellipse at 80% 50%, rgba(37,99,235,0.18) 0%, transparent 70%)" />
                    <div class="absolute inset-0 opacity-[0.04]"
                        style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;" />
                </div>
            </transition-group>

            <!-- ── Subtle top vignette ── -->
            <div class="absolute inset-x-0 top-0 h-40 pointer-events-none z-10"
                style="background: linear-gradient(to bottom, rgba(10,22,50,0.6), transparent)" />
            <!-- ── Bottom vignette ── -->
            <div class="absolute inset-x-0 bottom-0 h-48 pointer-events-none z-10"
                style="background: linear-gradient(to top, rgba(10,22,50,0.85), transparent)" />

            <!-- ── Overlay content ── -->
            <div class="relative z-20 flex flex-col justify-between h-full min-h-screen px-12 py-10">

                <!-- Top bar -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-white/10 backdrop-blur-sm border border-white/20 flex items-center justify-center">
                            <img :src="CLINIC_LOGO" class="w-6 h-6 object-contain" />
                        </div>
                        <span class="text-white/90 font-bold text-sm tracking-wide">ClinicHub</span>
                    </div>
                    <!-- Live indicator -->
                    <div class="flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/10 border border-white/15 backdrop-blur-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse" />
                        <span class="text-white/70 text-[11px] font-medium">Staff Portal</span>
                    </div>
                </div>

                <!-- Center headline -->
                <div class="max-w-lg space-y-6">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-[11px] font-semibold text-sky-300 border border-sky-400/30"
                            style="background: rgba(14,165,233,0.12)">
                            <span class="w-1.5 h-1.5 rounded-full bg-sky-400" />
                            {{ CLINIC_INFO.subtitle }}
                        </div>
                        <h2 class="text-[2.6rem] font-black text-white leading-[1.08] tracking-tight">
                            {{ CLINIC_INFO.name }}
                        </h2>
                        <p class="text-white/55 text-sm leading-relaxed max-w-sm">
                            Complete clinic management — patients, lab, diagnostics, drug testing, prescriptions & billing in one place.
                        </p>
                    </div>

                    <!-- Feature chips -->
                    <div class="flex flex-wrap gap-2">
                        <span v-for="f in ['Patient Records','Laboratory','X-Ray & Imaging','Drug Testing','Prescriptions','Billing']"
                            :key="f"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-semibold text-white/75 border border-white/10"
                            style="background: rgba(255,255,255,0.07); backdrop-filter: blur(4px)">
                            <span class="w-1 h-1 rounded-full bg-sky-400/80" />
                            {{ f }}
                        </span>
                    </div>
                </div>

                <!-- Bottom — address + carousel controls -->
                <div class="space-y-4">
                    <!-- Address -->
                    <div class="space-y-0.5">
                        <p class="text-white/60 text-[11px] font-medium">{{ CLINIC_INFO.addressFull }}</p>
                        <p class="text-white/40 text-[11px]">{{ CLINIC_INFO.phone }} &nbsp;·&nbsp; {{ CLINIC_INFO.phoneSmart }}</p>
                    </div>

                    <!-- Dots + arrows row -->
                    <div v-if="photos.length > 1" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button v-for="(_, i) in photos" :key="i" @click="goSlide(i)"
                                class="rounded-full transition-all duration-300"
                                :class="currentSlide === i
                                    ? 'w-7 h-2 bg-white'
                                    : 'w-2 h-2 bg-white/30 hover:bg-white/55'" />
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="prevSlide"
                                class="w-8 h-8 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/20 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <polyline points="15 18 9 12 15 6"/>
                                </svg>
                            </button>
                            <button @click="nextSlide"
                                class="w-8 h-8 rounded-full border border-white/20 bg-white/10 backdrop-blur-sm text-white flex items-center justify-center hover:bg-white/20 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <polyline points="9 18 15 12 9 6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════
             RIGHT — login form
        ════════════════════════════════════════════════ -->
        <div class="flex-1 flex flex-col justify-center items-center relative bg-white">

            <!-- Subtle background texture -->
            <div class="absolute inset-0 pointer-events-none"
                style="background: radial-gradient(ellipse at 60% 0%, rgba(219,234,254,0.5) 0%, transparent 60%),
                                   radial-gradient(ellipse at 0% 100%, rgba(224,231,255,0.3) 0%, transparent 50%)" />

            <div class="relative w-full max-w-[380px] px-8 py-10 lg:py-0">

                <!-- Mobile logo -->
                <div class="lg:hidden text-center mb-10">
                    <img :src="CLINIC_LOGO" class="w-16 h-16 object-contain mx-auto mb-4 rounded-2xl shadow-lg" />
                    <h1 class="text-xl font-black text-slate-800 tracking-tight">{{ CLINIC_INFO.name }}</h1>
                    <p class="text-slate-400 text-sm mt-1">{{ CLINIC_INFO.subtitle }}</p>
                </div>

                <!-- Heading -->
                <div class="mb-8">
                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-widest mb-2">Staff Access</p>
                    <h1 class="text-[1.75rem] font-black text-slate-900 tracking-tight leading-tight">Welcome back</h1>
                    <p class="text-slate-400 text-sm mt-1.5">Sign in to your account to continue</p>
                </div>

                <!-- Error alert -->
                <transition name="fade-down">
                    <div v-if="form.errors.email"
                        class="mb-6 flex items-center gap-3 px-4 py-3 rounded-2xl text-sm font-medium text-red-700 border"
                        style="background: #fff5f5; border-color: #fecaca;">
                        <div class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                            <svg class="w-3 h-3 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        {{ form.errors.email }}
                    </div>
                </transition>

                <form @submit.prevent="submit" class="space-y-4">

                    <!-- Email -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Email Address</label>
                        <div class="relative group">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect width="20" height="16" x="2" y="4" rx="2"/>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                            </svg>
                            <input v-model="form.email"
                                type="email" autocomplete="email" required
                                placeholder="your@email.com"
                                class="w-full pl-10 pr-4 py-3 rounded-xl text-sm text-slate-800 placeholder-slate-300 border transition-all outline-none"
                                :class="form.errors.email
                                    ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                                    : 'border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-100'" />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Password</label>
                        <div class="relative group">
                            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 group-focus-within:text-blue-500 transition-colors"
                                fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <rect width="18" height="11" x="3" y="11" rx="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                            <input v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password" required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-11 py-3 rounded-xl text-sm text-slate-800 placeholder-slate-300 border transition-all outline-none"
                                :class="form.errors.password
                                    ? 'border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                                    : 'border-slate-200 bg-slate-50 focus:bg-white focus:border-blue-400 focus:ring-2 focus:ring-blue-100'" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 w-6 h-6 flex items-center justify-center text-slate-300 hover:text-slate-500 transition-colors rounded-md hover:bg-slate-100">
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                    <line x1="2" x2="22" y1="2" y2="22"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember + divider -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <div class="relative">
                                <input v-model="form.remember" id="remember" type="checkbox"
                                    class="sr-only peer" />
                                <div class="w-4 h-4 rounded border-2 border-slate-300 bg-white peer-checked:bg-blue-600 peer-checked:border-blue-600 transition-all flex items-center justify-center">
                                    <svg v-if="form.remember" class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </div>
                            </div>
                            <span class="text-xs text-slate-500 group-hover:text-slate-700 transition-colors select-none">Keep me signed in</span>
                        </label>
                    </div>

                    <!-- Submit button -->
                    <div class="pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="w-full relative flex items-center justify-center gap-2.5 py-3.5 px-6 rounded-xl text-sm font-bold text-white overflow-hidden transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed group"
                            style="background: linear-gradient(135deg, #1e3a8a 0%, #1B4F9B 40%, #2563eb 100%); box-shadow: 0 4px 20px rgba(37,99,235,0.40), 0 1px 3px rgba(37,99,235,0.3);">
                            <!-- Shimmer effect -->
                            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                                style="background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 40%, #3b82f6 100%)" />
                            <span class="relative flex items-center gap-2.5">
                                <svg v-if="form.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
                                    <polyline points="10 17 15 12 10 7"/>
                                    <line x1="15" x2="3" y1="12" y2="12"/>
                                </svg>
                                {{ form.processing ? 'Signing in…' : 'Sign In' }}
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Footer -->
                <div class="mt-10 pt-6 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <img :src="CLINIC_LOGO" class="w-5 h-5 object-contain opacity-40" />
                        <span class="text-[11px] text-slate-400">ClinicHub</span>
                    </div>
                    <span class="text-[11px] text-slate-300">© {{ new Date().getFullYear() }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active { transition: opacity 1.2s ease; }
.slide-fade-enter-from,
.slide-fade-leave-to     { opacity: 0; }

.fade-down-enter-active  { transition: all 0.25s ease; }
.fade-down-enter-from    { opacity: 0; transform: translateY(-6px); }
</style>
