<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\BookingPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    // ── Public: Booking Form ──────────────────────────────────────────────

    public function bookingForm()
    {
        $photos = BookingPhoto::active()->get()->map(fn($p) => [
            'id'      => $p->id,
            'url'     => $p->url,
            'caption' => $p->caption,
        ]);

        return inertia('Appointments/Landing', ['photos' => $photos]);
    }

    public function bookingStore(Request $request)
    {
        $validated = $request->validate([
            'patient_name'    => ['required', 'string', 'max:150'],
            'patient_email'   => ['nullable', 'email', 'max:150'],
            'patient_phone'   => ['required', 'string', 'max:30'],
            'patient_dob'     => ['nullable', 'date', 'before:today'],
            'patient_gender'  => ['nullable', 'in:male,female'],
            'service_type'    => ['required', 'in:general_consultation,laboratory,xray_utz,drug_test,physical_exam,prenatal,other'],
            'chief_complaint' => ['nullable', 'string', 'max:1000'],
            'preferred_date'  => ['required', 'date', 'after_or_equal:today'],
            'preferred_time'  => ['nullable', 'in:morning,afternoon'],
        ]);

        $appointment = Appointment::create(array_merge($validated, [
            'appointment_number' => Appointment::generateNumber(),
            'status'             => 'pending',
        ]));

        return inertia('Appointments/Confirmed', [
            'appointment' => [
                'appointment_number' => $appointment->appointment_number,
                'patient_name'       => $appointment->patient_name,
                'service_label'      => $appointment->service_label,
                'preferred_date'     => $appointment->preferred_date->format('F d, Y'),
                'preferred_time'     => $appointment->preferred_time_label,
                'chief_complaint'    => $appointment->chief_complaint,
            ],
        ]);
    }

    // ── Authenticated: Staff Management ──────────────────────────────────

    public function index(Request $request)
    {
        $search  = $request->get('search', '');
        $status  = $request->get('status', '');
        $date    = $request->get('date', '');
        $service = $request->get('service', '');

        $appointments = Appointment::with(['confirmedBy', 'cancelledBy'])
            ->when($search, fn($q) =>
                $q->where('patient_name', 'like', "%{$search}%")
                  ->orWhere('patient_phone', 'like', "%{$search}%")
                  ->orWhere('patient_email', 'like', "%{$search}%")
                  ->orWhere('appointment_number', 'like', "%{$search}%")
            )
            ->when($status, fn($q) => $q->where('status', $status))
            ->when($date, fn($q) => $q->whereDate('preferred_date', $date))
            ->when($service, fn($q) => $q->where('service_type', $service))
            ->orderByRaw("FIELD(status, 'pending', 'confirmed', 'completed', 'no_show', 'cancelled')")
            ->orderBy('preferred_date')
            ->paginate(20)
            ->withQueryString();

        $mapped = $appointments->getCollection()->map(fn($a) => [
            'id'                 => $a->id,
            'appointment_number' => $a->appointment_number,
            'patient_name'       => $a->patient_name,
            'patient_phone'      => $a->patient_phone,
            'patient_email'      => $a->patient_email,
            'patient_gender'     => $a->patient_gender,
            'service_type'       => $a->service_type,
            'service_label'      => $a->service_label,
            'chief_complaint'    => $a->chief_complaint,
            'preferred_date'     => $a->preferred_date->format('M d, Y'),
            'preferred_date_raw' => $a->preferred_date->toDateString(),
            'preferred_time'     => $a->preferred_time,
            'preferred_time_label' => $a->preferred_time_label,
            'status'             => $a->status,
            'confirmed_by'       => $a->confirmedBy?->name,
            'confirmed_at'       => $a->confirmed_at?->format('M d, Y h:i A'),
            'admin_notes'        => $a->admin_notes,
            'created_at'         => $a->created_at->format('M d, Y h:i A'),
        ]);
        $appointments->setCollection($mapped);

        // Summary counts
        $summary = [
            'pending'   => Appointment::where('status', 'pending')->count(),
            'confirmed' => Appointment::where('status', 'confirmed')->count(),
            'today'     => Appointment::whereDate('preferred_date', today())->whereIn('status', ['pending', 'confirmed'])->count(),
            'this_week' => Appointment::whereBetween('preferred_date', [now()->startOfWeek(), now()->endOfWeek()])->count(),
        ];

        return inertia('Appointments/Index', [
            'appointments' => $appointments,
            'summary'      => $summary,
            'filters'      => compact('search', 'status', 'date', 'service'),
        ]);
    }

    public function confirm(Appointment $appointment)
    {
        if ($appointment->status !== 'pending') {
            return back()->withErrors(['error' => 'Only pending appointments can be confirmed.']);
        }

        $appointment->update([
            'status'       => 'confirmed',
            'confirmed_by' => Auth::id(),
            'confirmed_at' => now(),
        ]);

        return back()->with('success', 'Appointment confirmed for ' . $appointment->patient_name . '.');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        if (in_array($appointment->status, ['completed', 'cancelled'])) {
            return back()->withErrors(['error' => 'This appointment cannot be cancelled.']);
        }

        $request->validate([
            'cancellation_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $appointment->update([
            'status'              => 'cancelled',
            'cancelled_by'        => Auth::id(),
            'cancelled_at'        => now(),
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        return back()->with('success', 'Appointment cancelled.');
    }

    public function complete(Appointment $appointment)
    {
        if ($appointment->status === 'cancelled') {
            return back()->withErrors(['error' => 'Cannot complete a cancelled appointment.']);
        }

        $appointment->update(['status' => 'completed']);

        return back()->with('success', 'Appointment marked as completed.');
    }

    public function noShow(Appointment $appointment)
    {
        if ($appointment->status === 'cancelled') {
            return back()->withErrors(['error' => 'Cannot mark a cancelled appointment as no-show.']);
        }

        $appointment->update(['status' => 'no_show']);

        return back()->with('success', 'Appointment marked as no-show.');
    }

    public function updateNotes(Request $request, Appointment $appointment)
    {
        $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $appointment->update(['admin_notes' => $request->admin_notes]);

        return back()->with('success', 'Notes updated.');
    }
}
