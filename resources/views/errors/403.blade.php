<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Restricted Access | Clipper MS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #0a0a0a;
        }

        .danger-glow {
            box-shadow: 0 0 50px -12px #ef4444;
        }
    </style>
</head>

<body class="flex min-h-screen items-center justify-center p-6 text-[#ededec]">
    <div class="relative w-full max-w-lg text-center">
        <div class="absolute -top-24 left-1/2 -z-10 h-64 w-64 -translate-x-1/2 rounded-full bg-red-500/10 blur-[100px]">
        </div>

        <div class="space-y-8">
            <div class="mx-auto w-fit overflow-hidden rounded-3xl border border-white/10 bg-[#161615] p-2 danger-glow">
                <img src="https://media1.giphy.com/media/v1.Y2lkPTc5MGI3NjExdzU1cXpxNjZ5b3psNHQwbGRlNWthd2tjZXJ6eHowZHhiYzBkcXdhayZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/EIbNk3GZnHYOa9Zfz5/giphy.gif"
                    alt="Stop right there" class="h-96 w-96 rounded-2xl object-cover" />
            </div>

            <div class="space-y-2">
                <h1 class="text-6xl font-black tracking-tighter text-white">403</h1>
                <h2 class="text-2xl font-bold tracking-tight text-red-500">Not your flint.</h2>
                <p class="mx-auto max-w-xs text-[#706f6c]">
                    You don't have the permissions to spark this page. If you feel this is uncorrect, please contact the
                    site admin.
                </p>
                <br />
                <p class="mx-auto max-w-xs text-[#706f6c]">
                    Logging in again might fix this.
                </p>
            </div>

            <div class="pt-4">
                <a href="/dashboard"
                    class="inline-flex items-center justify-center rounded-xl border border-white/10 bg-white/5 px-8 py-4 text-sm font-bold text-white transition-all hover:bg-white/10 active:scale-95">
                    Return to safety
                </a>
            </div>
        </div>
    </div>
</body>

</html>