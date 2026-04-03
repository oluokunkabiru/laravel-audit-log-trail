<?php

namespace Oluokunkabiru\AuditTrail\Http\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Layout('audit-trail::dashboard-layout')]
class Dashboard extends Component
{
    public array $models = [];
    public string $activeTab = 'models';
    public array $settings = [
        'driver' => 'database',
        'queue_enabled' => false,
        'keep_days' => 365,
    ];

    public function mount()
    {
        if (request()->has('tab') && request()->query('tab') === 'settings') {
            $this->activeTab = 'settings';
        }

        $this->loadModels();
        $this->loadSettings();
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    protected function loadModels()
    {
        $modelsPath = app_path('Models');
        $this->models = [];

        if (!File::exists($modelsPath)) {
            return;
        }

        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            $path = $file->getRealPath();
            $content = file_get_contents($path);

            $namespace = 'App\\Models';
            if (preg_match('/namespace\s+(.+?);/', $content, $matches)) {
                $namespace = trim($matches[1]);
            }

            $class = $file->getFilenameWithoutExtension();
            $fqcn = $namespace . '\\' . $class;

            if (class_exists($fqcn)) {
                $hasTrait = in_array(
                    \Oluokunkabiru\AuditTrail\Traits\HasAuditTrail::class,
                    class_uses_recursive($fqcn)
                );

                $this->models[] = [
                    'name' => $class,
                    'fqcn' => $fqcn,
                    'path' => $path,
                    'has_trait' => $hasTrait,
                ];
            }
        }
    }

    public function toggleAudit(string $fqcn)
    {
        $model = collect($this->models)->firstWhere('fqcn', $fqcn);
        
        if (!$model) {
            $this->dispatch('audit-trail:toast', type: 'error', message: "Model not found.");
            return;
        }

        $content = file_get_contents($model['path']);
        
        if ($model['has_trait']) {
            // Remove trait
            $content = str_replace("use \Oluokunkabiru\AuditTrail\Traits\HasAuditTrail;\n", "", $content);
            $content = str_replace("use Oluokunkabiru\AuditTrail\Traits\HasAuditTrail;\n", "", $content);
            $content = preg_replace('/[ \t]*use\s+HasAuditTrail;[\r\n]*/', '', $content);
            $message = "Audit trait removed from {$model['name']}.";
        } else {
            // Add trait
            $import = "use Oluokunkabiru\AuditTrail\Traits\HasAuditTrail;";
            
            // Insert import after namespace
            if (!str_contains($content, $import)) {
                $content = preg_replace('/(namespace\s+[^;]+;)/', "$1\n\n{$import}", $content);
            }

            // Insert use HasAuditTrail inside class
            $className = class_basename($fqcn);
            $pattern = "/(class\s+{$className}[^{]*{)/";
            $content = preg_replace($pattern, "$1\n    use HasAuditTrail;\n", $content);
            $message = "Audit trait enabled for {$model['name']}.";
        }

        file_put_contents($model['path'], $content);
        $this->loadModels();
        
        $this->dispatch('audit-trail:toast', type: 'success', message: $message);
    }

    protected function loadSettings()
    {
        $this->settings['driver'] = config('audit.driver', 'database');
        $this->settings['queue_enabled'] = config('audit.queue.enabled', false);
        $this->settings['keep_days'] = config('audit.prune.keep_days', 365);
    }

    public function saveSettings()
    {
        $map = [
            'AUDIT_DRIVER' => $this->settings['driver'],
            'AUDIT_QUEUE_ENABLED' => $this->settings['queue_enabled'] ? 'true' : 'false',
            'AUDIT_KEEP_DAYS' => $this->settings['keep_days'],
        ];

        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->dispatch('audit-trail:toast', type: 'error', message: '.env file not found.');
            return;
        }

        $env = file_get_contents($envPath);

        foreach ($map as $key => $value) {
            $line = "{$key}={$value}";
            if (preg_match("/^{$key}=.*/m", $env)) {
                $env = preg_replace("/^{$key}=.*/m", $line, $env);
            } else {
                $env .= "\n{$line}";
            }
        }

        file_put_contents($envPath, $env);

        if (function_exists('artisan')) {
            \Artisan::call('config:clear');
        }

        $this->dispatch('audit-trail:toast', type: 'success', message: 'Settings saved successfully.');
    }

    public function render()
    {
        return view('audit-trail::livewire.dashboard');
    }
}
