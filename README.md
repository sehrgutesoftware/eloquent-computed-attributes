# Eloquent Computed Attributes

> Automatically compute attributes on Laravel Eloquent models when their input changes

*Computed attributes* provide an alternative to Laravel's built-in *accessors*. In contrast to accessors, *computed attributes* are not computed when their value is requested, but rather when their dependencies change (*computed attributes* are persisted in the DB). It should be preferred in cases of resource intensive computations.

It works by listening for a models' `saving` event and checking whether the dependencies of a *computed attribute* are dirty. If so, the attribute is recomputed and its new value is updated in the database.

Dependencies of a computed attribute are declared through the signature of the "compute" method. Argument names in that method correspond to attributes on the model. See below for an example on how this works.

**Example Use Cases:**
- Render markdown fields as html
- Save excerpts of longer texts
- Geocoding of addresses on update

**Comparison with other options:**

Method | Use Case | Computation time
--- | --- | --- 
[Laravel Accessors](https://laravel.com/docs/5.6/eloquent-mutators#accessors-and-mutators) | Simple transformations (affected by n+1 issue) | On read
[laravel-computed-properties](https://github.com/n7olkachev/laravel-computed-properties) | Querieable attributes that should be computed on GET (avoids n+1 issue). | On read
This package | Time consuming operations or operations involving external API calls | On write

## Getting Started

### 1. Installation
```
composer require sehrgut/eloquent-computed-attributes
```

### 2. Define a *computed attribute*

Let's say we have a `Post` model with a `text` column and a `text_excerpt` column. Every time the value of `text` changes, we want to recompute the `text_excerpt` column. This is what our `Post` class could look like:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SehrGut\EloquentComputedAttributes\HasComputedAttributes;

class Post extends Model
{
    use HasComputedAttributes;

    /** @inheritDoc */
    protected $fillable = ['title', 'text'];

    /**
     * Recompute the `text_excerpt` attribute when `text` changes.
     *
     * @param  string $text
     * @return string
     */
    public function computeTextExcerptAttribute(string $text): string
    {
        return substr($text, 0, 255) . '…';
    }
}
```

#### What's happening under the hood:

1. The `HasComputedAttributes` trait recognises that by defining `computeTextExcerptAttribute`, we want to compute a `text_excerpt` attribute (following the naming convention of [Accessors & Mutators](https://laravel.com/docs/5.6/eloquent-mutators#accessors-and-mutators))
2. The trait derives the computed attributes' dependencies from the method signature: If `$this->text` changes (`$text` being part of the method signature), `text_excerpt` needs to be recomputed
3. On each `saving` event of the model, if any of the computed attributes' dependencies (arguments of the method) have changed, the method will be called with the updated values as arguments, and its return value will be assigned to `$this->text_excerpt` before the models' `save()` method is called.
