# Laravel ElevenLabs Package – Roadmap (SemVer)

Bu fayl paketning kelgusi rejalari, milestones va issue’lar bo‘yicha yo‘l xaritasini ko‘rsatadi. Har bir milestone: funksional o‘zgarishlar + testlar + hujjatlar + CHANGELOG yangilanishi bilan tugallanadi. SemVer (MAJOR.MINOR.PATCH) qoidasiga amal qilinadi.

## Umumiy tamoyillar
- Versiyalash: SemVer
- Har bir issue: Acceptance Criteria, Tests, Docs bandlari bilan
- Hech qanday maxfiy ma’lumotni repo’da saqlamaslik

## Versiya rejalari

### v1.2 – Ishonchlilik va konfiguratsiya (no breaking changes)
- Retry/Backoff + Rate Limit Awareness
  - 429/5xx holatlarida exponential backoff, jitter, Retry-After sarlavhasiga hurmat
  - Konfiguratsiya: enable/disable, max_attempts, base_delay_ms, max_delay_ms, respect_retry_after
- Laravel HTTP Client adapter (ixtiyoriy)
  - config('elevenlabs.http_client') = guzzle|laravel
- Kesh
  - GET natijalarini qisqa TTL bilan cache (models, shared voices)

### v1.3 – Developer Experience (DX) va Typed API (no breaking changes)
- DTO/Response obyektlari (array bilan yonma-yon)
- TTS/STS/Dubbing uchun fluent builders (chainable)

### v1.4 – Standartlarga moslashuv (no breaking changes)
- PSR-18 client qo‘llab-quvvatlash (default Guzzle, PSR-18 ixtiyoriy)

### v1.5 – Observability va Audit (no breaking changes)
- PSR-3/OpenTelemetry log/tracing integratsiyasi (sensitive data maskalash)
- Audit eventlar (voice delete, dictionary edit)

### v1.6 – CLI va Workflow’lar (no breaking changes)
- Artisan komandalar: elevenlabs:tts, elevenlabs:audio-isolate, elevenlabs:dub
- Storage integratsiyasi: Storage disklarida put/get misollari

### v1.7 – Webhook/Queue integratsiyasi (no breaking changes)
- Webhook scaffolding (conversation/batch callbacks), signature verification bo‘lsa — qo‘llab-quvvatlash
- Queue workflows: uzun jarayonlar uchun job misollari (retry/delay)

### v1.8 – Admin integratsiyalari (no breaking changes)
- Laravel Nova/Filament resurslari (voices, agents, conversations) – ixtiyoriy alohida paket

### v1.9 – Qo‘shimcha API qamrovi (no breaking changes)
- Studio/Agent tools/feedback endpointlari to‘liq qamrov

### v2.0 – Breaking changes
- Exception-first error handling (success/array o‘rniga Exception tashlash — default)
  - Legacy rejim config flag bilan
- Default client’ni PSR-18 yoki Laravel HTTP Client’ga ko‘chirish
- Service interfeyslari (AudioServiceInterface, AIServiceInterface, ...)

## Issues – namunaviy ro‘yxat (asosiylari)

1) Retry middleware: exponential backoff + Retry-After
- Acceptance: 429/5xx uchun retry; Retry-After ga hurmat; config orqali boshqarish
- Tests: Unit (delay va attempts), Integration (429→200)
- Docs: README “Error handling & Retry” bo‘limi

2) Cache for GET endpoints
- Acceptance: models/shared-voices uchun TTL-based cache; config bilan on/off
- Tests: Cache hit/miss
- Docs: Config misoli

3) Laravel HTTP Client adapter (ixtiyoriy)
- Acceptance: config orqali tanlash; Http::fake() bilan testlash oson
- Tests: Fake bilan unit; Guzzle bilan mavjudlar
- Docs: Konfiguratsiya bo‘limi

4) DTO layer
- Acceptance: Service metodlari DTO yoki array qaytara olishi (dual mode)
- Tests: DTO mapping
- Docs: DTO usage

5) TTS Builder
- Acceptance: Chainable builder; mavjud API bilan yonma-yon ishlaydi
- Tests: Builder chain testlari
- Docs: Quickstart builder

6) PSR-18 adapter
- Acceptance: PSR-18 client injektsiya qilinadi; default Guzzle
- Tests: PSR-18 mock bilan
- Docs: PSR-18 usage

7) Observability & Audit
- Acceptance: Log sanitizatsiyasi, timing/tracing; audit eventlar
- Tests: Sanitization va event dispatch
- Docs: Observability bo‘limi

8) CLI & Storage
- Acceptance: Artisan komandalar ishlaydi; Storage misollari
- Tests: Console tester; feature tests
- Docs: CLI usage

9) Webhook & Queue
- Acceptance: Webhook controller; queue jobs
- Tests: Feature tests; job retry
- Docs: Webhook setup

10) Admin UI integrations
- Acceptance: Nova/Filament resources
- Docs: Integratsiya bo‘limi

11) v2.0 migration
- Acceptance: Exception-first; legacy mode; interfeyslar
- Docs: Migration Guide

## Changelog va Docs
- Har release’da CHANGELOG yoziladi
- README kengaytiriladi, docs/ katalogi qo‘shilishi mumkin (Docusaurus/Jekyll)
