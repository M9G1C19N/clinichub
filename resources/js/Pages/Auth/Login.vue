<script setup>
import { useForm } from '@inertiajs/vue3'
import ProgressBar from '@/Components/Layout/ProgressBar.vue'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'
const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post(route('login.post'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <div class="min-h-screen bg-slate-900 flex items-center justify-center p-4">
          <ProgressBar />
        <div class="w-full max-w-md">
            <!-- Logo / Clinic Name -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16mb-4">
                    <img :src="CLINIC_LOGO" class="w-12 h-12 object-contain bg-white rounded-xl p-1"/>
                </div>
                <h1 class="text-2xl font-bold text-white">ClinicHub</h1>
                <p class="text-slate-400 text-sm mt-1">St. Peter Diagnostics & Laboratory</p>
            </div>

            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-2xl p-8">
                <h2 class="text-xl font-semibold text-slate-800 mb-6">Staff Login</h2>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email Address</label>
                        <input
                            v-model="form.email"
                            type="email"
                            autocomplete="email"
                            required
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-400': form.errors.email }"
                            placeholder="you@clinichub.local"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="current-password"
                            required
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{ 'border-red-400': form.errors.password }"
                            placeholder="••••••••"
                        />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center">
                        <input v-model="form.remember" id="remember" type="checkbox"
                            class="w-4 h-4 text-blue-600 border-slate-300 rounded" />
                        <label for="remember" class="ml-2 text-sm text-slate-600">Remember me</label>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-lg text-sm transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                    >
                        <span v-if="form.processing">Signing in...</span>
                        <span v-else>Sign In</span>
                    </button>
                </form>
            </div>

            <p class="text-center text-slate-500 text-xs mt-6">
                © {{ new Date().getFullYear() }} ClinicHub · St. Peter Diagnostics
            </p>
        </div>
    </div>
</template>
