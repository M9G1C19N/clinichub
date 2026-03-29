<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const form = useForm({
    password: '',
    password_confirmation: '',
})

const showPassword = ref(false)
const showConfirm = ref(false)

const submit = () => {
    form.post(route('password.update'))
}
</script>

<template>
    <div class="min-h-screen bg-navy flex items-center justify-center p-4">
          <ProgressBar />
        <div class="w-full max-w-md">

            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-14 h-14 bg-clinic-blue rounded-2xl mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">ClinicHub</h1>
                <p class="text-slate-400 text-sm mt-1">St. Peter Diagnostics & Laboratory</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">

                <!-- Warning badge -->
                <div class="mb-6">
                    <div class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700 px-3 py-1.5 rounded-lg text-xs font-medium mb-3">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Action Required
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">Set New Password</h2>
                    <p class="text-slate-500 text-sm mt-1">
                        Your account requires a password change before continuing.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">

                    <!-- New Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            New Password
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="Minimum 8 characters"
                                class="w-full px-4 py-2.5 pr-11 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                                :class="form.errors.password
                                    ? 'border-red-400 bg-red-50 focus:ring-red-300'
                                    : 'border-slate-300 focus:ring-blue-500'"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-600">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Confirm New Password
                        </label>
                        <div class="relative">
                            <input
                                v-model="form.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                required
                                placeholder="Re-enter your new password"
                                class="w-full px-4 py-2.5 pr-11 border rounded-xl text-sm focus:outline-none focus:ring-2 focus:border-transparent transition"
                                :class="form.errors.password_confirmation
                                    ? 'border-red-400 bg-red-50 focus:ring-red-300'
                                    : 'border-slate-300 focus:ring-blue-500'"
                            />
                            <button
                                type="button"
                                @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                            >
                                <svg v-if="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Password requirements -->
                    <div class="bg-slate-50 rounded-xl p-3 space-y-1.5">
                        <p class="text-xs font-medium text-slate-500 mb-2">Password requirements:</p>
                        <div class="flex items-center gap-2 text-xs transition-colors"
                            :class="form.password.length >= 8 ? 'text-emerald-600' : 'text-slate-400'">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            At least 8 characters
                        </div>
                        <div class="flex items-center gap-2 text-xs transition-colors"
                            :class="form.password.length > 0 && form.password === form.password_confirmation ? 'text-emerald-600' : 'text-slate-400'">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                            </svg>
                            Passwords match
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing || form.password.length < 8"
                        class="w-full bg-clinic-blue hover:bg-navy text-white font-semibold py-2.5 px-4 rounded-xl text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        {{ form.processing ? 'Saving...' : 'Set Password & Continue' }}
                    </button>

                </form>
            </div>

            <p class="text-center text-slate-500 text-xs mt-6">
                © {{ new Date().getFullYear() }} ClinicHub · St. Peter Diagnostics
            </p>
        </div>
    </div>
</template>
