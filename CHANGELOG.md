# Changelog

All notable changes to `DataTables` will be documented in this file

# Version 2

## 2.2.0 (Current - 2026-04-12)

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
