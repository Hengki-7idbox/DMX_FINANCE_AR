# Graph Canvas Guide

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Panduan penggunaan Graph Canvas untuk visualisasi data relationship dalam Finance AR.

## Supported Visualizations

### 1. Customer Relationship Graph

```text
Customer
   │
   ├──< Invoice
   ├──< Payment
   ├──< Collection Activity
   └──< Promise to Pay
```

### 2. Payment Flow Graph

```text
Invoice
   │
   ▼
Payment Allocation
   │
   ▼
Payment
   │
   ▼
Bank Transaction
   │
   ▼
Reconciliation
```

### 3. Aging Timeline

```text
Current → 1-30 → 31-60 → 61-90 → 91-120 → >120
```

## Canvas Features

### Zoom Controls

```text
- Scroll to zoom in/out
- Double-click to reset zoom
- Pinch to zoom on mobile
```

### Drag & Drop

```text
- Drag nodes to reposition
- Hold Shift for multi-select
- Double-click node to view details
```

### Filtering

```text
- Filter by date range
- Filter by customer
- Filter by status
- Filter by amount range
```

## Node Types

```text
Customer:    Circle, Blue
Invoice:     Rectangle, Green
Payment:     Diamond, Yellow
Bank:        Hexagon, Purple
Collection:  Triangle, Orange
```

## Edge Types

```text
Belongs to:  Solid line
Has many:    Dashed line
Linked to:   Dotted line
```

## Export Options

```text
- PNG image
- SVG vector
- PDF document
- JSON data
```

## Usage Examples

### View Customer Graph

```text
1. Navigate to Customer → Select Customer
2. Click "View Graph"
3. Select visualization type
4. Apply filters if needed
```

### Analyze Payment Flow

```text
1. Navigate to Payment → Select Payment
2. Click "View Flow"
3. See complete payment chain
4. Click nodes for details
```

## Performance

```text
- Maximum nodes: 1000
- Recommended: < 500 nodes
- For larger datasets, use pagination
```

## Keyboard Shortcuts

```text
F: Fit to screen
R: Reset view
+/-: Zoom in/out
Space: Pan mode
Esc: Close graph
```
