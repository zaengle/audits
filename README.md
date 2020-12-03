![Tests](https://github.com/zaengle/audits/workflows/Tests/badge.svg?branch=master)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/zaengle/audits.svg?style=flat-square)](https://packagist.org/packages/zaengle/audits)
[![Total Downloads](https://img.shields.io/packagist/dt/zaengle/audits.svg?style=flat-square)](https://packagist.org/packages/zaengle/audits)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

![audit header](audit-header.jpg)

# Audits
This packages records changes to a Laravel model and stores them in a json column on the model.

## Usage

Use the `MakesAudits` trait on your model.

Next add a nullable json column to your model. By default the package will look for a column called `audits`. To override the auditable column, change the `$auditableColumn` property on your model.
```
protected $auditableColumn = 'audits';
```

Finally add a `json` cast for the auditable column.
```
protected $casts = [
    'audits' => 'json',
];
```



## Attributions

If you are looking for a more robust solution, this package is worth checking out: [Laravel Auditor](http://www.laravel-auditing.com/docs/9.0/auditor)
