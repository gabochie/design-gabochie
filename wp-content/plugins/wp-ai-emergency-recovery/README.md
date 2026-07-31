# Enterprise AI WordPress Emergency Recovery Plugin

Production-grade, autonomous emergency site recovery tool designed for enterprise WordPress SRE and DevOps incident response.

## Key Features
- **Frontend Crash Diagnosis**: Identifies PHP fatal errors, theme crashes, rewrite rule corruption, and database transient bottlenecks.
- **Provider Abstraction Layer**: Support for Google Gemini, Anthropic Claude, OpenAI GPT, Ollama, and Offline Diagnostics Mode.
- **Site Snapshot Engine**: Takes full state snapshots before any repair attempt.
- **Auto-Validation & Rollback**: Verifies homepage, REST API, and login page HTTP statuses after repairs. Rolls back automatically on failure.
- **WP-CLI Support**: Execute emergency recovery directly from SSH terminal via `wp ai-recovery scan` and `wp ai-recovery fix`.
