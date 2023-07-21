# api Face Relations php class

## Installation
You can install this plugin into your application using
[composer](https://getcomposer.org):

```
  composer require fawno/face-relations @stable
```

## Usage

```php
  use Fawno\FaceRelations\apiRelations;

  // Check NIF
  $nif = 'S3100015A';
  $result = apiRelations::nif_validation($nif) ? 'Valid' : 'Invalid';

  // Get relations by NIF (CIF)
  var_dump(apiRelations::query(['cif' => 'ESS3100015A']));

  // Get relations by OC
  var_dump(apiRelations::query(['oc' => 'GE0001267']));

  // Get relations by OG
  var_dump(apiRelations::query(['og' => 'A15002920']));

  // Get relations by UT
  var_dump(apiRelations::query(['ut' => 'A15007700']));

  // Get relations by NIF, OC, OG and UT
  var_dump(apiRelations::query(['cif' => 'S3100015A', 'oc' => 'GE0001267', 'og' => 'A15002920', 'ut' => 'A15007701']));

```
