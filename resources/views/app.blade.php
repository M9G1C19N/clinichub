<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ClinicHub — St. Peter Diagnostics</title>
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
    <style>
        @media print {
            /* Hide everything except the print area */
            body * { visibility: hidden; }
            #print-area, #print-area * { visibility: visible; }
            #print-area {
                position: fixed;
                top: 0; left: 0;
                width: 100%;
                padding: 0;
                margin: 0;
            }
            /* Hide sidebar, topbar, buttons */
            aside, header, nav, button, a[href], .no-print { display: none !important; }
        }
    </style>
</head>
<body class="antialiased bg-slate-50">
    @inertia
</body>
</html>
