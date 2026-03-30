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
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }

        /* Hide the app shell */
        body > div > div > aside,
        body > div > div > div > header,
        .no-print,
        button,
        nav {
            display: none !important;
        }

        /* Make everything invisible first */
        body { background: white !important; }

        /* Show only print area */
        #print-area,
        .print\:block {
            display: block !important;
            visibility: visible !important;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
        }

        /* Hide non-print content */
        #app > * {
            display: none !important;
        }

        /* Show only the print block */
        #app .print\:block {
            display: block !important;
        }
    }
</style>
</head>
<body class="antialiased bg-slate-50">
    @inertia
</body>
</html>
