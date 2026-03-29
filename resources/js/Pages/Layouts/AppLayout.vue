<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { navigationByRole } from '@/config/navigation'
import { usePermissions } from '@/composables/usePermissions'
import {
    ChevronLeft, ChevronRight, LogOut, Activity
} from 'lucide-vue-next'

const page = usePage()
const { role, user } = usePermissions()

const collapsed = ref(false)

const navItems = computed(() => navigationByRole[role.value] ?? [])

const isActive = (href) => {
    return page.url === href || page.url.startsWith(href + '/')
}

const roleBadgeColor = {
    admin:           'bg-accent text-white',
    receptionist:    'bg-blue text-white',
    nurse:           'bg-emerald-500 text-white',
    doctor:          'bg-purple-600 text-white',
    lab_technician:  'bg-amber-500 text-white',
    xray_tech:       'bg-orange-500 text-white',
    drug_test_staff: 'bg-rose-500 text-white',
}

const roleLabel = {
    admin:           'Admin',
    receptionist:    'Receptionist',
    nurse:           'Nurse',
    doctor:          'Doctor',
    lab_technician:  'Lab Tech',
    xray_tech:       'X-Ray Tech',
    drug_test_staff: 'Drug Test',
}
</script>

<template>
    <aside
        :class="[
            'flex flex-col h-screen bg-navy transition-all duration-300 ease-in-out flex-shrink-0',
            collapsed ? 'w-16' : 'w-64'
        ]"
    >
        <!-- Logo / Brand -->
        <div class="flex items-center h-16 px-4 border-b border-navy-light flex-shrink-0">
            <div class="flex items-center justify-center w-9 h-9 bg-blue rounded-xl flex-shrink-0">
                <Activity class="w-5 h-5 text-white" />
            </div>
            <transition name="fade-slide">
                <div v-if="!collapsed" class="ml-3 overflow-hidden">
                    <p class="text-white font-bold text-sm leading-tight">ClinicHub</p>
                    <p class="text-sky/60 text-xs leading-tight truncate">St. Peter Diagnostics</p>
                </div>
            </transition>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden scrollbar-thin">
            <ul class="space-y-0.5 px-2">
                <template v-for="(item, index) in navItems" :key="index">
                    <!-- Divider -->
                    <li v-if="item.divider" class="my-3">
                        <div class="border-t border-navy-light mx-2" />
                    </li>

                    <!-- Nav Item -->
                    <li v-else>
                        <Link
                            :href="item.href"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all duration-150 group relative',
                                isActive(item.href)
                                    ? 'bg-blue text-white shadow-md'
                                    : 'text-sky/70 hover:bg-navy-light hover:text-white'
                            ]"
                        >
                            <!-- Active indicator bar -->
                            <span
                                v-if="isActive(item.href)"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 bg-accent rounded-r-full"
                            />

                            <component
                                :is="item.icon"
                                :class="[
                                    'flex-shrink-0 transition-colors',
                                    collapsed ? 'w-5 h-5' : 'w-4.5 h-4.5',
                                    isActive(item.href) ? 'text-white' : 'text-sky/50 group-hover:text-white'
                                ]"
                            />

                            <transition name="fade-slide">
                                <span
                                    v-if="!collapsed"
                                    class="text-sm font-medium truncate"
                                >
                                    {{ item.label }}
                                </span>
                            </transition>

                            <!-- Tooltip when collapsed -->
                            <div
                                v-if="collapsed"
                                class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded-lg
                                       opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none"
                            >
                                {{ item.label }}
                            </div>
                        </Link>
                    </li>
                </template>
            </ul>
        </nav>

        <!-- User Profile + Logout -->
        <div class="border-t border-navy-light p-3 flex-shrink-0">
            <div class="flex items-center gap-3">
                <!-- Avatar -->
                <div class="flex-shrink-0 w-9 h-9 rounded-xl bg-mid-blue flex items-center justify-center text-white font-semibold text-sm uppercase">
                    {{ user?.name?.charAt(0) ?? 'U' }}
                </div>

                <transition name="fade-slide">
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate">{{ user?.name }}</p>
                        <span
                            :class="['inline-block text-xs px-2 py-0.5 rounded-full font-medium mt-0.5', roleBadgeColor[role] ?? 'bg-gray-500 text-white']"
                        >
                            {{ roleLabel[role] ?? role }}
                        </span>
                    </div>
                </transition>

                <transition name="fade-slide">
                    <Link
                        v-if="!collapsed"
                        href="/logout"
                        method="post"
                        as="button"
                        class="flex-shrink-0 p-1.5 text-sky/40 hover:text-rose-400 transition-colors rounded-lg hover:bg-navy-light"
                        title="Logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </Link>
                </transition>
            </div>
        </div>

        <!-- Collapse Toggle -->
        <button
            @click="collapsed = !collapsed"
            class="absolute -right-3 top-20 w-6 h-6 bg-navy border-2 border-navy-light rounded-full
                   flex items-center justify-center text-sky/60 hover:text-white transition-colors z-10"
        >
            <ChevronLeft v-if="!collapsed" class="w-3 h-3" />
            <ChevronRight v-else class="w-3 h-3" />
        </button>
    </aside>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-slide-enter-from,
.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-6px);
}
</style>
