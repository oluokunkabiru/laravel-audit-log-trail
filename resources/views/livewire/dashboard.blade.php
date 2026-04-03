<div class="h-full flex flex-col">
    
    @include('audit-trail::partials.header')

    <div class="flex flex-1 overflow-hidden">
        
        @include('audit-trail::partials.sidebar')

        <main class="flex-1 overflow-y-auto bg-slate-50 dark:bg-slate-950 p-6 lg:p-10">
            <div class="max-w-7xl mx-auto min-h-full flex flex-col">
                <div class="flex-1">

        <!-- MODELS TAB -->
        @if($activeTab === 'models')
        <div>
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Model Management</h1>
                <p class="mt-1 text-slate-500 dark:text-slate-400">Discover Eloquent models and toggle their audit trail tracking with a single click.</p>
            </div>

            @if(empty($models))
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <div class="w-20 h-20 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                        <svg class="w-10 h-10 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-700 dark:text-slate-300">No models found</h3>
                    <p class="text-sm text-slate-500 dark:text-slate-500 mt-1 max-w-sm">
                        Make sure your models are in <code class="text-brand-600 dark:text-brand-400">app/Models</code>.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($models as $model)
                    <div class="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm hover:shadow-md hover:border-brand-400 dark:hover:border-brand-600 transition-all duration-200 flex flex-col justify-between">
                        
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shadow-sm group-hover:scale-105 transition-transform 
                                {{ $model['has_trait'] ? 'bg-gradient-to-br from-brand-400 to-brand-600' : 'bg-slate-100 dark:bg-slate-800' }}">
                                <svg class="w-5 h-5 {{ $model['has_trait'] ? 'text-white' : 'text-slate-400 dark:text-slate-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                                    {{ $model['name'] }}
                                    @if($model['has_trait'])
                                        <span class="flex h-2.5 w-2.5 relative">
                                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand-400 opacity-75"></span>
                                          <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-brand-500"></span>
                                        </span>
                                    @endif
                                </h3>
                                <p class="text-xs text-slate-400 dark:text-slate-500 font-mono truncate" title="{{ $model['fqcn'] }}">
                                    {{ $model['fqcn'] }}
                                </p>
                            </div>
                        </div>

                        <button wire:click="toggleAudit('{{ addslashes($model['fqcn']) }}')"
                            wire:loading.attr="disabled"
                            wire:target="toggleAudit('{{ addslashes($model['fqcn']) }}')"
                            class="w-full h-10 rounded-xl text-sm font-semibold shadow-sm disabled:opacity-60 disabled:cursor-not-allowed transition-all hover:shadow-md flex items-center justify-center gap-2
                            {{ $model['has_trait'] 
                                ? 'bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 border border-red-200 dark:border-red-900/30' 
                                : 'bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white border border-transparent' }}">
                            
                            <svg wire:loading wire:target="toggleAudit('{{ addslashes($model['fqcn']) }}')" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            
                            <span wire:loading.remove wire:target="toggleAudit('{{ addslashes($model['fqcn']) }}')">
                                {{ $model['has_trait'] ? 'Disable Audit' : 'Enable Audit' }}
                            </span>
                            <span wire:loading wire:target="toggleAudit('{{ addslashes($model['fqcn']) }}')">Processing…</span>
                        </button>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        <!-- SETTINGS TAB -->
        @if($activeTab === 'settings')
        <div>
            <div class="mb-8">
                <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Global Settings</h1>
                <p class="mt-1 text-slate-500 dark:text-slate-400">Configure core behaviors for the audit trail package. These settings are saved directly to your <code class="text-brand-600 dark:text-brand-400">.env</code> file.</p>
            </div>

            <div class="max-w-2xl">
                <form wire:submit.prevent="saveSettings" class="space-y-6">

                    <!-- Storage Driver -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 dark:text-blue-400 text-xs">💾</span>
                            Storage
                        </h2>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Audit Driver</label>
                            <select wire:model="settings.driver"
                                class="w-full h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent transition-all">
                                <option value="database">Database (Default)</option>
                                <option value="file">File System</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1.5">The primary engine used for storing audit logs.</p>
                        </div>
                    </div>

                    <!-- Performance -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 dark:text-purple-400 text-xs">⚡</span>
                            Performance
                        </h2>
                        
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" wire:model="settings.queue_enabled" class="peer sr-only">
                            <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-500 relative"></div>
                            <div>
                                <div class="text-sm font-medium text-slate-900 dark:text-white">Queue Enabled</div>
                                <div class="text-xs text-slate-500">Dispatch audit logging to background queues</div>
                            </div>
                        </label>
                    </div>

                    <!-- Maintenance -->
                    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h2 class="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                            <span class="w-6 h-6 rounded-md bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center text-orange-600 dark:text-orange-400 text-xs">🧹</span>
                            Maintenance
                        </h2>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5">Prune After (Days)</label>
                            <input type="number" wire:model="settings.keep_days" min="1" max="3650"
                                class="w-40 h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-transparent transition-all">
                            <p class="text-[11px] text-slate-400 mt-1.5">Number of days before pruning old logs (via artisan command)</p>
                        </div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full h-11 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white text-sm font-semibold shadow-sm disabled:opacity-60 disabled:cursor-not-allowed transition-all hover:shadow-md hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 mt-4">
                        
                        <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        
                        <span wire:loading.remove>Save Configuration</span>
                        <span wire:loading>Saving…</span>
                    </button>
                </form>
            </div>
        </div>
        @endif

                </div>

            </div>
        </main>
    </div>
</div>
