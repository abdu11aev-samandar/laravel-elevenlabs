# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2025-01-14

### Added
- New endpoints for AI service: signed-url, widget, knowledge base docs, RAG, tools, MCP, and dashboard endpoints.
- New endpoints for audio service: createAudioNativeProject and getAudioNativeSettings.
- New endpoints for studio service: chapter and snapshot endpoints, project snapshot, and dubbing transcript retrieval.
- New endpoints for workspace service: getWorkspaceResource, searchWorkspaceGroups, and workspace-level secrets retrieval.
- Added test coverage for new endpoints.
- Added `REAL_API_TEST_GUIDE.md` and `API_TEST_GUIDE.md` for testing guidance.
- Added GitHub workflow for running tests.
- Added comprehensive examples and API coverage summaries.

### Changed
- Updated README with new badges, links, and examples.
- Updated issue templates.
- Refactored core service to read `base_uri` and `timeout` from config.
- Dropped global Content-Type header.

### Fixed
- Fixed conversation audio GET in tests.

## [1.1.0] - 2025-01-13
### Added
- Tests for AIService extra endpoints and WorkspaceService
- Extended README examples
- Added Studio chapters/snapshots tests

### Fixed
- Fixed conversation audio GET in tests

## [1.0.0] - 2025-01-13
### Added
- Initial release with comprehensive ElevenLabs API support
- All major service endpoints (Voice, Audio, AI, Studio, Analytics, Workspace)
- Laravel service provider and facade
- Basic testing framework

[Unreleased]: https://github.com/abdu11aev-samandar/laravel-elevenlabs/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/abdu11aev-samandar/laravel-elevenlabs/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/abdu11aev-samandar/laravel-elevenlabs/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/abdu11aev-samandar/laravel-elevenlabs/releases/tag/v1.0.0

