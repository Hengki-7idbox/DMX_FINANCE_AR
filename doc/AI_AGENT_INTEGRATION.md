# AI Agent Integration

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Dokumentasi integrasi AI Agent dalam sistem Finance AR untuk otomatisasi dan assistensi.

## AI Use Cases

### 1. Collection Assistant

```text
- Generate draft pesan collection
- Personalisasi bahasa berdasarkan customer
- Rekomendasi waktu follow-up
- Rangkum respons customer
```

### 2. Payment Matching

```text
- Sugest invoice yang cocok dengan bank transaction
- Deteksi pola pembayaran
- Auto-classify selisih
```

### 3. Aging Analysis

```text
- Prediksi risiko overdue
- Rekomendasi prioritas collection
- Trend analysis
```

### 4. Reconciliation Helper

```text
- Auto-suggest matching
- Deteksi anomali
- Klasifikasi selisih
```

## Integration Architecture

```text
User Request
     ↓
AI Agent Service
     ↓
┌─────────────────────┐
│ - Context Builder    │
│ - Prompt Template    │
│ - Response Parser    │
│ - Action Executor    │
└─────────────────────┘
     ↓
Finance AR API
     ↓
Response to User
```

## Prompt Templates

### Collection Message

```text
Buatkan pesan collection untuk:
Customer: {customer_name}
Invoice: {invoice_number}
Outstanding: {outstanding_amount}
Days Overdue: {days_overdue}
Tone: {tone} (formal/semi-formal/friendly)
```

### Payment Suggestion

```text
Berdasarkan bank transaction:
Customer: {customer_name}
Amount: {amount}
Reference: {reference}

Suggest invoice yang paling cocok untuk pembayaran ini.
```

## AI Response Format

```json
{
  "suggestion": "string",
  "confidence": 0.85,
  "reasoning": "string",
  "actions": [
    {
      "type": "create_payment",
      "params": {}
    }
  ]
}
```

## Human Approval

```text
AI Suggestion → Preview → User Approval → Execute
```

## Audit Trail

Semua aktivitas AI harus tercatat:

```text
- AI suggestion
- User decision
- Execution result
- Timestamp
```
