# Changelog

All notable changes to `ai-agent` will be documented in this file.

## 2.0.0 - 2024-01-XX

### Changed
- Migrated package architecture to follow Spatie package skeleton standards
- Renamed namespaces from `App\AI` to `WebsiteLearners\AIAgent`
- Restructured all source files under `src/` directory
- Updated service provider to extend Spatie's `PackageServiceProvider`
- Consolidated configuration into single `ai-agent.php` file
- Improved test structure using Pest PHP

### Added
- PassportRedirectTrait for Laravel Passport integration
- Comprehensive GitHub Actions workflows
- Better configuration options for modules
- Support for fallback providers
- Rate limiting and caching features

### Fixed
- SOLID principle compliance throughout the codebase
- Proper dependency injection patterns
- Service registration and binding

## 1.0.0 - 2024-01-01

- Initial release
- Multi-provider support (Claude, OpenAI, Ideogram)
- Text, Image, and Video service interfaces
- Storyboard module with Character and Shot services
