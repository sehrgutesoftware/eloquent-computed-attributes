<?php

namespace SehrGut\EloquentComputeOnSave;

use Illuminate\Support\Str;
use ReflectionMethod;

/**
 * Update computed attributes on save.
 *
 * This is an alternative to "accessors", which are calculated when the
 * value is retrieved from the model. These "computed attributes" are
 * computed when the model is saved (and persisted to the database).
 *
 * Sometimes, it may be necessary to recompute these values in other
 * events than the actual model "save". For that purpose, the two
 * public methods `recompute` and `recomputeAsync` can be used.
 *
 * Computed attributes are declared in the following manner: If you
 * need a computed attribute that is called, let's say `excerpt`,
 * which is dependent on the value of `text`, then you define
 * a method on your model called `computeExcerptAttribute`.
 *
 * The method takes (string $text) as its argument. Then, when the
 * model is "saving", this trait will check whether $this->text
 * is dirty, and if so, will call the computeExcerptAttribute
 * method, passing it the new value for `text` and storing
 * its return value in the `excerpt` column to the DB.
 */
trait ComputesOnSave
{
    /**
     * Cache of the names of the computed attributes.
     *
     * @var array|null
     */
    protected static $computedAttributeNames = null;

    /**
     * Boot the trait.
     *
     * @return void
     */
    public static function bootComputesOnSave()
    {
        static::saving(function ($model) {
            $model->recomputeDirty();
        });
    }

    /**
     * Recompute all computed attributes on the model.
     *
     * @return $this
     */
    public function recompute()
    {
        foreach ($this->getAttributesComputedOnSave() as $recomputeMethodName => $attributeName) {
            $args = $this->getDependencyValues($recomputeMethodName);
            $this->{$attributeName} = $this->{$recomputeMethodName}(...$args);
        }

        return $this;
    }

    /**
     * Recompute all computed attributes on the model, which have dirty dependencies.
     *
     * @return $this
     */
    public function recomputeDirty()
    {
        foreach ($this->getAttributesComputedOnSave() as $recomputeMethodName => $attributeName) {
            if ($this->areDependenciesDirty($recomputeMethodName)) {
                $args = $this->getDependencyValues($recomputeMethodName);
                $this->{$attributeName} = $this->{$recomputeMethodName}(...$args);
            }
        }

        return $this;
    }

    /**
     * Recompute all computed attributes on the model.
     *
     * @return $this
     */
    public function recomputeAsync()
    {
        RecomputeJob::dispatch($this);

        return $this;
    }

    /**
     * Get the names of the computed attributes and the
     * name of their corresponding "recompute method".
     *
     * @return array
     */
    protected function getAttributesComputedOnSave(): array
    {
        // Return them from cache, if present
        if (!is_null(static::$computedAttributeNames)) {
            return static::$computedAttributeNames;
        }
        static::$computedAttributeNames = [];

        $methods = array_filter(get_class_methods(static::class), function ($methodName) {
            // Pick all methods that start with 'compute' and end with 'Attribute'
            // and have at least one character in between.
            return substr($methodName, 0, 7) === "compute" AND
                substr($methodName, -9) === "Attribute" AND
                strlen($methodName) > 16;
        });


        foreach ($methods as $methodName) {
            // Build an array of 'computeFieldNameAttribute' => 'field_name'
            // pairs, mapping compute method names to attribute names.
            $attributeName = Str::snake(substr($methodName, 7, -9));
            static::$computedAttributeNames[$methodName] = $attributeName;
        }

        return static::$computedAttributeNames;
    }

    /**
     * Get method arguments if at least one of them is dirty.
     *
     * @param  string $methodName
     * @return bool
     */
    protected function areDependenciesDirty(string $methodName): bool
    {
        $reflectionMethod = new ReflectionMethod($this, $methodName);

        foreach ($reflectionMethod->getParameters() as $argument) {
            // $relation = $this->getRelation($argument->name);
            // if ($relation and $this->isRelationshipDirty($relation)) {
            //     return true
            // }
            if ($this->isDirty($argument->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get method argument values for given method.
     *
     * @param  string $methodName
     * @return array
     */
    protected function getDependencyValues(string $methodName): array
    {
        $reflectionMethod = new ReflectionMethod($this, $methodName);

        return array_map(function ($argument) {
            return $this->{$argument->name};
        }, $reflectionMethod->getParameters());
    }

    // protected function isRelationshipDirty($relationship): bool
    // {

    // }
}
