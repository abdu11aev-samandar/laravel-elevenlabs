# ElevenLabs API Coverage vs Current Services

Generated: 2025-08-08

This report solishtiradi: paketdagi mavjud servis metodlari va ElevenLabs API snippetlarida ko‘rsatilgan endpointlar.
Har bir bo‘limda:
- Covered: hozirgi kod bilan qamrab olingan endpointlar/metodlar
- Missing / Gaps: hali yo‘q yoki nomlanishi/mapping’i farq qiladigan endpointlar
- Notes: moslik, versiya (v1/v2) va kichik tafovutlar

Legend:
- Base URL asosiy hisob: https://api.elevenlabs.io/v1 (agar alohida ko‘rsatilmasa)

---

## 1) Audio Service (TTS, STT, STS, Sound, Alignment)

Covered
- POST /text-to-speech/{voiceId}
  - textToSpeech(text, voiceId, voiceSettings)
- POST /text-to-speech/{voiceId}/stream
  - streamTextToSpeech(text, voiceId, modelId, voiceSettings)
- POST /speech-to-text
  - speechToText(file, modelId)
- POST /speech-to-speech/{voiceId}
  - speechToSpeech(voiceId, file, modelId, voiceSettings)
- POST /forced-alignment
  - createForcedAlignment(file, text, language)
- POST /sound-generation
  - soundGeneration(text, durationSeconds?, promptInfluence?)
- Utility: saveAudioToFile, textToSpeechAndSave

Missing / Gaps
- Audio Native:
  - POST /audio-native (docs: Audio Native project create, returns JSON with project_id/html_snippet)
    - Current audioIsolation() posts to /audio-native and expects binary audio. This likely mismatched with API (should return JSON project, not binary). Needs new methods:
      - audioNativeCreate(name, ...)
      - audioNativeGetSettings(projectId) (GET /audio-native/{project_id}/settings) – snippet mavjud
- Sound Effects additional params (if any advanced options exposed in docs)

Notes
- audioIsolation() nomi va mapping’i tekshirilishi lozim. Agar maqsad shovqinni tozalash bo‘lsa, rasmiy endpoint nomi/yo‘li tekshirilsin. Hozirgi /audio-native API ma’nosi boshqa (pleyer loyihasi).

---

## 2) Voice Service

Covered
- GET /voices – getVoices()
- GET /voices/{voiceId} – getVoice()
- POST /voices/add – addVoice(name, files, description, labels)
- POST /voices/{voiceId}/settings/edit – editVoiceSettings()
- DELETE /voices/{voiceId} – deleteVoice()
- POST /similar-voices – getSimilarLibraryVoices(file)
- GET /shared-voices – getSharedVoices()
- GET /pronunciation-dictionaries – getPronunciationDictionaries()
- POST /pronunciation-dictionaries/add – addPronunciationDictionary()
- POST /text-to-voice/create-previews – createVoicePreviews(text, voiceId)

Missing / Gaps
- PVC samples related endpoints (e.g., separated speaker audio):
  - GET /voices/{voice_id}/pvc/samples/{sample_id}/speakers/{speaker_id}/audio – snippetda bor
- Default voice settings GET /voices/settings/default – snippetda bor

Notes
- Docs’larda v2/voices (search) ham ko‘rsatilgan. Hozir /v1/voices ishlatilmoqda – moslik tekshirilishi mumkin.

---

## 3) AI Service (Conversational AI, Agents, Conversations, Batch Calling, KB, MCP, Widget)

Covered
- Settings:
  - GET /convai/settings – getConversationalAISettings()
  - PATCH /convai/settings – updateConversationalAISettings()
- Secrets:
  - GET /convai/secrets – getWorkspaceSecrets()
- Knowledge Base (root):
  - GET /convai/knowledge-base – getKnowledgeBases(cursor, page_size)
  - POST /convai/knowledge-base/url – createKnowledgeBaseFromURL(url)
  - DELETE /convai/knowledge-base/{documentationId} – deleteKnowledgeBase()
- Agents:
  - GET /convai/agents – getAgents(cursor, page_size)
  - POST /convai/agents/create – createAgent(data)
  - GET /convai/agents/{agentId} – getAgent()
  - POST /convai/agents/{agentId} – updateAgent()
  - DELETE /convai/agents/{agentId} – deleteAgent()
- Conversations:
  - GET /convai/conversations?cursor=&page_size=&call_start_after_unix=&call_start_before_unix – getConversations(...)
  - GET /convai/agents/{agentId}/conversations – getAgentConversations()
  - POST /convai/agents/{agentId}/conversations – createConversation()
  - GET /convai/conversations/{conversationId} – getConversation()
  - [Audio] (expected GET) /convai/conversations/{conversationId}/audio – getConversationAudio() [uses POST binary, consider GET]
- Batch Calling:
  - POST /convai/batch-calling/submit – submitBatchCalling()
  - GET /convai/batch-calling/{batch_id} – getBatchCalling()

Missing / Gaps
- Conversations:
  - GET /convai/conversations/get-signed-url?agent_id=… – getSignedUrl() (snippet mavjud)
- Agent Widget:
  - GET /convai/agents/{agent_id}/widget – getAgentWidgetConfig() (snippet mavjud)
- Knowledge Base – Documents:
  - POST /convai/knowledge-base/documents/create-from-file – createKBFromFile()
  - GET /convai/knowledge-base/documents/{id}/content – getKBDocumentContent()
- RAG Index Overview:
  - GET /convai/knowledge-base/rag-index-overview – getRagIndexOverview()
- Tools:
  - GET /convai/tools – listTools()
  - GET /convai/tools/{tool_id} – getTool()
  - POST /convai/tools – createTool()
  - GET /convai/tools/{tool_id}/dependent-agents – getDependentAgents()
- MCP Servers:
  - GET /convai/mcp-servers – listMcpServers()
  - POST /convai/mcp-servers – createMcpServer()
  - POST /convai/mcp-servers/approval-policies – createMcpApprovalPolicy()
- Dashboard:
  - GET /convai/dashboard/settings – getDashboardSettings()

Notes
- getConversationAudio() hozir POST orqali binary oladi; rasmiy docs ko‘pi GET misollar beradi. Tekshirib, GET’ga moslashtirish mumkin.

---

## 4) Studio Service (Projects, Chapters, Snapshots, Podcast, Dubbing)

Covered
- Projects:
  - GET /studio/projects – getStudioProjects()
  - POST /studio/projects – createStudioProject(file, name?)
  - GET /studio/projects/{projectId} – getStudioProject()
  - DELETE /studio/projects/{projectId} – deleteStudioProject()
  - POST /studio/projects/{projectId}/convert – convertStudioProject()
- Podcast:
  - POST /studio/podcasts – createPodcastProject(data)
- Dubbing:
  - POST /dubbing – createDubbing(file, target_lang, ...)
  - GET /dubbing/{dubbingId} – getDubbing()
  - GET /dubbing/{dubbingId}/audio/{languageCode} – getDubbedAudio() [coded via postBinary]

Missing / Gaps
- Chapters:
  - GET /studio/projects/{project_id}/chapters/{chapter_id} – getChapter()
  - GET /studio/projects/{project_id}/chapters/{chapter_id}/snapshots – listChapterSnapshots()
  - GET /studio/projects/{project_id}/chapters/{chapter_id}/snapshots/{chapter_snapshot_id} – getChapterSnapshot()
- Project Snapshots:
  - GET /studio/projects/{project_id}/snapshots/{project_snapshot_id} – getProjectSnapshot()

Notes
- Dubbing: transcript endpointlar (SRT/WEBVTT) kabi qo‘shimchalar ham bor (snippets: transcript/get) – kerak bo‘lsa qo‘shish mumkin.

---

## 5) Analytics Service (Models, Usage, History, Subscription)

Covered
- GET /user – getUserInfo()
- GET /user/subscription – getUserSubscription()
- GET /models – getModels()
- GET /usage/character-stats – getCharacterUsage()
- History:
  - GET /history – getHistory(page_size, start_after)
  - GET /history/{historyItemId} – getHistoryItem()
  - DELETE /history/{historyItemId} – deleteHistoryItem()
  - POST /history/download – downloadHistory(ids)

Missing / Gaps
- None obvious from provided snippets

Notes
- OK

---

## 6) Workspace Service (Resources, Members)

Covered
- POST /workspace/resources/{resourceId}/share – shareWorkspaceResource()
- GET /workspace/resources – getWorkspaceResources()
- Members:
  - GET /workspace/members – getWorkspaceMembers()
  - POST /workspace/members/invite – inviteWorkspaceMember(email, permissions)
  - DELETE /workspace/members/{memberId} – removeWorkspaceMember()

Missing / Gaps
- GET /workspace/resources/{resource_id} – getWorkspaceResource() (snippet: Get Resource response example bor)
- GET /workspace/groups/search – searchWorkspaceGroups(query?)
- GET /workspace/secrets – ayrim docs’larda Convai/Workspace yo‘llari alohida ko‘rinadi. Hozir AIService’da /convai/secrets bor; /workspace/secrets ham kerak bo‘lishi mumkin.

Notes
- Secrets end-point joylashuvi (convai vs workspace) aniq sinxronlashtirilishi kerak.

---

## 7) Legacy / Misc

Covered
- Legacy Facade metodlari backward compatible (README va examples’da ko‘rsatilgan)

Missing / Gaps
- Yo‘q

---

## Konspekt – Implementatsiya uchun TODO ro‘yxati

Audio
- [ ] Audio Native: createAudioNativeProject(name, options…) -> POST /audio-native (JSON javob)
- [ ] Audio Native: getAudioNativeSettings(projectId) -> GET /audio-native/{projectId}/settings
- [ ] audioIsolation() mappingini tekshirish yoki to‘g‘ri endpointga ko‘chirish

Voice
- [ ] getDefaultVoiceSettings() -> GET /voices/settings/default
- [ ] getSeparatedSpeakerAudio(voiceId, sampleId, speakerId) -> GET /voices/{voice_id}/pvc/samples/{sample_id}/speakers/{speaker_id}/audio

AI / Conversational
- [ ] getSignedUrl(agentId) -> GET /convai/conversations/get-signed-url?agent_id=...
- [ ] getAgentWidgetConfig(agentId, conversation_signature?) -> GET /convai/agents/{agent_id}/widget
- Knowledge Base – Documents:
  - [ ] createKBDocumentFromFile(file, meta?) -> POST /convai/knowledge-base/documents/create-from-file
  - [ ] getKBDocumentContent(documentId) -> GET /convai/knowledge-base/documents/{id}/content
- [ ] getRagIndexOverview() -> GET /convai/knowledge-base/rag-index-overview
- Tools:
  - [ ] listTools() -> GET /convai/tools
  - [ ] getTool(toolId) -> GET /convai/tools/{tool_id}
  - [ ] createTool(payload) -> POST /convai/tools
  - [ ] getDependentAgents(toolId) -> GET /convai/tools/{tool_id}/dependent-agents
- MCP Servers:
  - [ ] listMcpServers() -> GET /convai/mcp-servers
  - [ ] createMcpServer(payload) -> POST /convai/mcp-servers
  - [ ] createMcpApprovalPolicy(payload) -> POST /convai/mcp-servers/approval-policies
- Dashboard:
  - [ ] getDashboardSettings() -> GET /convai/dashboard/settings

Studio
- [ ] getChapter(projectId, chapterId)
- [ ] listChapterSnapshots(projectId, chapterId)
- [ ] getChapterSnapshot(projectId, chapterId, chapterSnapshotId)
- [ ] getProjectSnapshot(projectId, projectSnapshotId)
- [ ] (Optional) Dubbing transcripts: GET /dubbing/{dubbing_id}/transcript?format_type={srt|webvtt}

Workspace
- [ ] getWorkspaceResource(resourceId) -> GET /workspace/resources/{resource_id}
- [ ] searchWorkspaceGroups(query) -> GET /workspace/groups/search
- [ ] (Optional) getWorkspaceSecrets() in WorkspaceService -> GET /workspace/secrets (agar convai/secrets’dan alohida bo‘lsa)

---

## Moslik va nomlash bo‘yicha tavsiyalar
- HTTP methodlari: audio va studio audio endpointlarida GET/POST farqlari bor – rasmiy docs bilan sinxronlashtirish
- Versiyalar: Voices uchun v1 vs v2 qismi (search) – agar kerak bo‘lsa qo‘shimcha metod sifatida qo‘shish
- Filtrlash: Conversations endpointlariga call_start_after_unix va call_start_before_unix qo‘shilgan – allaqachon qo‘llab-quvvatlangan

---

## Keyingi qadamlar (taklif)
1) AIService kengaytmalari (signed URL, widget, KB documents, RAG, tools, MCP, dashboard)
2) StudioService’da chapters/snapshots endpointlari
3) Audio Native uchun alohida service metodlari + README usage
4) Workspace’da resource get va groups search
5) Unit testlar (mock Guzzle) + README usage misollar

Agar xohlasangiz, yuqoridagi TODO’lar bo‘yicha bosqichma-bosqich implementatsiyani boshlayman.

