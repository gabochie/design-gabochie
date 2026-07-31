# AI Growth OS — Software Architecture

## 1. System Overview

AI Growth OS is an enterprise WordPress plugin that transforms any WordPress site into a self-improving AI business operating system. It employs a multi-agent AI architecture, event-driven domain logic, and clean architecture principles.

## 2. Architectural Style

- **Clean Architecture** (Hexagonal/Onion) — domain core isolated from infrastructure
- **Event-Driven** — async communication between agents and subsystems
- **CQRS** — segregated read/write models for dashboards and analytics
- **Plugin + Composer** — PSR-4 autoloaded, WordPress as a dependency

## 3. Layer Diagram

```
┌─────────────────────────────────────────────────┐
│                 PRESENTATION                     │
│  Admin UI / Dashboard / Gutenberg / REST / CLI  │
├─────────────────────────────────────────────────┤
│                 APPLICATION                      │
│  Services / Use Cases / DTOs / Middleware        │
├─────────────────────────────────────────────────┤
│                   DOMAIN                         │
│  Entities / VOs / Aggregates / Events / Repos    │
├─────────────────────────────────────────────────┤
│                INFRASTRUCTURE                    │
│  AI Providers / DB / Cache / Queue / Logging     │
└─────────────────────────────────────────────────┘
```

## 4. Subsystems (Bounded Contexts)

| Context | Responsibility |
|---------|---------------|
| **AI Orchestration** | Provider abstraction, model routing, streaming, fallback |
| **Agent Engine** | Multi-agent lifecycle, memory, planning, tool execution |
| **Audit Engine** | Business & technical audit pipelines |
| **Dark Funnel** | Hidden buying-behaviour estimation & attribution |
| **Flywheel Engine** | Referral, review, loyalty & virality optimisation |
| **Reverse Engineering** | Website analysis, bottleneck detection, recommendations |
| **Dashboard** | Executive metrics, scores, priorities, revenue impact |
| **Workflow Engine** | Scheduled/event-driven autonomous workflows |
| **Observability** | Metrics, tracing, structured logging, health checks |
| **Security** | Nonce, CSRF, RBAC, encryption, audit logs |

## 5. Technology Stack

- **Language**: PHP 8.2+
- **Framework**: WordPress plugin + Composer + PSR-4
- **AI SDK**: Custom provider abstraction (OpenAI, Anthropic, Gemini, Ollama, OpenRouter)
- **Queue**: Action Scheduler / WP Cron (deferred)
- **Cache**: WP Transients + Object Cache API
- **Database**: Custom tables with prepared SQL + WPDB
- **Testing**: PHPUnit (unit/integration), Playwright (E2E)
- **Static Analysis**: PHPStan level max, Rector
- **Frontend**: React (Gutenberg), vanilla JS dashboard
