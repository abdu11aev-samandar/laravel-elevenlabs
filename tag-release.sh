#!/bin/bash

# Script to tag v1.2.0 release after PR merge
# Usage: ./tag-release.sh

set -e

echo "🚀 Preparing to tag v1.2.0 release..."

# Ensure we're on master branch
echo "📍 Switching to master branch..."
git checkout master

# Pull latest changes
echo "⬇️  Pulling latest changes from origin..."
git pull origin master

# Create and push the tag
echo "🏷️  Creating v1.2.0 tag..."
git tag -a v1.2.0 -m "Release v1.2.0: Major Package Improvements

### ✨ New Features
- Custom Exception System with dedicated exceptions
- Retry Mechanisms with exponential backoff
- Comprehensive Logging system
- New API endpoints across all services (AI, Audio, Studio, Workspace)
- Real API Testing Framework
- Comprehensive test coverage
- GitHub workflow automation

### 🔧 Improvements
- Enhanced error handling and reliability
- Better configuration management
- Improved service architecture
- Extensive documentation and examples

### 📋 Stats
- 50 files changed: 11,250 insertions, 199 deletions
- Backward-compatible release
- No breaking changes"

echo "📤 Pushing tag to origin..."
git push origin v1.2.0

echo "✅ Successfully tagged and pushed v1.2.0!"
echo ""
echo "🔗 Release URL: https://github.com/abdu11aev-samandar/laravel-elevenlabs/releases/tag/v1.2.0"
echo ""
echo "📝 Next steps:"
echo "  1. Go to GitHub releases and edit the v1.2.0 release"
echo "  2. Add release notes from CHANGELOG.md"
echo "  3. Consider updating Packagist if auto-update is not enabled"
