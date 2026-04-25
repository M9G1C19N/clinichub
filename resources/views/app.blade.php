<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ClinicHub — St. Peter Diagnostics</title>

    <!-- Favicons -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" href="/images/spdl-logo.png">
    <link rel="apple-touch-icon" href="/images/spdl-logo.png">

    <!-- Open Graph / Google Search -->
    <meta name="description" content="Saint Peter Diagnostics and Laboratory — Medical and Dental Clinic in Claver, Surigao del Norte.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Saint Peter Diagnostics and Laboratory">
    <meta property="og:description" content="Medical and Dental Clinic — Surigao-Davao Coastal Road, Brgy. Ladgaron, Claver, Surigao del Norte 8410">
    <meta property="og:image" content="/images/spdl-logo.png">
    <meta property="og:image:alt" content="Saint Peter Diagnostics and Laboratory Logo">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Saint Peter Diagnostics and Laboratory">
    <meta name="twitter:description" content="Medical and Dental Clinic in Claver, Surigao del Norte">
    <meta name="twitter:image" content="/images/spdl-logo.png">

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
