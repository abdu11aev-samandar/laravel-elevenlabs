# Real API Test Guide - Run Until 100% Pass

This guide explains how to run the ElevenLabs real API tests until they achieve 100% success rate.

## 🎯 Goal

Run `php artisan test --group=real-api` (or equivalent PHPUnit command) until the test suite passes 100%, with automated retry logic and CI/CD integration.

## 🚀 Quick Start

### 1. Set Your API Key

```bash
export ELEVENLABS_API_KEY="sk-your-actual-elevenlabs-api-key-here"
```

Or create a `.env` file:
```bash
echo "ELEVENLABS_API_KEY=sk-your-actual-elevenlabs-api-key-here" >> .env
```

### 2. Run Automated Test Script

```bash
./run_real_api_tests.sh
```

This script will:
- ✅ Run tests up to 10 times with 30-second intervals
- 📊 Track success rate and provide detailed reporting
- 🔧 Analyze failures and suggest fixes
- 📁 Save all logs and results for review

### 3. Or Run PHPUnit Directly

```bash
./vendor/bin/phpunit --group=real-api
```

## 📋 Test Overview

The real API tests include **22 test methods** covering:

### Analytics Service (5 tests)
- ✅ `test_can_get_user_info` - User profile information
- ✅ `test_can_get_user_subscription` - Subscription details
- ✅ `test_can_get_models` - Available TTS models
- ✅ `test_can_get_character_usage` - Usage statistics (fixed API parameters)
- ✅ `test_can_get_history` - Generation history

### Voice Service (5 tests)
- ✅ `test_can_get_voices` - Available voices list
- ✅ `test_can_get_specific_voice` - Individual voice details
- ✅ `test_can_get_shared_voices` - Community voices
- ✅ `test_can_get_pronunciation_dictionaries` - Custom pronunciations
- ✅ `test_can_create_voice_previews` - Voice preview generation

### Audio Service (3 tests)
- ✅ `test_can_generate_text_to_speech` - Basic TTS
- ✅ `test_can_save_tts_to_file` - File saving functionality
- ⚠️ `test_can_generate_sound_effects` - Sound effects (may require higher tier)

### AI Service (3 tests)
- ⚠️ `test_can_get_conversational_ai_settings` - Chat AI settings
- ⚠️ `test_can_get_knowledge_bases` - Knowledge base management
- ⚠️ `test_can_get_agents` - AI agents management
- ⚠️ `test_can_get_conversations` - Conversation history

### Studio & Workspace Services (2 tests)
- ⚠️ `test_can_get_studio_projects` - Studio project management
- ⚠️ `test_can_get_workspace_members` - Workspace collaboration
- ⚠️ `test_can_get_workspace_resources` - Workspace resources

### Integration Tests (4 tests)
- ✅ `test_full_tts_workflow` - Complete TTS workflow
- 🔍 `test_api_endpoints_are_current` - API validation
- ⚠️ `test_voice_cloning_workflow` - Voice cloning (destructive, skipped by default)

## 🔧 Fixes Applied

### ✅ Character Usage API Fix
**Issue**: `422 unknown` response - missing required parameters
**Solution**: Added required `start_unix` and `end_unix` parameters with sensible defaults

```php
// Before (failing)
$result = $this->get('usage/character-stats');

// After (working)
$params = [
    'start_unix' => $startUnix ?? (time() - (30 * 24 * 60 * 60)), // 30 days ago
    'end_unix' => $endUnix ?? time(), // now
];
$result = $this->get('usage/character-stats?' . http_build_query($params));
```

## 🤖 GitHub Actions CI/CD

### Automated Testing Matrix

The tests run automatically on:
- **Push** to main/develop branches
- **Pull requests** to main
- **Daily schedule** (2 AM UTC)
- **Manual trigger** with custom parameters

### Test Matrix Configuration

```yaml
strategy:
  matrix:
    os: [ubuntu-latest, windows-latest, macos-latest]
    php: ['8.1', '8.2', '8.3', '8.4']
    test-tier: ['free', 'starter', 'creator']
```

### Setting Up CI/CD

1. **Add API Key Secret**:
   - Go to Repository Settings → Secrets and Variables → Actions
   - Add `ELEVENLABS_API_KEY` with your API key

2. **Workflow will automatically**:
   - Run tests with retry logic (5 attempts by default)
   - Generate comprehensive test reports
   - Upload artifacts with logs and results
   - Update documentation with latest status

3. **Manual Triggers**:
   ```yaml
   workflow_dispatch:
     inputs:
       max_attempts:
         description: 'Maximum test attempts'
         default: '5'
       wait_time:
         description: 'Wait time between attempts (seconds)'
         default: '30'
   ```

## 📊 Success Metrics

### Target: 100% Test Success

Based on previous runs:
- **Total Tests**: 22
- **Currently Passing**: ~8-10 (with valid API key)
- **Skipped** (subscription dependent): ~7
- **Failures** (needs fixes): ~14 → 0 (goal)

### Progress Tracking

The automated script tracks:
- ✅ **Passed**: Tests that completed successfully
- ❌ **Failed**: Tests with errors or failures
- ⚠️ **Skipped**: Tests not available on current subscription tier
- 📈 **Success Rate**: (Passed / (Passed + Failed)) × 100%

## 🔍 Troubleshooting

### Common Issues & Solutions

#### 1. 401 Unauthorized
```
Error: Invalid API key
```
**Solutions**:
- Verify API key is correct and active
- Check if key has sufficient credits
- Ensure key hasn't expired

#### 2. 422 Validation Errors
```
Error: Field required, missing parameters
```
**Solutions**:
- Check API documentation for required parameters
- Review recent API changes
- Update service methods with correct parameters

#### 3. 429 Rate Limiting
```
Error: Too many requests
```
**Solutions**:
- Increase wait time between test attempts
- Consider upgrading subscription for higher rate limits
- Run tests during off-peak hours

#### 4. Subscription Tier Limitations
```
Tests skipped: Feature not available
```
**Solutions**:
- Tests are correctly skipped for unavailable features
- Consider upgrading for full test coverage
- These skips don't count as failures

### Debugging Steps

1. **Check Latest Logs**:
   ```bash
   ls -la test_results/
   cat test_results/attempt_1_*.log
   ```

2. **Review Failure Details**:
   ```bash
   grep -A 5 "FAIL" test_results/attempt_*.log
   ```

3. **Analyze XML Results**:
   ```bash
   grep "failure\|error" test_results/junit_*.xml
   ```

## 📈 Success Strategies

### 1. Incremental Fixing
- Fix API parameter issues first (highest impact)
- Address authentication problems
- Handle rate limiting gracefully
- Document subscription-dependent features

### 2. Monitoring & Alerts
- Set up GitHub notifications for test failures
- Monitor daily scheduled runs
- Track success rate trends over time

### 3. Documentation Updates
- Keep API documentation current
- Document known limitations by subscription tier
- Maintain troubleshooting guides

## 🎉 Success Criteria

**Mission Complete When**:
- ✅ All non-subscription-dependent tests pass (0 failures, 0 errors)
- ✅ Subscription-dependent tests skip gracefully
- ✅ Success rate = 100% consistently across multiple runs
- ✅ CI/CD pipeline shows green status
- ✅ Documentation is up to date

## 💡 Next Steps

Once 100% success is achieved:

1. **Maintain Test Health**:
   - Monitor for API changes
   - Update tests as new features are added
   - Keep dependencies updated

2. **Expand Test Coverage**:
   - Add edge cases
   - Test error conditions
   - Add performance benchmarks

3. **Optimize CI/CD**:
   - Reduce test execution time
   - Implement smart test selection
   - Add integration with monitoring tools

---

**Happy Testing! 🚀**

Need help? Check the [test results](./test_results/) directory or review the GitHub Actions run logs.
