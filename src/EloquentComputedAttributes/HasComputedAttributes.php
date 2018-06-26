<?php

namespace SehrGut\EloquentComputedAttributes;

use Illuminate\Support\Str;
use ReflectionMethod;
use SehrGut\EloquentComputedAttributes\Contracts\Recomputable;

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
trait HasComputedAttributes
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
    public static function bootHasComputedAttributes()
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
        foreach ($this->getComputedAttributes() as $recomputeMethodName => $attributeName) {
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
        foreach ($this->getComputedAttributes() as $recomputeMethodName => $attributeName) {
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
        RecomputeAttributes::dispatch($this);

        return $this;
    }

    /**
     * Get the names of the computed attributes and the
     * name of their corresponding "compute method".
     *
     * @return array
     */
    protected function getComputedAttributes(): array
    {
        // Return them from cache, if present
        if (!is_null(static::$computedAttributeNames)) {
            return static::$computedAttributeNames;
        }

        $methods = $this->getComputeMethodNames();

        return static::$computedAttributeNames = $this->mapMethodNamesToAttributeNames($methods);
    }

    /**
     * Get the names of all methods that define a computed attribute.
     *
     * Those are defined as:
     *     "All methods whose name starts with 'compute', ends with
     *     'Attribute' and has at least one character between."
     *
     * @return array
     */
    protected function getComputeMethodNames(): array
    {
        return array_filter(get_class_methods(static::class), function ($methodName) {
            return substr($methodName, 0, 7) === "compute" and
                substr($methodName, -9) === "Attribute" and
                strlen($methodName) > 16;
        });
    }

    /**
     * Map "compute method" names to their corresponding attribute names.
     *
     * Returns 'computeFieldNameAttribute' => 'field_name' pairs.
     *
     * @param  array  $methodNames
     * @return array
     */
    protected function mapMethodNamesToAttributeNames(array $methodNames): array
    {
        $map = [];

        foreach ($methodNames as $methodName) {
            $attributeName = Str::snake(substr($methodName, 7, -9));
            $map[$methodName] = $attributeName;
        }

        return $map;
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

    // protected function isRelationshipDirty($relationship): bool
    // {

    // }

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
}
