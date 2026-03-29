<template>
    <div class="min-h-screen bg-slate-900 flex items-center justify-center">
        <div class="text-center p-8">
            <!-- Icon box using inline style for custom color since v4 needs --color- prefix -->
            <div
                class="inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-6"
                style="background-color: #1B4F9B;"
            >
                <svg class="w-11 h-11 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>

            <h1 class="text-5xl font-bold text-white">ClinicHub</h1>
            <p class="text-slate-400 mt-2 text-lg">St. Peter Diagnostics & Laboratory</p>

            <!-- Tailwind v4 test badges -->
            <div class="mt-8 flex flex-col items-center gap-3">
                <div class="inline-flex items-center gap-2 bg-emerald-500/20 border border-emerald-500/30 text-emerald-400 px-4 py-2 rounded-xl text-sm font-medium">
                    <span class="w-2 h-2 bg-emerald-400 rounded-full animate-pulse inline-block"></span>
                    Tailwind v4 is working!
                </div>
                <div class="inline-flex items-center gap-2 bg-blue-500/20 border border-blue-500/30 text-blue-400 px-4 py-2 rounded-xl text-sm font-medium">
                    <span class="w-2 h-2 bg-blue-400 rounded-full inline-block"></span>
                    Inertia + Vue 3 connected
                </div>
            </div>
        </div>
    </div>
</template>
