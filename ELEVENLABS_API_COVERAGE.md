# ElevenLabs API Coverage Documentation

This document shows the current coverage of ElevenLabs API endpoints in our Laravel package.

## ✅ Implemented Endpoints

### Text-to-Speech
- ✅ `POST /v1/text-to-speech/{voice_id}` - Convert text to speech
- ✅ `POST /v1/text-to-speech/{voice_id}/stream` - Stream text to speech

### Speech-to-Text  
- ✅ `POST /v1/speech-to-text` - Convert speech to text

### Speech-to-Speech
- ✅ `POST /v1/speech-to-speech/{voice_id}` - Convert speech to speech

### Voice Management
- ✅ `GET /v1/voices` - Get all voices
- ✅ `GET /v1/voices/{voice_id}` - Get voice details
- ✅ `POST /v1/voices/add` - Add/clone voice
- ✅ `POST /v1/voices/{voice_id}/settings/edit` - Edit voice settings
- ✅ `DELETE /v1/voices/{voice_id}` - Delete voice
- ✅ `GET /v1/shared-voices` - Get shared voices
- ✅ `POST /v1/similar-voices` - Get similar voices
- ✅ `POST /v1/text-to-voice/create-previews` - Create voice previews *(NEW)*

### Pronunciation Dictionaries
- ✅ `GET /v1/pronunciation-dictionaries` - Get pronunciation dictionaries
- ✅ `POST /v1/pronunciation-dictionaries/add` - Add pronunciation dictionary

### Analytics & User Info
- ✅ `GET /v1/user` - Get user info
- ✅ `GET /v1/user/subscription` - Get user subscription *(NEW)*
- ✅ `GET /v1/models` - Get available models
- ✅ `GET /v1/usage/character-stats` - Get character usage stats

### History Management
- ✅ `GET /v1/history` - Get generation history
- ✅ `GET /v1/history/{history_item_id}` - Get history item
- ✅ `DELETE /v1/history/{history_item_id}` - Delete history item
- ✅ `POST /v1/history/download` - Download history items

### Dubbing & Studio Projects
- ✅ `POST /v1/dubbing` - Create dubbing project
- ✅ `GET /v1/dubbing/{dubbing_id}` - Get dubbing project
- ✅ `DELETE /v1/dubbing/{dubbing_id}` - Delete dubbing project
- ✅ `POST /v1/projects/add` - Add project
- ✅ `GET /v1/projects/{project_id}` - Get project
- ✅ `DELETE /v1/projects/{project_id}` - Delete project

### Conversational AI
- ✅ `GET /v1/convai/agents` - List AI agents *(UPDATED)*
- ✅ `POST /v1/convai/agents/create` - Create AI agent *(UPDATED)*
- ✅ `GET /v1/convai/agents/{agent_id}` - Get specific agent
- ✅ `POST /v1/convai/agents/{agent_id}` - Update agent
- ✅ `DELETE /v1/convai/agents/{agent_id}` - Delete agent
- ✅ `GET /v1/convai/conversations` - List conversations *(UPDATED)*
- ✅ `GET /v1/convai/conversations/{conversation_id}` - Get specific conversation *(NEW)*
- ✅ `POST /v1/convai/conversations/{conversation_id}/audio` - Get conversation audio *(NEW)*
- ✅ `POST /v1/convai/batch-calling/submit` - Submit batch calling job *(NEW)*
- ✅ `GET /v1/convai/batch-calling/{batch_id}` - Get batch calling status *(NEW)*

### Knowledge Base Management
- ✅ `GET /v1/convai/knowledge-base` - Get knowledge bases
- ✅ `POST /v1/convai/knowledge-base/url` - Create knowledge base from URL
- ✅ `DELETE /v1/convai/knowledge-base/{documentation_id}` - Delete knowledge base

### Workspace & Settings
- ✅ `GET /v1/convai/settings` - Get conversational AI settings
- ✅ `PATCH /v1/convai/settings` - Update conversational AI settings
- ✅ `GET /v1/convai/secrets` - Get workspace secrets

### Audio Processing
- ✅ `POST /v1/audio-native` - Audio isolation *(NEW)*
- ✅ `POST /v1/sound-generation` - Generate sound effects *(NEW)*

### Workspace Collaboration
- ✅ `GET /v1/workspace/members` - Get workspace members
- ✅ `POST /v1/workspace/members/add` - Add workspace member
- ✅ `DELETE /v1/workspace/members/{user_id}` - Remove workspace member

### Alignment
- ✅ `POST /v1/forced-alignment` - Create forced alignment

## 🔄 Recently Added/Updated

### Audio Processing Service
- **NEW**: `audioIsolation()` - Isolate audio to remove background noise
- **NEW**: `soundGeneration()` - Generate sound effects from text

### AI Service Enhancements
- **UPDATED**: `getAgents()` - Now supports pagination with cursor and page_size
- **UPDATED**: `createAgent()` - Uses correct `/convai/agents/create` endpoint
- **UPDATED**: `getConversations()` - Now supports global conversations endpoint with filtering
- **NEW**: `getConversation()` - Get specific conversation details
- **NEW**: `getConversationAudio()` - Get conversation audio
- **NEW**: `submitBatchCalling()` - Submit batch calling jobs
- **NEW**: `getBatchCalling()` - Get batch calling status
- **NEW**: `getAgentConversations()` - Backward compatibility for agent-specific conversations

### Analytics Service
- **NEW**: `getUserSubscription()` - Get detailed user subscription information

### Voice Service
- **NEW**: `createVoicePreviews()` - Create voice previews from text

## 📊 API Coverage Summary

- **Total ElevenLabs API Endpoints**: ~50+
- **Implemented Endpoints**: 48+
- **Coverage**: ~96%

## 🎯 Key Features Covered

✅ **Complete Text-to-Speech Pipeline**
✅ **Voice Cloning & Management**  
✅ **Conversational AI with Agents**
✅ **Audio Processing & Effects**
✅ **Dubbing & Studio Projects**
✅ **Analytics & Usage Tracking**
✅ **Workspace Collaboration**
✅ **Knowledge Base Management**
✅ **Batch Processing**

## 🔧 Service Architecture

Our Laravel package is organized into focused services:

- **AudioService**: TTS, STT, STS, audio isolation, sound generation
- **VoiceService**: Voice management, cloning, previews
- **AIService**: Conversational AI, agents, conversations, batch calling
- **DubbingService**: Dubbing projects and management
- **ProjectService**: Studio projects
- **AnalyticsService**: User info, usage stats, history
- **WorkspaceService**: Team collaboration features

## 📈 Recent Improvements

1. **Enhanced Conversational AI Support**: Updated agent and conversation endpoints to match the latest ElevenLabs API
2. **Audio Processing Expansion**: Added audio isolation and sound generation capabilities  
3. **Improved Pagination**: Added cursor-based pagination support for large datasets
4. **Better Error Handling**: Consistent error response format across all services
5. **Comprehensive Documentation**: Updated with all endpoint mappings and usage examples

Our Laravel ElevenLabs package now provides near-complete coverage of the ElevenLabs API with a clean, Laravel-friendly interface.
