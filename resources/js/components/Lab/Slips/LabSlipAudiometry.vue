<script setup>
import { computed } from 'vue'
import { CLINIC_LOGO } from '@/config/clinic.js'

const props = defineProps({
    patient:   Object,
    visit:     Object,
    audResult: Object,
})

// ── CHART CONSTANTS ──────────────────────────────────────
const CW = 420, CH = 200
const ML = 44, MR = 12, MT = 12, MB = 28
const PW = CW - ML - MR
const PH = CH - MT - MB
const DB_MIN = -10, DB_MAX = 120, DB_RANGE = DB_MAX - DB_MIN
const FREQS = [250, 500, 1000, 2000, 4000, 8000]

function xPos(i)  { return ML + (i / (FREQS.length - 1)) * PW }
function yPos(db) { return MT + ((db - DB_MIN) / DB_RANGE) * PH }

const dbGridLines = computed(() => {
    const lines = []
    for (let db = DB_MIN; db <= DB_MAX; db += 10) lines.push({ db, y: yPos(db) })
    return lines
})
const freqGridLines = computed(() => FREQS.map((hz, i) => ({ hz, x: xPos(i) })))

const R_KEYS = ['re_250','re_500','re_1000','re_2000','re_4000','re_8000']
const L_KEYS = ['le_250','le_500','le_1000','le_2000','le_4000','le_8000']

const rePoints = computed(() =>
    R_KEYS.map((k, i) => {
        const v = parseFloat(props.audResult?.[k])
        return isNaN(v) ? null : { x: xPos(i), y: yPos(v) }
    })
)
const lePoints = computed(() =>
    L_KEYS.map((k, i) => {
        const v = parseFloat(props.audResult?.[k])
        return isNaN(v) ? null : { x: xPos(i), y: yPos(v) }
    })
)

function segs(points) {
    const out = []
    for (let i = 1; i < points.length; i++) {
        if (points[i] && points[i - 1])
            out.push({ x1: points[i-1].x, y1: points[i-1].y, x2: points[i].x, y2: points[i].y })
    }
    return out
}
const reSegs = computed(() => segs(rePoints.value))
const leSegs = computed(() => segs(lePoints.value))

const nZoneY1 = yPos(0), nZoneY2 = yPos(25)

// Case History display
const ch = computed(() => props.audResult ?? {})
function yn(val) { return val ? 'YES' : 'NO' }

const caseHistory = computed(() => [
    { label: 'Do you think you have a hearing loss?',                            answer: yn(ch.value.ch_hearing_loss),       note: ch.value.ch_hearing_loss ? ch.value.ch_hearing_loss_when : '' },
    { label: 'Have hearing aid(s) ever been recommended for you?',               answer: yn(ch.value.ch_hearing_aid),        note: '' },
    { label: 'Is your hearing better in one ear?',                               answer: yn(ch.value.ch_better_ear),         note: ch.value.ch_better_ear ? ch.value.ch_better_ear_which : '' },
    { label: 'Have you ever had a sudden or rapid progression of hearing loss?',  answer: yn(ch.value.ch_sudden_progression), note: '' },
    { label: 'Do you have ringing or noises in your ears?',                      answer: yn(ch.value.ch_ringing_noises),     note: '' },
    { label: 'Have you had recent drainage from your ears?',                     answer: yn(ch.value.ch_drainage),           note: '' },
    { label: 'Do you have pain or discomfort in your ears?',                     answer: yn(ch.value.ch_pain_discomfort),    note: '' },
    { label: 'Have you received medical consultation for any of the above conditions?', answer: yn(ch.value.ch_medical_consultation), note: '' },
])

const freqCols = [
    { label: '250',  re: 're_250',  le: 'le_250'  },
    { label: '500',  re: 're_500',  le: 'le_500'  },
    { label: '1000', re: 're_1000', le: 'le_1000' },
    { label: '1500', re: 're_1500', le: 'le_1500' },
    { label: '2000', re: 're_2000', le: 'le_2000' },
    { label: '3000', re: 're_3000', le: 'le_3000' },
    { label: '4000', re: 're_4000', le: 'le_4000' },
    { label: '8000', re: 're_8000', le: 'le_8000' },
]
function fv(key) {
    const v = props.audResult?.[key]
    return (v !== null && v !== undefined && v !== '') ? v : ''
}
</script>

<template>
    <div style="font-family:Arial,sans-serif;font-size:11px;color:#111;background:white;
                width:210mm;min-height:287mm;padding:8mm 10mm;box-sizing:border-box;">

        <!-- ══ HEADER ══ -->
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:5px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <img :src="CLINIC_LOGO" style="width:50px;height:50px;object-fit:contain;flex-shrink:0;"/>
                <div>
                    <div style="font-weight:900;font-size:12px;line-height:1.2;">SAINT PETER DIAGNOSTICS AND LABORATORY</div>
                    <div style="font-size:9px;color:#333;">Medical and Dental Clinic</div>
                    <div style="font-size:9px;color:#333;">Brgy. Ladgaron, Claver, Surigao del Norte</div>
                </div>
            </div>
            <div style="border:2px solid #111;padding:6px 14px;text-align:center;">
                <div style="font-weight:900;font-size:13px;letter-spacing:0.5px;line-height:1.2;">SCREENING</div>
                <div style="font-weight:900;font-size:13px;letter-spacing:0.5px;">AUDIOMETRY</div>
            </div>
        </div>

        <!-- ══ PATIENT INFO ══ -->
        <div style="border-top:2px solid #111;border-bottom:1px solid #999;padding:4px 0;margin-bottom:5px;">
            <div style="display:flex;gap:16px;margin-bottom:3px;">
                <div style="flex:2;">
                    <span style="font-size:9px;color:#555;">Name of Patient:</span>
                    <strong style="margin-left:4px;font-size:10px;">
                        {{ patient?.last_name }}, {{ patient?.first_name }}
                        {{ patient?.middle_name ? patient.middle_name.charAt(0) + '.' : '' }}
                    </strong>
                </div>
                <div>
                    <span style="font-size:9px;color:#555;">Age/Sex:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ patient?.age_sex }}</strong>
                </div>
                <div>
                    <span style="font-size:9px;color:#555;">Birthday:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ patient?.birthdate }}</strong>
                </div>
            </div>
            <div style="display:flex;gap:16px;margin-bottom:3px;">
                <div style="flex:2;">
                    <span style="font-size:9px;color:#555;">Address/Company:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ [patient?.address, visit?.employer_company].filter(Boolean).join(' / ') }}</strong>
                </div>
                <div>
                    <span style="font-size:9px;color:#555;">Dept/Section:</span>
                    <strong style="margin-left:4px;font-size:10px;">&nbsp;</strong>
                </div>
                <div>
                    <span style="font-size:9px;color:#555;">Designation:</span>
                    <strong style="margin-left:4px;font-size:10px;">&nbsp;</strong>
                </div>
            </div>
            <div style="display:flex;gap:16px;">
                <div>
                    <span style="font-size:9px;color:#555;">Audiometer Used:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ audResult?.audiometer_used }}</strong>
                </div>
                <div>
                    <span style="font-size:9px;color:#555;">Last Calibrated:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ audResult?.last_calibrated }}</strong>
                </div>
                <div>
                    <span style="font-size:9px;color:#555;">Examiner:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ audResult?.examiner }}</strong>
                </div>
                <div style="flex:1;">
                    <span style="font-size:9px;color:#555;">Reason for Audiometry:</span>
                    <strong style="margin-left:4px;font-size:10px;">{{ audResult?.reason_for_audiometry }}</strong>
                </div>
            </div>
        </div>

        <!-- ══ CASE HISTORY ══ -->
        <div style="margin-bottom:5px;">
            <div style="font-weight:900;font-size:10px;text-transform:uppercase;margin-bottom:3px;letter-spacing:0.5px;">
                Case History
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:9.5px;">
                <tbody>
                    <tr v-for="q in caseHistory" :key="q.label" style="border:1px solid #ccc;">
                        <td style="padding:2px 6px;border:1px solid #ccc;width:68%;">{{ q.label }}</td>
                        <td style="padding:2px 6px;border:1px solid #ccc;width:10%;text-align:center;font-weight:900;"
                            :style="{ color: q.answer === 'YES' ? '#dc2626' : '#111' }">
                            {{ q.answer }}
                        </td>
                        <td style="padding:2px 6px;border:1px solid #ccc;width:22%;font-size:9px;color:#444;">
                            {{ q.note }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ══ VISUAL/OTOSCOPIC ══ -->
        <div style="margin-bottom:5px;">
            <div style="font-weight:900;font-size:10px;text-transform:uppercase;margin-bottom:3px;letter-spacing:0.5px;">
                Visual/Otoscopic Inspection
            </div>
            <div style="display:flex;gap:20px;font-size:9.5px;">
                <div style="flex:1;border-bottom:1px solid #999;">
                    Right Ear: <strong>{{ audResult?.otoscopic_right_ear }}</strong>
                </div>
                <div style="flex:1;border-bottom:1px solid #999;">
                    Left Ear: <strong>{{ audResult?.otoscopic_left_ear }}</strong>
                </div>
            </div>
        </div>

        <!-- ══ PURE TONE TABLE ══ -->
        <div style="margin-bottom:4px;">
            <div style="font-weight:900;font-size:10px;text-transform:uppercase;margin-bottom:3px;letter-spacing:0.5px;">
                Pure Tone Screening Audiometry
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:9.5px;text-align:center;">
                <thead>
                    <tr style="background:#f0f0f0;">
                        <th style="border:1.5px solid #111;padding:3px 4px;font-size:9px;width:40px;"></th>
                        <th v-for="col in freqCols" :key="col.label"
                            style="border:1.5px solid #111;padding:3px 2px;font-size:9px;">
                            {{ col.label }}
                        </th>
                        <th style="border:1.5px solid #111;padding:3px 4px;font-size:9px;background:#e0e0e0;">AVERAGE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border:1.5px solid #111;padding:4px;font-weight:900;color:#dc2626;background:#fff5f5;">R.E.</td>
                        <td v-for="col in freqCols" :key="col.re"
                            style="border:1.5px solid #111;padding:4px;font-weight:700;">
                            {{ fv(col.re) }}
                        </td>
                        <td style="border:1.5px solid #111;padding:4px;font-weight:900;background:#f5f5f5;">
                            {{ audResult?.re_average ?? '' }}
                        </td>
                    </tr>
                    <tr>
                        <td style="border:1.5px solid #111;padding:4px;font-weight:900;color:#2563eb;background:#eff6ff;">L.E.</td>
                        <td v-for="col in freqCols" :key="col.le"
                            style="border:1.5px solid #111;padding:4px;font-weight:700;">
                            {{ fv(col.le) }}
                        </td>
                        <td style="border:1.5px solid #111;padding:4px;font-weight:900;background:#f5f5f5;">
                            {{ audResult?.le_average ?? '' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ══ AUDIOGRAM CHART ══ -->
        <div style="margin-bottom:5px;">
            <svg :width="CW" :height="CH" :viewBox="`0 0 ${CW} ${CH}`" style="width:100%;height:auto;display:block;">
                <!-- Y-axis label -->
                <text :x="8" :y="MT + PH/2" text-anchor="middle" font-size="8" fill="#555" font-weight="600"
                    :transform="`rotate(-90, 8, ${MT + PH/2})`">Hearing Level (dB)</text>

                <!-- Normal zone -->
                <rect :x="ML" :y="nZoneY1" :width="PW" :height="nZoneY2 - nZoneY1" fill="#d1fae5" fill-opacity="0.5"/>

                <!-- Horizontal gridlines -->
                <g v-for="gl in dbGridLines" :key="gl.db">
                    <line :x1="ML" :y1="gl.y" :x2="ML + PW" :y2="gl.y"
                        :stroke="gl.db % 20 === 0 ? '#aaa' : '#ddd'"
                        :stroke-width="gl.db === 0 ? 1.5 : 0.8"/>
                    <text :x="ML - 4" :y="gl.y + 3" text-anchor="end" font-size="7" fill="#888">{{ gl.db }}</text>
                </g>

                <!-- Vertical gridlines (frequencies) -->
                <g v-for="gf in freqGridLines" :key="gf.hz">
                    <line :x1="gf.x" :y1="MT" :x2="gf.x" :y2="MT + PH" stroke="#ddd" stroke-width="0.8"/>
                    <text :x="gf.x" :y="MT + PH + 12" text-anchor="middle" font-size="7" fill="#666">
                        {{ gf.hz >= 1000 ? (gf.hz/1000)+'k' : gf.hz }}
                    </text>
                </g>

                <!-- Axis labels -->
                <text :x="ML + PW/2" :y="CH - 2" text-anchor="middle" font-size="8" fill="#555">Frequency Hz</text>

                <!-- Border -->
                <rect :x="ML" :y="MT" :width="PW" :height="PH" fill="none" stroke="#999" stroke-width="1"/>

                <!-- Low/High pitch labels -->
                <text :x="ML + 4" :y="CH - 2" font-size="7" fill="#888">Low pitch</text>
                <text :x="ML + PW - 4" :y="CH - 2" text-anchor="end" font-size="7" fill="#888">High pitch</text>

                <!-- Right Ear (O — red) lines -->
                <line v-for="(s, i) in reSegs" :key="`rs${i}`"
                    :x1="s.x1" :y1="s.y1" :x2="s.x2" :y2="s.y2"
                    stroke="#dc2626" stroke-width="1.2"/>
                <!-- Right Ear O markers -->
                <g v-for="(pt, i) in rePoints" :key="`ro${i}`">
                    <circle v-if="pt" :cx="pt.x" :cy="pt.y" r="4"
                        fill="white" stroke="#dc2626" stroke-width="1.5"/>
                </g>

                <!-- Left Ear (X — blue) lines -->
                <line v-for="(s, i) in leSegs" :key="`ls${i}`"
                    :x1="s.x1" :y1="s.y1" :x2="s.x2" :y2="s.y2"
                    stroke="#2563eb" stroke-width="1.2"/>
                <!-- Left Ear X markers -->
                <g v-for="(pt, i) in lePoints" :key="`lx${i}`">
                    <g v-if="pt">
                        <line :x1="pt.x-4" :y1="pt.y-4" :x2="pt.x+4" :y2="pt.y+4" stroke="#2563eb" stroke-width="1.5"/>
                        <line :x1="pt.x+4" :y1="pt.y-4" :x2="pt.x-4" :y2="pt.y+4" stroke="#2563eb" stroke-width="1.5"/>
                    </g>
                </g>
            </svg>
            <!-- Legend -->
            <div style="display:flex;gap:24px;font-size:9px;justify-content:center;margin-top:2px;">
                <span>
                    <svg width="12" height="12" viewBox="0 0 12 12" style="vertical-align:middle;margin-right:3px;">
                        <circle cx="6" cy="6" r="4" fill="none" stroke="#dc2626" stroke-width="1.5"/>
                    </svg>
                    O – Right Ear
                </span>
                <span>
                    <svg width="12" height="12" viewBox="0 0 12 12" style="vertical-align:middle;margin-right:3px;">
                        <line x1="2" y1="2" x2="10" y2="10" stroke="#2563eb" stroke-width="1.5"/>
                        <line x1="10" y1="2" x2="2" y2="10" stroke="#2563eb" stroke-width="1.5"/>
                    </svg>
                    X – Left Ear
                </span>
            </div>
        </div>

        <!-- ══ REMARKS ══ -->
        <div style="margin-bottom:5px;">
            <div style="font-weight:900;font-size:10px;text-transform:uppercase;margin-bottom:3px;letter-spacing:0.5px;">Remarks:</div>
            <div style="display:flex;gap:20px;font-size:9.5px;">
                <div style="flex:1;">
                    <span style="font-weight:700;">RIGHT EAR</span>
                    <div style="border-bottom:1px solid #999;min-height:18px;padding:2px;">
                        {{ audResult?.remarks_right_ear }}
                    </div>
                </div>
                <div style="flex:1;">
                    <span style="font-weight:700;">LEFT EAR</span>
                    <div style="border-bottom:1px solid #999;min-height:18px;padding:2px;">
                        {{ audResult?.remarks_left_ear }}
                    </div>
                </div>
            </div>
        </div>

        <!-- ══ RECOMMENDATIONS ══ -->
        <div style="margin-bottom:10px;">
            <div style="font-weight:900;font-size:10px;text-transform:uppercase;margin-bottom:3px;letter-spacing:0.5px;">Recommendations:</div>
            <div style="border-bottom:1px solid #999;min-height:18px;font-size:9.5px;padding:2px;">
                {{ audResult?.recommendations }}
            </div>
        </div>

        <!-- ══ FOOTER / SIGNATURES ══ -->
        <div style="display:flex;justify-content:space-between;align-items:flex-end;border-top:1.5px solid #aaa;padding-top:8px;margin-top:8px;">
            <!-- Examined By -->
            <div style="font-size:10px;">
                <div style="color:#555;margin-bottom:4px;">Examined By:</div>
                <div style="min-height:40px;display:flex;align-items:flex-end;margin-bottom:3px;">
                    <img v-if="audResult?.examined_by_signature"
                        :src="audResult.examined_by_signature"
                        style="max-height:38px;max-width:150px;object-fit:contain;" alt="Signature"/>
                </div>
                <div style="border-top:1.5px solid #333;padding-top:2px;">
                    <div style="font-weight:900;font-size:11px;">{{ audResult?.examined_by_name }}</div>
                    <div style="font-size:9.5px;">License No.: {{ audResult?.examined_by_license }}</div>
                </div>
            </div>
            <!-- Noted By -->
            <div style="font-size:10px;text-align:right;">
                <div style="color:#555;margin-bottom:4px;">Noted:</div>
                <div style="min-height:40px;display:flex;align-items:flex-end;justify-content:flex-end;margin-bottom:3px;">
                    <img v-if="audResult?.noted_by_signature"
                        :src="audResult.noted_by_signature"
                        style="max-height:38px;max-width:150px;object-fit:contain;" alt="Signature"/>
                </div>
                <div style="border-top:1.5px solid #333;padding-top:2px;">
                    <div style="font-weight:900;font-size:11px;">{{ audResult?.noted_by_name }}</div>
                    <div style="font-size:9.5px;">Licensed No. {{ audResult?.noted_by_license }}</div>
                </div>
            </div>
        </div>

    </div>
</template>
