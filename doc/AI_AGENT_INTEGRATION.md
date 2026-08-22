# AI Agent Integration

## Overview
Dokumentasi integrasi AI Agent (Gitnexus) dengan AR Finance Tools untuk asisten development dan maintenance.

## Architecture

```
┌──────────────────────────────────────────┐
│           AI Agent (Gitnexus)            │
│  - Code understanding                    │
│  - Query suggestion                      │
│  - Refactoring recommendation            │
└──────────────┬───────────────────────────┘
               │
       ┌───────▼────────┐
       │  AR Finance     │
       │  Laravel App     │
       │  - API Layer     │
       │  - Models        │
       │  - Controllers   │
       └─────────────────┘
```

## Supported Operations

### 1. Code Analysis
- Analisis struktur model & relasi
- Identifikasi N+1 query problems
- Suggest index optimization

### 2. Query Assistance
- Generate Eloquent queries dari deskripsi
- Optimasi slow queries
- Suggest proper eager loading

### 3. Documentation Sync
- Auto-generate API docs dari routes
- Sync model relationships ke knowledge graph
- Update DATABASE.md dari migration files

### 4. Bug Detection
- Identify potential SQL injection
- Detect missing validation rules
- Flag unused routes/controllers

## Integration Points

| Component | Integration | Protocol |
|-----------|-------------|----------|
| Laravel App | Codebase access | File system read |
| MySQL Database | Schema inspection | `SHOW CREATE TABLE` |
| Gitnexus | AI memory | Gitnexus API |
| Obsidian | Project docs | Markdown files |

## Memory Sync Protocol

### Project Memory (Obsidian)
- Format: Markdown files di `doc/` folder
- Update frequency: Setiap perubahan besar
- Scope: Architecture, decisions, roadmaps

### AI Memory (Gitnexus)
- Format: Structured knowledge
- Update frequency: Setiap selesai task
- Scope: Code patterns, bugs, solutions

### Knowledge Graph (Graphify)
- Format: Entity-relationship graphs
- Update frequency: Setiap perubahan schema
- Scope: Model relationships, data flow

## Usage Guidelines

### DO
- Selalu refer ke `PRD_Sistem.md` untuk business rules
- Cek `DATABASE.md` sebelum buat query
- Consult `BUSINESS_RULES.md` untuk validasi
- Review `ROUTE_MAP.md` sebelum tambah endpoint

### DON'T
- Jangan bypass authentication
- Jangan hardcode credentials
- Jangan skip validation
- Jangan direct DB queries tanpa Eloquent
