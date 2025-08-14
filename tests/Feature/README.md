# Real API Tests Documentation

This directory contains feature tests that make actual calls to the ElevenLabs API. These tests are designed to validate that the package correctly integrates with the live ElevenLabs API.

## ⚠️ IMPORTANT WARNINGS

### API Key Requirements
- **Real API key required**: These tests require a valid ElevenLabs API key
- **API usage costs**: Running these tests will consume your ElevenLabs API quota/credits
- **Rate limits apply**: Tests may fail if you hit API rate limits

### Destructive Operations
Some tests are marked as `@group destructive` and can:
- Create voices in your account
- Create agents and knowledge bases
- Use API credits for text-to-speech generation
- These are **SKIPPED BY DEFAULT** for safety

## 🚀 Running the Tests

### Prerequisites
1. Valid ElevenLabs API key
2. Active ElevenLabs subscription (recommended: not free tier for full testing)
3. Disposable test environment (recommended for destructive tests)

### Set Environment Variables
```bash
export ELEVENLABS_API_KEY="your_real_api_key_here"
```

### Run Safe Tests Only (Recommended)
```bash
# Run only safe, non-destructive tests
./vendor/bin/phpunit --group real-api --exclude-group destructive,external

# Run specific service groups
./vendor/bin/phpunit --group analytics
./vendor/bin/phpunit --group voice --exclude-group destructive
./vendor/bin/phpunit --group audio
```

### Run All Tests (Including Destructive - Use with Caution)
```bash
# ⚠️ WARNING: This will create resources in your account!
./vendor/bin/phpunit --group real-api

# Run only destructive tests (for thorough testing in disposable environment)
./vendor/bin/phpunit --group destructive
```

### Run Integration Workflows
```bash
# Test complete workflows end-to-end
./vendor/bin/phpunit --group integration
```

## 📊 Test Groups

### Safe Groups (Recommended for regular testing)
- `@group analytics` - User info, subscription, usage stats
- `@group voice` (non-destructive) - List voices, get voice details
- `@group audio` - Text-to-speech generation (uses credits but safe)
- `@group ai` (read-only) - List agents, conversations (read operations)
- `@group studio` (read-only) - List projects
- `@group workspace` (read-only) - List members, resources

### Destructive Groups (Use with extreme caution)
- `@group destructive` - Creates/deletes resources
  - Voice cloning (creates custom voices)
  - Agent creation/deletion
  - Knowledge base creation/deletion
  
### External Groups (Require network)
- `@group external` - All tests that make network requests

### Integration Groups
- `@group integration` - End-to-end workflows
- `@group endpoint-verification` - API endpoint compatibility checks

## 🛡️ Safety Features

### Automatic Cleanup
The test suite includes automatic cleanup in the `tearDown()` method:
- Created voices are automatically deleted
- Created agents are removed
- Created knowledge bases are cleaned up
- Generated files are removed

### Skipped Tests
Tests are automatically skipped when:
- No real API key is provided
- API key is set to test/placeholder value
- Feature not available on current subscription tier
- Required test data not available

### Error Handling
- Tests gracefully handle API errors
- Network timeouts are handled appropriately  
- Invalid responses are properly managed
- Failed cleanup operations are logged but don't fail tests

## 📝 Test Structure

### User Information & Analytics
- `test_can_get_user_info()` - Basic user account info
- `test_can_get_user_subscription()` - Subscription details and limits
- `test_can_get_models()` - Available TTS models
- `test_can_get_character_usage()` - Usage statistics
- `test_can_get_history()` - Generation history

### Voice Management
- `test_can_get_voices()` - List all available voices
- `test_can_get_specific_voice()` - Get detailed voice information
- `test_can_get_shared_voices()` - Community shared voices
- `test_can_create_voice_previews()` - Generate voice previews (safe)
- `test_voice_cloning_workflow()` - **DESTRUCTIVE** - Voice cloning

### Audio Generation
- `test_can_generate_text_to_speech()` - Basic TTS generation
- `test_can_save_tts_to_file()` - File saving functionality
- `test_can_generate_sound_effects()` - Sound effects generation

### AI & Conversational Features
- `test_can_get_agents()` - List AI agents
- `test_can_get_conversations()` - List conversations
- `test_can_get_knowledge_bases()` - List knowledge bases

### Studio & Workspace
- `test_can_get_studio_projects()` - List studio projects
- `test_can_get_workspace_members()` - List workspace members

### Integration Tests
- `test_full_tts_workflow()` - Complete TTS workflow
- `test_api_endpoints_are_current()` - Endpoint compatibility

## 🔧 Configuration

### Environment Variables
```bash
ELEVENLABS_API_KEY=your_api_key_here
APP_ENV=testing
```

### PHPUnit Configuration
The `phpunit.xml` is configured to:
- Exclude destructive tests by default
- Group tests by functionality
- Set appropriate timeouts for network requests

## 🐛 Troubleshooting

### Common Issues

#### "Real API key required" error
- Ensure `ELEVENLABS_API_KEY` environment variable is set
- Verify API key is valid and not a placeholder

#### "Feature not available" warnings  
- Some features require paid subscriptions
- Tests will skip gracefully if features aren't available

#### Rate limit errors
- Reduce concurrent test execution
- Add delays between test runs
- Use a higher-tier subscription for testing

#### Network timeouts
- Check internet connectivity
- Increase timeout values in service configurations
- Run tests during off-peak hours

### Test Output Files
Generated audio files are saved to:
- `storage/test_output/` directory
- Files are automatically cleaned up after tests
- Check this directory if cleanup fails

## 📚 Best Practices

1. **Use separate test account** - Don't run destructive tests on production accounts
2. **Monitor API usage** - Check your quota before running extensive tests  
3. **Run safe tests first** - Validate basic functionality before destructive tests
4. **Clean environment** - Use fresh test environments for destructive testing
5. **Version control** - Don't commit API keys or generated test files
6. **CI/CD considerations** - Exclude real API tests from automated CI unless using test accounts

## 🔗 Resources

- [ElevenLabs API Documentation](https://elevenlabs.io/docs)
- [ElevenLabs API Reference](https://elevenlabs.io/docs/api-reference)  
- [Package Documentation](../../README.md)
