<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Commands;

use Illuminate\Console\Command;
use Kaviyarasu\AIAgent\Factory\ProviderFactory;

class ListProvidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:providers {--capability= : Filter by capability (text, image, video)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all available AI providers and their models';

    /**
     * Execute the console command.
     */
    public function handle(ProviderFactory $providerFactory): int
    {
        $capability = $this->option('capability');

        $this->info('AI Agent Providers and Models');
        $this->info('============================');

        $providers = config('ai-agent.providers', []);

        foreach ($providers as $name => $config) {
            // Skip if capability filter is set and provider doesn't support it
            if ($capability) {
                $providerCapabilities = $this->getProviderCapabilities($config);
                if (! in_array($capability, $providerCapabilities)) {
                    continue;
                }
            }

            $this->newLine();
            $this->line("<fg=yellow>Provider: {$name}</>");
            $this->line("Class: {$config['class']}");
            $this->line("Default Model: {$config['default_model']}");

            try {
                $provider = $providerFactory->create($name);
                $isAvailable = $provider->isAvailable();
                $status = $isAvailable ? '<fg=green>✓ Available</>' : '<fg=red>✗ Not Available (API key missing)</>';
                $this->line("Status: {$status}");
            } catch (\Exception $e) {
                $this->line("Status: <fg=red>✗ Error: {$e->getMessage()}</>");
            }

            $this->newLine();
            $this->line('<fg=cyan>Models:</>');

            $models = $config['models'] ?? [];
            foreach ($models as $modelKey => $modelConfig) {
                $this->line("  • {$modelKey}");
                $this->line("    Name: {$modelConfig['name']}");
                $this->line("    Version: {$modelConfig['version']}");
                $this->line('    Capabilities: '.implode(', ', $modelConfig['capabilities'] ?? []));

                if (isset($modelConfig['max_tokens'])) {
                    $this->line("    Max Tokens: {$modelConfig['max_tokens']}");
                }

                if (isset($modelConfig['supports_streaming'])) {
                    $streaming = $modelConfig['supports_streaming'] ? 'Yes' : 'No';
                    $this->line("    Streaming: {$streaming}");
                }

                if (isset($modelConfig['supports_functions'])) {
                    $functions = $modelConfig['supports_functions'] ? 'Yes' : 'No';
                    $this->line("    Functions: {$functions}");
                }

                // Additional model-specific info
                if (isset($modelConfig['sizes'])) {
                    $this->line('    Sizes: '.implode(', ', $modelConfig['sizes']));
                }

                if (isset($modelConfig['styles'])) {
                    $this->line('    Styles: '.implode(', ', $modelConfig['styles']));
                }

                $this->newLine();
            }
        }

        // Show default providers
        $this->line('<fg=yellow>Default Providers by Capability:</>');
        $defaults = config('ai-agent.default_providers', []);
        foreach ($defaults as $cap => $provider) {
            $this->line("  • {$cap}: {$provider}");
        }

        // Show model selection strategies
        $this->newLine();
        $this->line('<fg=yellow>Model Selection Strategies:</>');
        $strategies = config('ai-agent.model_selection.strategies', []);
        $defaultStrategy = config('ai-agent.model_selection.default_strategy', 'balanced');

        foreach ($strategies as $strategy => $models) {
            $isDefault = $strategy === $defaultStrategy ? ' (default)' : '';
            $this->line("  • {$strategy}{$isDefault}:");
            foreach ($models as $capability => $modelList) {
                $this->line("    - {$capability}: ".implode(', ', $modelList));
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Get all capabilities from provider models
     */
    private function getProviderCapabilities(array $config): array
    {
        $capabilities = [];
        $models = $config['models'] ?? [];

        foreach ($models as $modelConfig) {
            $capabilities = array_merge($capabilities, $modelConfig['capabilities'] ?? []);
        }

        return array_unique($capabilities);
    }
}
