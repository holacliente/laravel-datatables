Holacliente laravel-datatables
==============================

[![Total Downloads](https://img.shields.io/packagist/dt/guzzlehttp/guzzle.svg?style=flat-square)](https://packagist.org/packages/holacliente/laravel-datatables)

Laravel Datatables

- Fork de ACFBentveld\DataTables
- Copia para salvar el proyecto
- Mejora en el paginate del server side

## Query Added

Ahora puedes ejecutar una consulta sql directo 25/02/2021


```php
DataTables::query("SELECT * from table_name")->get();
```
