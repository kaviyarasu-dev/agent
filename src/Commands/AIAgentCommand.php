<?php

declare(strict_types=1);

namespace Kaviyarasu\AIAgent\Commands;

use Illuminate\Console\Command;

class AIAgentCommand extends Command
{
    public $signature = 'ai-agent:install';

    public $description = 'Install the AI Agent package';

    public function handle(): int
    {
        $this->info('Installing AI Agent package...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'ai-agent-config',
            '--force' => true,
        ]);

        $this->info('Publishing migration...');
        $this->call('vendor:publish', [
            '--tag' => 'ai-agent-migrations',
        ]);

        $this->info('AI Agent has been installed successfully!');
        $this->newLine();

        $this->comment('Please update your .env file with the following:');
        $this->comment('CLAUDE_API_KEY=your-claude-api-key');
        $this->comment('OPENAI_API_KEY=your-openai-api-key');
        $this->comment('IDEOGRAM_API_KEY=your-ideogram-api-key');

        return self::SUCCESS;
    }
}
