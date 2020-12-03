# Audits
A simple package to audit models.

## Usage

Use the `MakesAudits` trait on your model.

Next add a nullable json column to your model. By default the package will look for a column called `audits`. To change the auditable column, change the `$auditableColumn` property on your model.
```
protected $auditableColumn = 'audits';
```

Finally add a `json` cast for the auditable column.
```
protected $casts = [
    'audits' => 'json',
];
```
