# Migration Plan: Converting AI Agent Package to Spatie Package Skeleton Architecture

## Executive Summary

This document outlines a comprehensive plan to migrate the current Laravel AI Agent package (`WebsiteLearners/ai-agent`) to follow the Spatie package skeleton architecture while preserving all existing functionality and maintaining backward compatibility.

## Current State Analysis

### Existing Package Structure
```
WebsiteLearners/ai-agent/
├── app/                    # Application code (non-standard for packages)
│   ├── AI/
│   │   ├── Config/
│   │   ├── Contracts/
│   │   ├── Factory/
│   │   ├── Providers/
│   │   └── Services/
│   └── Providers/
│       └── AIServiceProvider.php
├── config/
│   ├── ai.php
│   └── ai-agent.php
├── src/                    # Current package source (minimal)
│   ├── AI.php
│   ├── AIServiceProvider.php
│   ├── Commands/
│   └── Facades/
├── tests/
├── examples/
└── database/
```

### Key Components to Preserve
1. **AI Service Architecture**: Multi-provider support (Claude, OpenAI, Ideogram)
2. **SOLID Principles**: Clean architecture with interfaces and implementations
3. **Module System**: Storyboard module with Character and Shot services
4. **Configuration**: Environment-based provider selection
5. **Service Bindings**: Dependency injection configurations

### Missing Component
- **PassportRedirectTrait.php**: Not found in current codebase (needs clarification)

## Target Architecture (Spatie Skeleton)

### Expected Structure
```
WebsiteLearners/ai-agent/
├── config/                 # Package configuration
│   └── ai-agent.php
├── database/              # Migrations and factories
│   ├── factories/
│   └── migrations/
├── resources/             # Views, lang files
│   └── views/
├── src/                   # All package source code
│   ├── Commands/
│   ├── Contracts/
│   ├── Exceptions/
│   ├── Facades/
│   ├── Http/              # If needed for API endpoints
│   ├── Models/            # If database models needed
│   ├── Providers/
│   ├── Services/
│   ├── Traits/            # For PassportRedirectTrait
│   ├── AI.php            # Main package class
│   └── AIAgentServiceProvider.php
├── tests/                 # Comprehensive test suite
├── workbench/            # Development environment
└── composer.json
```

## Implementation Plan

### Phase 1: Preparation and Setup (Day 1)

#### 1.1 Backup Current State
#### 1.3 Migration Branch
```bash
git checkout -b feature/change-architechture
```

### Phase 2: Restructure Codebase (Day 2-3)

#### 2.1 Move Core Components
```bash
# Create new directory structure
mkdir -p src/{Contracts,Providers,Services,Config,Factory,Traits,Exceptions,Models}

# Move existing app/AI components to src/
mv app/AI/Contracts/* src/Contracts/
mv app/AI/Providers/* src/Providers/AI/
mv app/AI/Services/* src/Services/
mv app/AI/Config/* src/Config/
mv app/AI/Factory/* src/Factory/
```

#### 2.2 Update Namespaces
From: `App\AI\*`
To: `WebsiteLearners\AIAgent\*`

Example transformation:
```php
// Before
namespace App\AI\Contracts\Services;

// After
namespace WebsiteLearners\AIAgent\Contracts\Services;
```

#### 2.3 Create PassportRedirectTrait (if needed)
```php
// src/Traits/PassportRedirectTrait.php
<?php

namespace WebsiteLearners\AIAgent\Traits;

trait PassportRedirectTrait
{
    /**
     * Get the redirect URL for Passport authentication
     */
    public function getPassportRedirectUrl(): string
    {
        return config('ai-agent.passport.redirect_url', '/home');
    }

    /**
     * Handle Passport redirect logic
     */
    public function handlePassportRedirect($request)
    {
        // Implementation based on requirements
    }
}
```

### Phase 3: Update Service Provider (Day 4)

#### 3.1 Refactor Service Provider
```php
// src/AIAgentServiceProvider.php
<?php

namespace WebsiteLearners\AIAgent;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use WebsiteLearners\AIAgent\Commands\AIAgentCommand;

class AIAgentServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-agent')
            ->hasConfigFile(['ai-agent', 'ai'])
            ->hasViews()
            ->hasMigration('create_ai_agent_table')
            ->hasCommand(AIAgentCommand::class);
    }

    public function packageRegistered(): void
    {
        // Register singletons
        $this->app->singleton(Config\AIConfigManager::class);
        
        $this->app->singleton(Factory\ProviderFactory::class, function ($app) {
            return new Factory\ProviderFactory(
                $app->make(Config\AIConfigManager::class)
            );
        });
        
        // Register service bindings
        $this->registerServices();
        
        // Register module services
        $this->registerModuleServices();
    }

    protected function registerServices(): void
    {
        $this->app->bind(
            Contracts\Services\TextServiceInterface::class,
            Services\Core\TextService::class
        );
        
        $this->app->bind(
            Contracts\Services\ImageServiceInterface::class,
            Services\Core\ImageService::class
        );
        
        $this->app->bind(
            Contracts\Services\VideoServiceInterface::class,
            Services\Core\VideoService::class
        );
    }

    protected function registerModuleServices(): void
    {
        // Storyboard Character Service
        $this->app->when(Services\Modules\Storyboard\CharacterService::class)
            ->needs(Contracts\Services\TextServiceInterface::class)
            ->give(function ($app) {
                $service = $app->make(Services\Core\TextService::class);
                if (config('ai-agent.storyboard.character_provider')) {
                    $service->setProvider(config('ai-agent.storyboard.character_provider'));
                }
                return $service;
            });
    }
}
```

### Phase 4: Update Configuration (Day 5)

#### 4.1 Merge Configuration Files
```php
// config/ai-agent.php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Agent Configuration
    |--------------------------------------------------------------------------
    */
    
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'claude'),
    
    'providers' => [
        'claude' => [
            'api_key' => env('CLAUDE_API_KEY'),
            'models' => require __DIR__ . '/providers/claude.php',
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'models' => require __DIR__ . '/providers/openai.php',
        ],
        'ideogram' => [
            'api_key' => env('IDEOGRAM_API_KEY'),
            'models' => require __DIR__ . '/providers/ideogram.php',
        ],
    ],
    
    'modules' => [
        'storyboard' => [
            'character_provider' => env('STORYBOARD_CHARACTER_PROVIDER'),
            'shot_provider' => env('STORYBOARD_SHOT_PROVIDER'),
        ],
    ],
    
    'features' => [
        'rate_limiting' => [
            'enabled' => env('AI_RATE_LIMITING_ENABLED', true),
            'requests_per_minute' => env('AI_RATE_LIMIT_PER_MINUTE', 60),
        ],
        'cache' => [
            'enabled' => env('AI_CACHE_ENABLED', true),
            'ttl' => env('AI_CACHE_TTL', 3600),
            'store' => env('AI_CACHE_STORE', 'redis'),
        ],
        'logging' => [
            'enabled' => env('AI_LOGGING_ENABLED', true),
            'channel' => env('AI_LOG_CHANNEL', 'ai'),
        ],
    ],
    
    'passport' => [
        'enabled' => env('AI_PASSPORT_ENABLED', false),
        'redirect_url' => env('AI_PASSPORT_REDIRECT_URL', '/home'),
    ],
];
```

### Phase 5: Update Composer Configuration (Day 6)

#### 5.1 Update composer.json
```json
{
    "name": "websitelearners/ai-agent",
    "description": "A Laravel package for AI agent with multi-provider support",
    "keywords": [
        "ai",
        "agent",
        "laravel",
        "ai-agent",
        "claude",
        "openai",
        "ideogram"
    ],
    "homepage": "https://github.com/websitelearners/ai-agent",
    "license": "MIT",
    "authors": [
        {
            "name": "websitelearners",
            "email": "your-email@example.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.1",
        "spatie/laravel-package-tools": "^1.16",
        "illuminate/contracts": "^10.0||^11.0"
    },
    "require-dev": {
        "laravel/pint": "^1.14",
        "nunomaduro/collision": "^8.1.1||^7.10.0",
        "larastan/larastan": "^2.9",
        "orchestra/testbench": "^9.0.0||^8.22.0",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-arch": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "phpstan/extension-installer": "^1.3",
        "phpstan/phpstan-deprecation-rules": "^1.1",
        "phpstan/phpstan-phpunit": "^1.3"
    },
    "autoload": {
        "psr-4": {
            "WebsiteLearners\\AIAgent\\": "src/",
            "WebsiteLearners\\AIAgent\\Database\\Factories\\": "database/factories/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "WebsiteLearners\\AIAgent\\Tests\\": "tests/",
            "Workbench\\App\\": "workbench/app/"
        }
    },
    "scripts": {
        "post-autoload-dump": "@composer run prepare",
        "prepare": "@php vendor/bin/testbench package:discover --ansi",
        "analyse": "vendor/bin/phpstan analyse",
        "test": "vendor/bin/pest",
        "test-coverage": "vendor/bin/pest --coverage",
        "format": "vendor/bin/pint"
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "phpstan/extension-installer": true
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "WebsiteLearners\\AIAgent\\AIAgentServiceProvider"
            ],
            "aliases": {
                "AIAgent": "WebsiteLearners\\AIAgent\\Facades\\AIAgent"
            }
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

### Phase 6: Create Facade (Day 7)

#### 6.1 Update Main Package Class
```php
// src/AIAgent.php
<?php

namespace WebsiteLearners\AIAgent;

use WebsiteLearners\AIAgent\Factory\ServiceFactory;

class AIAgent
{
    public function __construct(
        protected ServiceFactory $serviceFactory
    ) {}

    public function text(): Contracts\Services\TextServiceInterface
    {
        return $this->serviceFactory->createTextService();
    }

    public function image(): Contracts\Services\ImageServiceInterface
    {
        return $this->serviceFactory->createImageService();
    }

    public function video(): Contracts\Services\VideoServiceInterface
    {
        return $this->serviceFactory->createVideoService();
    }

    public function provider(string $name): self
    {
        $this->serviceFactory->setDefaultProvider($name);
        return $this;
    }
}
```

#### 6.2 Create Facade
```php
// src/Facades/AIAgent.php
<?php

namespace WebsiteLearners\AIAgent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \WebsiteLearners\AIAgent\Contracts\Services\TextServiceInterface text()
 * @method static \WebsiteLearners\AIAgent\Contracts\Services\ImageServiceInterface image()
 * @method static \WebsiteLearners\AIAgent\Contracts\Services\VideoServiceInterface video()
 * @method static \WebsiteLearners\AIAgent\AIAgent provider(string $name)
 * 
 * @see \WebsiteLearners\AIAgent\AIAgent
 */
class AIAgent extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \WebsiteLearners\AIAgent\AIAgent::class;
    }
}
```

### Phase 7: Testing Migration (Day 8-9)

#### 7.1 Update Existing Tests
```php
// tests/TestCase.php
<?php

namespace WebsiteLearners\AIAgent\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use WebsiteLearners\AIAgent\AIAgentServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'WebsiteLearners\\AIAgent\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            AIAgentServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('ai-agent.default_provider', 'claude');
        
        // Migration would go here if needed
        // $migration = include __DIR__.'/../database/migrations/create_ai_agent_table.php.stub';
        // $migration->up();
    }
}
```

#### 7.2 Create Architecture Tests
```php
// tests/ArchTest.php
<?php

use WebsiteLearners\AIAgent\Contracts;
use WebsiteLearners\AIAgent\Services;

it('ensures contracts are interfaces')
    ->expect('WebsiteLearners\AIAgent\Contracts')
    ->toBeInterfaces();

it('ensures services implement their contracts')
    ->expect('WebsiteLearners\AIAgent\Services\Core')
    ->toImplement('WebsiteLearners\AIAgent\Contracts');

it('follows naming conventions')
    ->expect('WebsiteLearners\AIAgent\Services')
    ->classes()
    ->toHaveSuffix('Service');
```

### Phase 8: Documentation Update (Day 10)

#### 8.1 Update README.md
```markdown
# AI Agent for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/WebsiteLearners/ai-agent.svg?style=flat-square)](https://packagist.org/packages/WebsiteLearners/ai-agent)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/WebsiteLearners/ai-agent/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/WebsiteLearners/ai-agent/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/WebsiteLearners/ai-agent/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/WebsiteLearners/ai-agent/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/WebsiteLearners/ai-agent.svg?style=flat-square)](https://packagist.org/packages/WebsiteLearners/ai-agent)

A flexible, modular AI service architecture for Laravel that supports multiple AI providers (Claude, OpenAI, Ideogram) with easy switching via configuration.

## Installation

You can install the package via composer:

```bash
composer require WebsiteLearners/ai-agent
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="ai-agent-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="ai-agent-config"
```

## Usage

```php
use WebsiteLearners\AIAgent\Facades\AIAgent;

// Text generation
$response = AIAgent::text()->generateText('Write a story about a robot');

// Image generation
$imageUrl = AIAgent::image()->generateImage('A futuristic city at sunset');

// Switch provider at runtime
$response = AIAgent::provider('openai')->text()->generateText('Hello world');

// Using PassportRedirectTrait
use WebsiteLearners\AIAgent\Traits\PassportRedirectTrait;

class YourController extends Controller
{
    use PassportRedirectTrait;
    
    public function handleAuth()
    {
        return redirect($this->getPassportRedirectUrl());
    }
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [WebsiteLearners](https://github.com/WebsiteLearners)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
```

### Phase 9: Migration Scripts (Day 11)

#### 9.1 Create Migration Script
```php
// scripts/migrate-to-spatie.php
#!/usr/bin/env php
<?php

echo "Starting migration to Spatie package skeleton...\n";

// Namespace updates
$namespaceMap = [
    'App\\AI\\' => 'WebsiteLearners\\AIAgent\\',
    'WebsiteLearners\\AI\\' => 'WebsiteLearners\\AIAgent\\',
    'VendorName\\Skeleton\\' => 'WebsiteLearners\\AIAgent\\',
];

// Directory mapping
$directoryMap = [
    'app/AI' => 'src',
    'app/Providers/AIServiceProvider.php' => 'src/AIAgentServiceProvider.php',
];

// Update namespaces in all PHP files
function updateNamespaces($file, $namespaceMap) {
    $content = file_get_contents($file);
    foreach ($namespaceMap as $old => $new) {
        $content = str_replace($old, $new, $content);
    }
    file_put_contents($file, $content);
}

// Process all PHP files
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        echo "Processing: " . $file->getPathname() . "\n";
        updateNamespaces($file->getPathname(), $namespaceMap);
    }
}

echo "Migration completed!\n";
```

### Phase 10: Quality Assurance (Day 12)

#### 10.1 Run Code Quality Tools
```bash
# Fix code style
vendor/bin/pint

# Run static analysis
vendor/bin/phpstan analyse

# Run tests
vendor/bin/pest

# Generate coverage report
vendor/bin/pest --coverage
```

#### 10.2 Create GitHub Actions
```yaml
# .github/workflows/run-tests.yml
name: run-tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ${{ matrix.os }}
    timeout-minutes: 5
    strategy:
      fail-fast: true
      matrix:
        os: [ubuntu-latest]
        php: [8.1, 8.2, 8.3]
        laravel: [10.*, 11.*]
        stability: [prefer-lowest, prefer-stable]
        include:
          - laravel: 10.*
            testbench: 8.*
          - laravel: 11.*
            testbench: 9.*

    name: P${{ matrix.php }} - L${{ matrix.laravel }} - ${{ matrix.stability }} - ${{ matrix.os }}

    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, soap, intl, gd, exif, iconv, imagick, fileinfo
          coverage: none

      - name: Setup problem matchers
        run: |
          echo "::add-matcher::${{ runner.tool_cache }}/php.json"
          echo "::add-matcher::${{ runner.tool_cache }}/phpunit.json"

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" "orchestra/testbench:${{ matrix.testbench }}" --no-interaction --no-update
          composer update --${{ matrix.stability }} --prefer-dist --no-interaction

      - name: List Installed Dependencies
        run: composer show -D

      - name: Execute tests
        run: vendor/bin/pest --ci
```

## Migration Checklist

### Pre-Migration
- [ ] Backup current codebase
- [ ] Document all custom implementations
- [ ] Identify PassportRedirectTrait requirements
- [ ] Review current test coverage

### During Migration
- [ ] Move app/AI to src/
- [ ] Update all namespaces
- [ ] Refactor service provider
- [ ] Update composer.json
- [ ] Merge configuration files
- [ ] Create/update facades
- [ ] Implement PassportRedirectTrait
- [ ] Update tests

### Post-Migration
- [ ] Run all tests
- [ ] Fix code style issues
- [ ] Run static analysis
- [ ] Update documentation
- [ ] Test in fresh Laravel installation
- [ ] Create migration guide for users
- [ ] Tag new version

## Rollback Plan

If issues arise during migration:

1. **Immediate Rollback**
   ```bash
   git checkout main
   git branch -D feature/spatie-skeleton-migration
   ```

2. **Partial Rollback**
   - Keep beneficial changes (tests, documentation)
   - Revert structural changes
   - Apply fixes incrementally

3. **Communication**
   - Notify users of any breaking changes
   - Provide migration guide
   - Offer support period

## Success Criteria

1. **All tests pass** with 80%+ coverage
2. **No breaking changes** for existing users
3. **Clean static analysis** (PHPStan level 8)
4. **Updated documentation** with examples
5. **Successful installation** in fresh Laravel app
6. **Performance maintained** or improved

## Timeline

- **Days 1-3**: Structure migration
- **Days 4-6**: Code updates and configuration
- **Days 7-9**: Testing and quality assurance
- **Days 10-11**: Documentation and scripts
- **Day 12**: Final review and release

Total estimated time: **12 working days**

## Notes for PassportRedirectTrait

Since PassportRedirectTrait.php was not found in the current codebase, I've included a placeholder implementation in the migration plan. Please provide:

1. The actual implementation or requirements for this trait
2. Its intended use cases
3. Any Passport-specific configurations needed

This will ensure the trait is properly implemented in the new structure.
