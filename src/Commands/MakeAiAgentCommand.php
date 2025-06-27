<?php

declare(strict_types=1);

namespace WebsiteLearners\AIAgent\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use WebsiteLearners\AIAgent\Config\AIConfigManager;

class MakeAiAgentCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai-agent {name}
                            {--capability=text : The AI capability (text, image, video)}
                            {--provider= : The default AI provider (claude, openai, ideogram, etc.)}
                            {--model= : The default AI model}
                            {--with-logging : Include logging trait}
                            {--with-fallback : Include fallback provider trait}
                            {--force : Overwrite existing file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new AI agent class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'AI Agent';

    /**
     * The available AI capabilities and their corresponding services.
     *
     * @var array<string, string>
     */
    protected array $capabilities = [
        'text' => 'TextServiceInterface',
        'image' => 'ImageServiceInterface',
        'video' => 'VideoServiceInterface',
    ];

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $name = $this->ask('Enter agent class (e.g. Blog\BlogAiAgent)');
        $capability = $this->choice(
            'Choose capability',
            array_keys($this->capabilities),
            'text'
        );

        $configManager = app(AIConfigManager::class);
        $allProviders = $configManager->getAllProviders();
        $providers = array_keys($allProviders);

        $provider = config()->get('ai-agent.default_provider');
        if (!empty($providers)) {
            $provider = $this->choice(
                'Choose default provider (optional)',
                $providers,
                config()->get('ai-agent.default_provider')
            );
        }

        $models = array_keys($configManager->getModelsForProvider($provider));
        $model = $this->choice(
            'Choose default provider (optional)',
            $models,
            $allProviders[$provider]['default_model'] ?? null
        );

        $withLogging = $this->confirm('Include logging functionality?', false);
        $withFallback = $this->confirm('Include fallback provider functionality?', false);

        // Set the arguments and options
        $this->input->setArgument('name', $name);
        $this->input->setOption('capability', $capability);
        if ($provider) {
            $this->input->setOption('provider', $provider);
        }
        if ($model) {
            $this->input->setOption('model', $model);
        }
        $this->input->setOption('with-logging', $withLogging);
        $this->input->setOption('with-fallback', $withFallback);

        return parent::handle();
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/../../stubs/ai-agent.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\AI\Agents';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        $name = str_replace('\\', '/', $name);

        // Convert forward slashes to proper namespace format
        if (str_contains($this->argument('name'), '/')) {
            $parts = explode('/', $this->argument('name'));
            $className = array_pop($parts);
            $namespace = implode('\\', array_map('ucfirst', $parts));

            $name = $this->getDefaultNamespace('') . '\\' . $namespace . '\\' . $className;
            $name = str_replace('\\', '/', $name);
        }

        return $this->laravel['path'] . '/' . $name . '.php';
    }

    /**
     * Parse the class name and format according to conventions.
     *
     * @param  string  $name
     * @return string
     */
    protected function qualifyClass($name)
    {
        $name = ltrim($name, '\\/');

        // Handle forward slash notation
        if (str_contains($name, '/')) {
            $parts = explode('/', $name);
            $className = array_pop($parts);

            // Ensure proper casing
            $parts = array_map('ucfirst', $parts);
            $className = ucfirst(Str::studly($className));

            $name = implode('\\', $parts) . '\\' . $className;
        }

        $name = str_replace('/', '\\', $name);

        if (Str::startsWith($name, $rootNamespace = $this->rootNamespace())) {
            return $name;
        }

        return $this->qualifyClass(
            $this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name
        );
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        $capability = $this->option('capability');
        $provider = $this->option('provider');
        $model = $this->option('model');

        // Replace capability-related placeholders
        $serviceInterface = $this->capabilities[$capability];
        $serviceProperty = lcfirst(str_replace('ServiceInterface', 'Service', $serviceInterface));

        $stub = str_replace('{{ serviceInterface }}', $serviceInterface, $stub);
        $stub = str_replace('{{ serviceProperty }}', $serviceProperty, $stub);
        $stub = str_replace('{{ capability }}', $capability, $stub);
        $stub = str_replace('{{ methodSuffix }}', ucfirst($capability), $stub);

        // Replace provider and model
        $stub = str_replace('{{ provider }}', $provider ?: 'config default', $stub);
        $stub = str_replace('{{ model }}', $model ?: 'config default', $stub);

        // Handle provider/model initialization
        $providerInit = '';
        if ($provider) {
            $providerInit .= "\$this->{$serviceProperty}->setProvider('{$provider}');\n";
        }
        if ($model) {
            $providerInit .= "        \$this->{$serviceProperty}->switchModel('{$model}');\n";
        }
        $stub = str_replace('{{ providerInit }}', rtrim($providerInit), $stub);

        // Handle traits
        $traits = [];
        $traitImports = [];

        if ($this->option('with-logging')) {
            $traits[] = 'LogsAIUsage';
            $traitImports[] = 'use App\AI\Traits\LogsAIUsage;';
        }

        if ($this->option('with-fallback')) {
            $traits[] = 'UsesFallbackProvider';
            $traitImports[] = 'use App\AI\Traits\UsesFallbackProvider;';
        }

        $stub = str_replace('{{ traitImports }}', implode("\n", $traitImports), $stub);
        $stub = str_replace('{{ traits }}', !empty($traits) ? 'use ' . implode(', ', $traits) . ';' : '', $stub);

        return $stub;
    }

    /**
     * Validate the provider for the given capability.
     *
     * @param  string  $provider
     * @param  string  $capability
     * @return bool
     */
    protected function validateProvider(string $provider, string $capability): bool
    {
        try {
            $configManager = app(AIConfigManager::class);
            $supportedProviders = $this->getProvidersForCapability($configManager, $capability);

            if (!in_array($provider, $supportedProviders)) {
                $this->error("Provider '$provider' does not support '$capability' capability.");

                // Find similar provider names
                $suggestions = $this->findSimilarProviders($provider, $supportedProviders);
                if (!empty($suggestions)) {
                    $this->error('Did you mean: ' . implode(', ', $suggestions) . '?');
                } else {
                    $this->error('Available providers for ' . $capability . ': ' . implode(', ', $supportedProviders));
                }

                return false;
            }
        } catch (\Exception $e) {
            // If we can't validate (e.g., in testing), allow it
            $this->warn('Could not validate provider. Proceeding anyway.');
        }

        return true;
    }

    /**
     * Get providers that support the given capability.
     *
     * @param  AIConfigManager  $configManager
     * @param  string  $capability
     * @return array
     */
    protected function getProvidersForCapability(AIConfigManager $configManager, string $capability): array
    {
        $providers = [];

        try {
            $allProviders = $configManager->getAllProviders();

            foreach ($allProviders as $providerName => $providerConfig) {
                if (isset($providerConfig['capabilities']) && in_array($capability, $providerConfig['capabilities'])) {
                    $providers[] = $providerName;
                }
            }
        } catch (\Exception $e) {
            // Return default providers if we can't load config
            return ['claude', 'openai', 'ideogram'];
        }

        return $providers;
    }

    /**
     * Find similar provider names using fuzzy matching.
     *
     * @param  string  $input
     * @param  array  $providers
     * @return array
     */
    protected function findSimilarProviders(string $input, array $providers): array
    {
        $suggestions = [];

        foreach ($providers as $provider) {
            $similarity = 0;
            similar_text(strtolower($input), strtolower($provider), $similarity);

            if ($similarity > 70) {
                $suggestions[] = $provider;
            }
        }

        return $suggestions;
    }

    /**
     * Build the class with the given name.
     *
     * @param  string  $name
     * @return string
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = parent::buildClass($name);

        // Ensure the traits directory exists if needed
        if ($this->option('with-logging') || $this->option('with-fallback')) {
            $traitsPath = app_path('AI/Traits');
            if (!$this->files->exists($traitsPath)) {
                $this->files->makeDirectory($traitsPath, 0755, true);
            }

            // Create trait stubs if they don't exist
            $this->createTraitStubs();
        }

        return $stub;
    }

    /**
     * Create trait stubs if they don't exist.
     *
     * @return void
     */
    protected function createTraitStubs(): void
    {
        if ($this->option('with-logging') && !$this->files->exists(app_path('AI/Traits/LogsAIUsage.php'))) {
            $this->createLoggingTrait();
        }

        if ($this->option('with-fallback') && !$this->files->exists(app_path('AI/Traits/UsesFallbackProvider.php'))) {
            $this->createFallbackTrait();
        }
    }

    /**
     * Create the logging trait.
     *
     * @return void
     */
    protected function createLoggingTrait(): void
    {
        $stub = $this->files->get(__DIR__ . '/../../stubs/traits/logs-ai-usage.stub');
        $this->files->put(app_path('AI/Traits/LogsAIUsage.php'), $stub);
        $this->info('Created LogsAIUsage trait.');
    }

    /**
     * Create the fallback provider trait.
     *
     * @return void
     */
    protected function createFallbackTrait(): void
    {
        $stub = $this->files->get(__DIR__ . '/../../stubs/traits/uses-fallback-provider.stub');
        $this->files->put(app_path('AI/Traits/UsesFallbackProvider.php'), $stub);
        $this->info('Created UsesFallbackProvider trait.');
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['capability', null, InputOption::VALUE_OPTIONAL, 'The AI capability (text, image, video)', 'text'],
            ['provider', null, InputOption::VALUE_OPTIONAL, 'The default AI provider'],
            ['model', null, InputOption::VALUE_OPTIONAL, 'The default AI model'],
            ['with-logging', null, InputOption::VALUE_NONE, 'Include logging trait'],
            ['with-fallback', null, InputOption::VALUE_NONE, 'Include fallback provider trait'],
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the AI Agent already exists'],
        ];
    }
}
