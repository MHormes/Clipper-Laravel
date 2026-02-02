<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Out of Gas | Clipper MS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
            background-color: #0a0a0a;
        }

        .orange-glow {
            box-shadow: 0 0 50px -12px #f53003;
        }
    </style>
</head>

<body class="flex min-h-screen items-center justify-center p-6 text-[#ededec]">
    <div class="relative w-full max-w-lg text-center">

        <div
            class="absolute -top-24 left-1/2 -z-10 h-64 w-64 -translate-x-1/2 rounded-full bg-[#f53003]/20 blur-[100px]">
        </div>

        <div class="space-y-8">
            <div class="mx-auto w-fit overflow-hidden rounded-3xl border border-white/10 bg-[#161615] p-2 orange-glow">
                <img src="https://media4.giphy.com/media/v1.Y2lkPTc5MGI3NjExZDEweDA3OGNrbDlheGtqMHR4bnQycDdxMnQyMXlxbjlvOThham55OCZlcD12MV9pbnRlcm5hbF9naWZfYnlfaWQmY3Q9Zw/h5iRoclpg4XI96nG56/giphy.gif"
                    alt="Lighter failing to spark" class="h-96 w-96 rounded-2xl object-cover" />
            </div>

            <div class="space-y-2">
                <h1 class="text-6xl font-black tracking-tighter text-white">500</h1>
                <h2 class="text-2xl font-bold tracking-tight text-[#f53003]">We ran out of gas.</h2>
                <p class="mx-auto max-w-xs text-[#706f6c]">
                    Our servers are flicking the flint but nothing is catching. We're refilling the tank right now.
                </p>
            </div>

            <div class="pt-4">
                <a href="/"
                    class="inline-flex items-center justify-center rounded-xl bg-[#f53003] px-8 py-4 text-sm font-bold text-white transition-all hover:scale-105 hover:bg-[#ff4433] active:scale-95 shadow-lg shadow-orange-500/20">
                    Back to the home page
                </a>
            </div>

            <div class="flex justify-center gap-2 pt-8 opacity-20">
                <div class="h-1 w-8 rounded-full bg-white"></div>
                <div class="h-1 w-2 rounded-full bg-[#f53003]"></div>
            </div>
        </div>
    </div>
</body>

</html>