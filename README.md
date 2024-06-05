Holacliente laravel-datatables
==============================

[![Total Downloads](https://img.shields.io/packagist/dt/holacliente/laravel-datatables.svg?style=flat-square)](https://packagist.org/packages/holacliente/laravel-datatables)

Laravel Datatables

- Fork de ACFBentveld\DataTables
- Copia para salvar el proyecto
- Mejora en el paginate del server side 2019
- Funcion query para mostrar resultados de una consulta sql 25/02/2021

## 2024-06-04

- optimize execute function pagination in model case

## 2021-02-25

Ahora puedes ejecutar una consulta sql directo 


```php
DataTables::query("SELECT * from table_name")->get();
```
