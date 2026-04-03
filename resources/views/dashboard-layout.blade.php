<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50 antialiased dark:bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Audit Trail Dashboard</title>

    <!-- Tailwind CSS (via CDN for standalone generic usage) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        }
                    }
                }
            }
        }
    </script>
</head>
<body class="h-full text-slate-800 dark:text-slate-200">
    
    {{ $slot }}

    <!-- Livewire Scripts -->
    @livewireScriptConfig

    <!-- Toast Notification Logic -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col gap-3"></div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('audit-trail:toast', (event) => {
                const toastStr = event.length ? event[0] : event;
                const { type, message } = toastStr;
                
                const container = document.getElementById('toast-container');
                const toast = document.createElement('div');
                toast.className = `transform transition-all duration-300 translate-y-10 opacity-0 flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg border text-sm font-medium ${
                    type === 'success' 
                        ? 'bg-white dark:bg-slate-900 border-green-200 dark:border-green-900/30 text-slate-800 dark:text-white' 
                        : 'bg-white dark:bg-slate-900 border-red-200 dark:border-red-900/30 text-slate-800 dark:text-white'
                }`;

                const icon = type === 'success' 
                    ? '<div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/50 flex items-center justify-center text-green-600 dark:text-green-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>'
                    : '<div class="w-8 h-8 rounded-full bg-red-100 dark:bg-red-900/50 flex items-center justify-center text-red-600 dark:text-red-400"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></div>';

                toast.innerHTML = `${icon} <div>${message}</div>`;
                container.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-y-10', 'opacity-0');
                    toast.classList.add('translate-y-0', 'opacity-100');
                }, 10);

                // Animate out
                setTimeout(() => {
                    toast.classList.remove('translate-y-0', 'opacity-100');
                    toast.classList.add('translate-y-2', 'opacity-0');
                    setTimeout(() => toast.remove(), 300);
                }, 4000);
            });
        });
    </script>
</body>
</html>
