# AGENTIC_SPEC.md — AI Agentic REST API Bridge Specification

> **Publisher**: Gabochie Design (Accra, Ghana HQ)  
> **Target Platform**: WordPress 7.0+ & Model Context Protocol (MCP)  
> **Plugin Name**: `gabochie-ai-agentic-bridge`  
> **Version**: 2.5.0-Bridge  

---

## 🎯 Purpose & Scope

Exposes secure REST API & MCP Server Connector routes for Google Gemini 2.5 Flash, Application Passwords validation, and AI Agent Studio communication in WordPress 7.0+.

---

## 🔌 Core REST API Endpoints

### 1. Model Brand Token Generation (`POST /wp-json/gabochie-ai/v1/generate-brand`)
- **Headers**: `Authorization: Basic <base64(username:app_password)>`
- **Body**:
```json
{
  "prompt": "Pan-African Luxury Ecommerce Brand"
}
```
- **Response**:
```json
{
  "success": true,
  "plugin": "gabochie-ai-agentic-bridge",
  "model": "gemini-2.5-flash",
  "result": "Generated design tokens..."
}
```

### 2. MCP Status Check (`GET /wp-json/gabochie-ai/v1/mcp-status`)
- **Public Endpoint**: Returns status, active endpoints, PHP version, and WP 7.0+ status.

---

## 🛠️ Instructions for Senior Dev AI Agents

- To connect your custom agent or MCP tool to WordPress, use Application Passwords generated under **WP Admin → Users → Profile → Application Passwords**.
- Send HTTP requests directly to `/wp-json/gabochie-ai/v1/` routes.
