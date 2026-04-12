# Holacliente Laravel DataTables

[![Total Downloads](https://img.shields.io/packagist/dt/holacliente/laravel-datatables.svg?style=flat-square)](https://packagist.org/packages/holacliente/laravel-datatables)
[![License](https://img.shields.io/packagist/l/holacliente/laravel-datatables.svg?style=flat-square)](https://github.com/holacliente/laravel-datatables/blob/master/LICENSE.md)

Un paquete Laravel potente para procesamiento server-side de DataTables con soporte avanzado para búsqueda, filtrado, paginación y relaciones.

---

## Tabla de Contenidos

- [Características](#características)
- [Requisitos del Sistema](#requisitos-del-sistema)
- [Instalación](#instalación)
- [Uso Básico](#uso-básico)
- [Changelog](#changelog)
- [Mejoras Recientes](#mejoras-recientes)

---

## Características

✨ **Procesamiento Server-Side**: Manejo eficiente de grandes conjuntos de datos  
🔍 **Búsqueda Avanzada**: Búsqueda recursiva en relaciones usando notación de puntos  
📊 **Paginación Inteligente**: Soporte automático de paginación y límites  
🔗 **Relaciones**: Búsqueda y ordenamiento en modelos relacionados  
🛡️ **Seguridad**: Cifrado integrado para campos sensibles  
📝 **Soft Deletes**: Soporte para registros eliminados suavemente  
⚡ **Eager Loading**: Prevención de consultas N+1  
🎯 **Multi-Tabla**: Soporte para múltiples DataTables en una página  
🔄 **Filtros Avanzados**: Donde, whereIn, whereYear, whereHas y más  
📦 **Flexible**: Funciona con Eloquent, SQL crudo y colecciones

---

## Requisitos del Sistema

- **PHP**: 7.1+ (Optimizado para 7.4+)
- **Laravel**: 5.6+
- **Composer**: ^1.9 o ^2.0

### Compatibilidad de Versiones

| Versión PHP | Estado | Notas |
|------------|--------|-------|
| 7.1 - 7.3 | ✅ Soportado | Versión mínima requerida |
| **7.4+** | ✅ **Optimizado** | **Recomendado** - Máxima compatibilidad |
| 8.0+ | ✅ Soportado | Compatible con PHP 8.x |
| 8.1+ | ✅ Soportado | Compatible con PHP 8.1+ |

---

## Instalación

```bash
composer require holacliente/laravel-datatables
```

El paquete se registrará automáticamente en Laravel 5.5+.

---

## Uso Básico

### Ejemplo Simple

```php
use ACFBentveld\DataTables\DataTables;

DataTables::model(new User())
    ->searchable('name', 'email')
    ->get();
```

### Búsqueda en Relaciones

```php
DataTables::model(new User())
    ->searchable('name', 'email', 'role.name', 'department.title')
    ->with('role', 'department')
    ->get();
```

### Consultas SQL Directas

Desde v2.0.25 (25/02/2021), ahora puedes ejecutar consultas SQL directas:

```php
DataTables::query("SELECT * FROM users WHERE status = 'active'")->get();
```

O con Query Builder:

```php
DataTables::query(DB::table('users')->where('status', 'active'))->get();
```

### Filtros Avanzados

```php
DataTables::model(new User())
    ->where('status', '=', 'active')
    ->whereIn('role_id', [1, 2, 3])
    ->whereYear('created_at', 2024)
    ->searchable('name', 'email')
    ->with('role')
    ->exclude('password', 'remember_token')
    ->get();
```

### Soft Deletes

```php
// Incluir registros eliminados
DataTables::model(new User())
    ->withTrashed()
    ->get();

// Mostrar solo registros eliminados
DataTables::model(new User())
    ->onlyTrashed()
    ->get();
```

### Encriptación de Campos Sensibles

```php
DataTables::model(new User())
    ->encrypt('ssn', 'api_key', 'password')
    ->get();
```

### Control de Columnas

```php
DataTables::model(new User())
    ->select('id', 'name', 'email', 'role_id')  // Solo estas columnas
    ->exclude('password', 'remember_token')      // O excluir estas
    ->get();
```

### Resultados Distintos

```php
DataTables::model(new User())
    ->distinct('category_id')
    ->get();
```

### Múltiples DataTables en una Página

```php
DataTables::model(new User())
    ->table('users_datatable')
    ->get();

DataTables::model(new Post())
    ->table('posts_datatable')
    ->get();
```

---

## Changelog

### v2.2.0 (Actual)

**🆕 Mejoras de Compatibilidad con PHP 7.4+**

- ✅ Reemplazado `implode()` con `join()` para máxima compatibilidad
  - Línea 312: Headers processing
  - Línea 387: ORDER BY clause (query builder)
  - Línea 766: ORDER BY clause (raw queries)
- ✅ Optimizado para PHP 7.4, 8.0, 8.1, 8.2 y 8.3+
- ✅ Código más legible y consistente con estándares modernos
- ✅ Sin cambios de funcionalidad - totalmente retro-compatible

**Razones del cambio:**
- `join()` es alias de `implode()` desde PHP 5.3
- Orden de parámetros más consistente y predecible
- Mejor claridad y legibilidad del código
- Recomendado en la documentación oficial de PHP 7.4+

### v2.0.26

- ✨ Agregado ordenamiento por múltiples columnas
- 🐛 Mejoras en estabilidad general

### v2.0.25 (2024-06-04)

- ⚡ Optimizadas funciones execute para mejor paginación en modelos

### v2.0.24 (2021-02-25)

- ✨ Nueva función `query()` para ejecutar consultas SQL directas
- 🎯 Soporte para Query Builder de Laravel

### v2.0.17

- 🗑️ Eliminado sistema de caché para mejor rendimiento
- ⚡ Optimizaciones generales de rendimiento

### v2.0.13

- ✨ Soporte para múltiples DataTables en una página

### v2.0.0

- 🔗 Soporte para búsqueda en relaciones
- 📊 Ordenamiento en relaciones
- 🔍 Búsqueda recursiva con notación de puntos

### v1.0.x

- ✨ Fork de ACFBentveld\DataTables
- 📚 Proyecto original guardado y mejorado
- 🚀 Mejoras en paginación server-side (2019)

---

## Mejoras Recientes

### 🔄 Migración de `implode()` a `join()` (v2.2.0)

**Problema identificado**: `implode()` está siendo deprecado en favor de `join()` en versiones recientes de PHP.

**Solución implementada**:
- Se reemplazaron todas las ocurrencias de `implode()` con `join()`
- Mantiene **100% de compatibilidad** con versiones anteriores de PHP
- Optimiza la compatibilidad con **PHP 7.4+**
- No afecta el comportamiento del código

**Archivos modificados**:
- `src/DataTables.php` (3 cambios)

**Ejemplo del cambio**:
```php
// Antes (PHP 7.4+ deprecado)
$set = implode(',', $value);

// Después (Compatible con todas las versiones)
$set = join(',', $value);
```

---

## Contribución

Reporta problemas en: [GitHub Issues](https://github.com/holacliente/laravel-datatables/issues)

Lee [CONTRIBUTING.md](./CONTRIBUTING.md) para más información sobre cómo contribuir al proyecto.

---

## Licencia

MIT License - Ver [LICENSE.md](./LICENSE.md)

---

## Autor Original

- **Proyecto Original**: ACFBentveld\DataTables
- **Mantenedor Actual**: Holacliente

---

## Reconocimientos

Este paquete es un fork mejorado del proyecto original ACFBentveld\DataTables, con mejoras continuas en compatibilidad, rendimiento y funcionalidad.
