# Changelog

All notable changes to `DataTables` will be documented in this file

# Version 2

## 2.4.5 (Current - 2026-04-20)

### 🚀 Performance & Robustness Hardening (cursor pagination + select)

Follow-up to 2.4.0 fixing latent bugs and reducing query load further.

#### ⚡ Skip `COUNT(*)` on subsequent cursor pages
- When `cursorPaginate()` is active and the request includes a `cursor`,
  both `recordsTotal` and `recordsFiltered` `COUNT(*)` queries are skipped.
- First page (no cursor) still computes counts so the UI can display totals.
- Eliminates 2 heavy queries per request after the first page on big tables.

#### 🔧 Cursor pagination robustness
- Cursor value is no longer cast to `int` — supports UUIDs, timestamps and hashes.
- Direction inferred from `$this->order[0]['dir']`: `DESC` uses `<`, `ASC` uses `>`.
- Empty/missing `cursor` no longer adds a spurious `WHERE` clause (first page works correctly).

#### 🐛 Search now applied to cursor query
- Previously `applySearchToQuery()` only affected `$filteredCount`; the paginated
  result set ignored the search filter. Fixed: paginated query reuses `$filteredQuery`.

#### 🛡️ `select()` / `exclude()` empty guard
- If the resulting column list is empty, `selectColumns` falls back to `null`
  instead of producing invalid `SELECT FROM ...` SQL.

#### 📁 Files modified
- `src/DataTables.php`
- `src/DataTablesQueryBuilders.php`

#### ✅ Backward compatibility
- 100% backward compatible.

## 2.4.0 (2026-04-20)

### 🚀 Performance: Keyset (Cursor) Pagination & SQL Push-Down

This release targets slow queries caused by deep `OFFSET` pagination and
in-memory sorting/pagination on large tables.

#### 🆕 New: `cursorPaginate()` (opt-in, backward compatible)

- Replaces `LIMIT n OFFSET m` with `WHERE column > :cursor LIMIT n` (keyset pagination)
- Eliminates the linear cost of high `OFFSET` values on large tables
- Response JSON now includes a `nextCursor` key when active
- Works with both `model()` and `query()` (Eloquent Builder) paths

```php
DataTables::query(
    Result::query()
        ->join('reports', 'results.report_id', '=', 'reports.id')
        ->where('reports.campaign_id', $campaignId)
)
->cursorPaginate('results.id')   // <- new
->get();
```

The frontend should send `?cursor=<nextCursor>` on subsequent requests.

#### ⚡ `sortModel()` push-down optimization

- When no search is active, `ORDER BY` + `LIMIT` are now pushed down to SQL
- Previously the full table was loaded into PHP and sliced in memory
- Massive reduction in memory usage and query time on large tables

#### 🛡️ Type-safety fixes

- `start` and `length` request parameters are now cast to `int` with sane defaults
  (`0` and `10` respectively) preventing silent string-coercion bugs

#### 📁 Files modified
- `src/DataTables.php`

#### ✅ Backward compatibility
- 100% backward compatible — all changes are opt-in or transparent optimizations
- Existing code works unchanged

## 2.3.0 (2026-04-12)

### 🆕 PHP 7.4+ Compatibility Enhancement

- ✅ Migrated `implode()` to `join()` for better PHP 7.4+ support
  - Updated 3 occurrences in `src/DataTables.php`
  - Line 312: Headers processing
  - Line 387: ORDER BY clause (query builder)
  - Line 766: ORDER BY clause (raw queries)
- ✅ Optimized for PHP 7.4, 8.0, 8.1, 8.2, and 8.3+
- ✅ Improved code readability and consistency
- ✅ 100% backward compatible - no breaking changes
- 📚 Updated documentation and README with comprehensive examples
- 📝 Added comprehensive changelog with all historical versions

**Why `join()` instead of `implode()`:**
- `join()` is an alias of `implode()` since PHP 5.3
- More consistent parameter order (always: separator, array)
- Better readability and clarity
- Recommended in official PHP 7.4+ documentation
- No functional difference, pure compatibility improvement

## 2.2.0 (2026-04-12)

- 📚 Initial documentation update
- 📝 Organized changelog with historical versions

## 2.0.26

- ✨ Added new feature: sorting on multiple columns
- 🐛 General stability improvements

## 2.0.25 (2024-06-04)

- ⚡ Optimized execute function for better pagination in model cases

## 2.0.24 (2021-02-25)

- ✨ Added new `query()` method for direct SQL queries
- 🎯 Support for Laravel Query Builder
- 📝 Documentation updates

## 2.0.18

- 🐛 Bug fixes

## 2.0.17

- ⚡ Performance improvements
- 🗑️ Removed cache method (not usable)
- 📝 Code improvements

## 2.0.16

- 🐛 Fixed issue #3

## 2.0.13

- ✨ Added support for multiple datatables on one page

## 2.0.11

- ✨ Added new instances to the instance check

## 2.0.7

- ✨ Added new method (caching)
- 🐛 Fixed issue with finding draw in request

## 2.0.6

- 🐛 Issue fixes
- ⚡ Performance updates

## 2.0.* (Initial v2.x releases)

- Updated to Laravel 5.6.^
- Updated requirement to PHP 7.1 or higher

---

# Version 1

## 1.0.7

- ✨ Added functionality to add model scopes to the collection
- ✅ Testing on Laravel 5.6.*
- ✅ Testing on PHP 7.2.*

## 1.0.5

- ✨ Added support for relations using the ->with() method
- 🐛 Fixed performance bugs
- ⚠️ Added some more undiscovered bugs

## 1.0.4

- ✨ Added support for sorting on relations
- 🐛 Fixed performance bugs
- 🐛 Fixed search on relations bug
- ⚠️ Added some more undiscovered bugs

## 1.0.0 - 201X-XX-XX

- 🎉 Initial release
