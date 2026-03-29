import {
    LayoutDashboard, Users, ClipboardList, FlaskConical,
    Radiation, TestTube, Stethoscope, Receipt, Calendar,
    Settings, BarChart3, ShieldCheck, UserCog, Pill
} from 'lucide-vue-next'

// Each nav item: { label, route, icon, permission?, roles? }
export const navigationByRole = {

    admin: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Reception',      href: '/reception',        icon: ClipboardList },
        { label: 'Queue',          href: '/queue',            icon: ClipboardList },
        { label: 'Laboratory',     href: '/laboratory',       icon: FlaskConical },
        { label: 'X-Ray & UTZ',    href: '/xray',             icon: Radiation },
        { label: 'Drug Test',      href: '/drug-test',        icon: TestTube },
        { label: 'Doctor',         href: '/doctor',           icon: Stethoscope },
        { label: 'Appointments',   href: '/appointments',     icon: Calendar },
        { label: 'Billing',        href: '/billing',          icon: Receipt },
        { label: 'Prescriptions',  href: '/prescriptions',    icon: Pill },
        { label: 'Reports',        href: '/reports',          icon: BarChart3 },
        { divider: true },
        { label: 'Service Catalog',href: '/admin/services',   icon: Settings },
        { label: 'Users & Staff',  href: '/admin/users',      icon: UserCog },
        { label: 'Audit Logs',     href: '/admin/audit',      icon: ShieldCheck },
    ],

    receptionist: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Reception',      href: '/reception',        icon: ClipboardList },
        { label: 'Queue',          href: '/queue',            icon: ClipboardList },
        { label: 'Appointments',   href: '/appointments',     icon: Calendar },
        { label: 'Billing',        href: '/billing',          icon: Receipt },
    ],

    nurse: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Queue',          href: '/queue',            icon: ClipboardList },
    ],

    doctor: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Patients',       href: '/patients',         icon: Users },
        { label: 'Consultations',  href: '/doctor',           icon: Stethoscope },
        { label: 'Queue',          href: '/queue',            icon: ClipboardList },
        { label: 'Appointments',   href: '/appointments',     icon: Calendar },
        { label: 'Prescriptions',  href: '/prescriptions',    icon: Pill },
        { label: 'Lab Results',    href: '/laboratory',       icon: FlaskConical },
    ],

    lab_technician: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Lab Queue',      href: '/laboratory',       icon: FlaskConical },
        { label: 'Patients',       href: '/patients',         icon: Users },
    ],

    xray_tech: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Imaging Queue',  href: '/xray',             icon: Radiation },
        { label: 'Patients',       href: '/patients',         icon: Users },
    ],

    drug_test_staff: [
        { label: 'Dashboard',      href: '/dashboard',        icon: LayoutDashboard },
        { label: 'Drug Test Queue',href: '/drug-test',        icon: TestTube },
        { label: 'Patients',       href: '/patients',         icon: Users },
    ],
}
