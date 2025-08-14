# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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


