<script setup>
import { computed, ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import {
    LayoutDashboard, Users, ClipboardList, FlaskConical,
    Radiation, TestTube, Stethoscope, Receipt, Calendar,
    Settings, BarChart3, ShieldCheck, UserCog, Pill,
    ChevronLeft, ChevronRight, LogOut, Activity, Monitor,
    Microscope, ScanLine, Syringe, HeartPulse, PenLine,
    DatabaseBackup, RefreshCw, MonitorPlay, Tag, Building2,
    Ear,
} from 'lucide-vue-next'
import { CLINIC_LOGO, CLINIC_INFO } from '@/config/clinic.js'

const page = usePage()
const collapsed = ref(false)

const user = computed(() => page.props.auth.user)
const role = computed(() => user.value?.role)

const permissions = computed(() => page.props.auth.user?.permissions ?? [])
const hasPermission = (perm) => permissions.value.includes(perm)


const navMap = {

    admin: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Reception',      href: '/reception',        icon: ClipboardList },
        { label: 'Queue',          href: '/queue',            icon: HeartPulse },
        { label: 'Kiosk',         href: '/queue/kiosk',      icon: MonitorPlay },
        { label: 'Laboratory',     href: '/laboratory',       icon: FlaskConical },
        { label: 'Audiometry',     href: '/audiometry',       icon: Ear },
        { label: 'X-Ray & UTZ',    href: '/xray',             icon: ScanLine },
        { label: 'Drug Test',      href: '/drug-test',        icon: TestTube },
        { label: 'Doctor',         href: '/doctor',           icon: Stethoscope },
        { label: 'Appointments',   href: '/appointments',     icon: Calendar },
        { label: 'Billing',        href: '/billing',                  icon: Receipt },
        { label: 'Company Billing',href: '/billing/company-billing',  icon: Building2 },
        { label: 'Reports',        href: '/reports',                  icon: BarChart3 },
        { divider: true },
        { label: 'Service Catalog',href: '/admin/services',          icon: Settings },
        { label: 'Pkg Discounts', href: '/admin/package-discounts', icon: Tag },
        { label: 'Users & Staff',  href: '/admin/users',          icon: UserCog },
        { label: 'E-Signatures',   href: '/admin/esignatures',    icon: PenLine },
        { label: 'Booking Photos', href: '/admin/booking-photos', icon: Monitor },
        { label: 'Audit Logs',     href: '/admin/audit',          icon: ShieldCheck },
        { divider: true },
        { label: 'Field Sync',     href: '/admin/field-sync',     icon: RefreshCw },
        { label: 'DB Backup',      href: '/admin/backup',         icon: DatabaseBackup },
    ],

    receptionist: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Reception',      href: '/reception',        icon: ClipboardList },
        { label: 'Queue',          href: '/queue',            icon: HeartPulse },
        { label: 'Kiosk',         href: '/queue/kiosk',      icon: MonitorPlay },
        { label: 'Appointments',   href: '/appointments',     icon: Calendar },
        { label: 'Billing',        href: '/billing',          icon: Receipt },
    ],

    nurse: [
        { label: 'Dashboard',      href: '/dashboard',              icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',               icon: Users },
        { label: 'Nurse Intake',   href: '/nurse',                  icon: HeartPulse },
        { divider: true },
        { label: 'Nurse Station',  href: '/queue/room/nurse_station', icon: Monitor, badge: 'Room Screen' },
    ],


    doctor: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Nurse Intake',   href: '/nurse',            icon: HeartPulse },
        { label: 'Consultations',  href: '/doctor',           icon: Stethoscope },
        { label: 'Queue',          href: '/queue',            icon: Activity },
        { label: 'Appointments',   href: '/appointments',     icon: Calendar },
        { label: 'Prescriptions',  href: '/prescriptions',    icon: Pill },
        { label: 'Lab Results',    href: '/laboratory',       icon: FlaskConical },
        { divider: true },
        { label: 'Nurse Station',  href: '/queue/room/nurse_station',  icon: Monitor, badge: 'Room Screen' },
        { label: 'Interview Room', href: '/queue/room/interview_room', icon: Monitor, badge: 'Room Screen' },
    ],

    lab_technician: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Queue',          href: '/queue',            icon: HeartPulse },
        { divider: true },
        { label: 'Lab Room Screen',href: '/queue/room/laboratory', icon: Monitor, badge: 'Room Screen' },
        { label: 'Lab Requests',   href: '/laboratory',       icon: FlaskConical },
        { label: 'Audiometry',     href: '/audiometry',       icon: Ear },
    ],

    xray_tech: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Queue',          href: '/queue',            icon: HeartPulse },
        { divider: true },
        { label: 'X-Ray Room Screen', href: '/queue/room/xray_utz', icon: Monitor, badge: 'Room Screen' },
        { label: 'Imaging Queue',  href: '/xray',             icon: ScanLine },
    ],

    drug_test_staff: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Queue',          href: '/queue',            icon: HeartPulse },
        { divider: true },
        { label: 'Drug Test Screen',href: '/queue/room/drug_test', icon: Monitor, badge: 'Room Screen' },
        { label: 'Drug Test Queue',href: '/drug-test',        icon: TestTube },
    ],

    billing: [
        { label: 'Dashboard',      href: '/dashboard',                icon: LayoutDashboard },
        { label: 'Billing',        href: '/billing',                  icon: Receipt },
        { label: 'Company Billing',href: '/billing/company-billing',  icon: Building2 },
        { label: 'Reports',        href: '/billing/reports',          icon: BarChart3 },
        { label: 'Appointments',   href: '/appointments',             icon: Calendar },
        { label: 'Patients',       href: '/patients',                 icon: Users },
    ],
}

const navItems = computed(() => {
    const base = navMap[role.value] ?? navMap.admin

    // Nurse with doctor_features → merge doctor nav items
    if (role.value === 'nurse' && hasPermission('doctor_features')) {
        return [
            ...navMap.nurse,
            { label: 'Interview Room', href: '/queue/room/interview_room', icon: Monitor, badge: 'Room Screen' },
            { divider: true },
            { label: 'Consultations',  href: '/doctor',       icon: Stethoscope },
            { label: 'Prescriptions',  href: '/prescriptions', icon: Pill },
            { label: 'Lab Results',    href: '/laboratory',    icon: FlaskConical },
        ]
    }

    return base
})

const isActive = (href) => page.url === href || page.url.startsWith(href + '/')

const roleBadgeColor = {
    admin:           'bg-sky-500',
    receptionist:    'bg-blue-500',
    nurse:           'bg-emerald-500',
    doctor:          'bg-purple-600',
    lab_technician:  'bg-amber-500',
    xray_tech:       'bg-orange-500',
    drug_test_staff: 'bg-rose-500',
    billing:         'bg-teal-600',
}

const roleLabel = {
    admin:           'Admin',
    receptionist:    'Receptionist',
    nurse:           'Nurse',
    doctor:          'Doctor',
    lab_technician:  'Lab Tech',
    xray_tech:       'X-Ray Tech',
    drug_test_staff: 'Drug Test',
    billing:         'Billing',
}
</script>

<template>
    <aside
        :style="{ backgroundColor: '#0F2044' }"
        :class="[
            'flex flex-col h-screen transition-all duration-300 flex-shrink-0 relative',
            collapsed ? 'w-16' : 'w-64'
        ]"
    >
        <!-- Logo -->
           <div class="flex items-center gap-3 px-4 py-5 border-b border-white/10">
            <div class="w-10 h-10 rounded-lg bg-white p-0.5 flex-shrink-0 overflow-hidden">
                <img :src="CLINIC_LOGO" alt="SPDL Logo"
                    class="w-full h-full object-contain"/>
                <!-- Fallback if logo missing -->
                <div class="w-full h-full items-center justify-center text-blue-700 font-black text-sm hidden">
                    SP
                </div>
            </div>
            <div v-if="!collapsed" class="min-w-0">
                <p class="text-white font-black text-sm leading-tight truncate">St. Peter Diagnostics</p>
                <p class="text-white/50 text-xs truncate">& Laboratory</p>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 py-4 overflow-y-auto overflow-x-hidden">
            <ul class="space-y-0.5 px-2">
                <template v-for="(item, index) in navItems" :key="index">

                    <!-- Divider -->
                    <li v-if="item.divider" class="my-3 border-t border-white/10 mx-2" />

                    <!-- Nav Item -->
                    <li v-else>
                        <Link
                            :href="item.href"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all group relative',
                                isActive(item.href)
                                    ? 'text-white'
                                    : 'text-white/50 hover:text-white hover:bg-white/10'
                            ]"
                            :style="isActive(item.href) ? 'background-color:#1B4F9B' : ''"
                        >
                            <!-- Active bar -->
                            <span v-if="isActive(item.href)"
                                class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-6 rounded-r-full"
                                style="background-color:#0EA5E9"/>

                            <!-- Icon -->
                            <component
                                :is="item.icon"
                                :class="[
                                    'flex-shrink-0 transition-colors',
                                    collapsed ? 'w-5 h-5' : 'w-4 h-4',
                                    isActive(item.href) ? 'text-white' : 'text-white/40 group-hover:text-white'
                                ]"
                            />

                            <!-- Label -->
                            <transition name="fade-slide">
                                <div v-if="!collapsed" class="flex items-center gap-2 flex-1 min-w-0">
                                    <span class="text-sm font-medium truncate">{{ item.label }}</span>
                                    <!-- Room Screen badge -->
                                    <span v-if="item.badge"
                                        class="text-xs px-1.5 py-0.5 rounded-md font-semibold flex-shrink-0"
                                        style="background:#0EA5E9; color:white; font-size:9px">
                                        LIVE
                                    </span>
                                </div>
                            </transition>

                            <!-- Tooltip when collapsed -->
                            <div v-if="collapsed"
                                class="absolute left-full ml-2 px-2 py-1 bg-gray-900 text-white text-xs rounded-lg
                                       opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap z-50 pointer-events-none">
                                {{ item.label }}
                            </div>
                        </Link>
                    </li>
                </template>
            </ul>
        </nav>

        <!-- User Profile -->
        <div class="border-t border-white/10 p-3 flex-shrink-0">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-white font-semibold text-sm uppercase"
                    style="background-color:#2E75B6">
                    {{ user?.name?.charAt(0) ?? 'U' }}
                </div>
                <transition name="fade-slide">
                    <div v-if="!collapsed" class="flex-1 min-w-0">
                        <p class="text-white text-sm font-medium truncate">{{ user?.name }}</p>
                        <span :class="['inline-block text-xs px-2 py-0.5 rounded-full font-medium mt-0.5 text-white',
                            roleBadgeColor[role] ?? 'bg-slate-500']">
                            {{ roleLabel[role] ?? role }}
                        </span>
                    </div>
                </transition>
                <transition name="fade-slide">
                    <Link v-if="!collapsed"
                        href="/logout" method="post" as="button"
                        class="flex-shrink-0 p-1.5 text-white/30 hover:text-rose-400 transition-colors rounded-lg hover:bg-white/10"
                        title="Logout">
                        <LogOut class="w-4 h-4" />
                    </Link>
                </transition>
            </div>
        </div>

        <!-- Collapse Toggle -->
        <button
            @click="collapsed = !collapsed"
            class="absolute -right-3 top-20 w-6 h-6 border-2 border-white/20 rounded-full
                   flex items-center justify-center text-white/50 hover:text-white transition-colors z-10"
            style="background-color:#0F2044">
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

/* ── Custom scrollbar for the nav ── */
nav {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.15) transparent;
}
nav::-webkit-scrollbar {
    width: 4px;
}
nav::-webkit-scrollbar-track {
    background: transparent;
}
nav::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 99px;
}
nav::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.30);
}
</style>
