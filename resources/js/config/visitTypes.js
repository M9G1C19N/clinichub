// Single source of truth for visit type labels and badge styles
// Used across all pages — update here only

export const VISIT_TYPE_LABEL = {
    opd:            'OPD',
    pre_employment: 'Pre-Employment',
    annual_pe:      'Annual PE',
    exit_pe:        'Exit PE',
    follow_up:      'Follow-up',
    lab_only:       'Lab Only',
}

// For pages using object style { bg, color, label }
export const VISIT_TYPE_BADGE = {
    opd:            { bg: '#eff6ff', color: '#1d4ed8', label: 'OPD'            },
    pre_employment: { bg: '#faf5ff', color: '#7c3aed', label: 'Pre-Employment' },
    annual_pe:      { bg: '#f0fdf4', color: '#15803d', label: 'Annual PE'      },
    exit_pe:        { bg: '#fff7ed', color: '#c2410c', label: 'Exit PE'        },
    follow_up:      { bg: '#fffbeb', color: '#b45309', label: 'Follow-up'      },
    lab_only:       { bg: '#f0fdfa', color: '#0f766e', label: 'Lab Only'       },
}

// For pages using Tailwind class style
export const VISIT_TYPE_CLASS = {
    opd:            'bg-blue-100 text-blue-700',
    pre_employment: 'bg-purple-100 text-purple-700',
    annual_pe:      'bg-emerald-100 text-emerald-700',
    exit_pe:        'bg-orange-100 text-orange-700',
    follow_up:      'bg-amber-100 text-amber-700',
    lab_only:       'bg-teal-100 text-teal-700',
}

// Which visit types are PE-type (need classification, physical exam, etc.)
export const IS_PE_TYPE = (visitType) =>
    ['pre_employment', 'annual_pe', 'exit_pe'].includes(visitType)
