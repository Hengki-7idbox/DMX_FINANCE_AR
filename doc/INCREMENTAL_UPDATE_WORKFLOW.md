# Incremental Update Workflow

**Version:** 1.0  
**Date:** 22 September 2026

---

## Overview

Workflow untuk update data secara incremental tanpa menghapus data existing.

## Principles

```text
1. Never delete existing data
2. Always track changes
3. Maintain audit trail
4. Support rollback
```

## Import Incremental

### Upload

```text
1. User uploads new file
2. System parses data
3. System compares with existing
4. System identifies changes
```

### Comparison

```text
For each record:
- New record → INSERT
- Existing record, changed → UPDATE
- Existing record, unchanged → SKIP
```

### Preview

```text
Summary:
- New records: 50
- Updated records: 120
- Unchanged records: 1000
- Failed records: 5
```

### Apply

```text
1. Start transaction
2. Insert new records
3. Update changed records
4. Create audit log
5. Commit transaction
```

## Database Incremental

### Migration Strategy

```text
- Use versioned migrations
- Never modify existing migrations
- Create new migration for changes
- Test rollback before deploy
```

### Schema Changes

```text
1. Create new table/column
2. Migrate data
3. Update application code
4. Drop old table/column (optional)
```

## Data Sync

### Internal to External

```text
1. Identify changed records
2. Compare with external
3. Sync differences
4. Log sync activity
```

### External to Internal

```text
1. Fetch new data from external
2. Compare with internal
3. Update internal data
4. Log sync activity
```

## Conflict Resolution

```text
Strategy: Last Write Wins

1. Check timestamp
2. Newer record wins
3. Log conflict
4. Notify user if needed
```

## Audit Trail

```text
Every incremental update must log:
- What changed
- Who changed it
- When it changed
- Old value
- New value
```

## Rollback

```text
1. Check audit log
2. Identify changes to rollback
3. Apply reverse changes
4. Verify data integrity
```
