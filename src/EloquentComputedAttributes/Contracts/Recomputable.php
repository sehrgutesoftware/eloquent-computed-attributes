<?php

namespace SehrGut\EloquentComputedAttributes\Contracts;

interface Recomputable
{
    /**
     * Recompute attributes without saving.
     *
     * @return mixed
     */
    public function recompute();

    /**
     * Recompute attributes in an async job handled by queue workers.
     *
     * @return mixed
     */
    public function recomputeAsync();

    /**
     * Persist the object's attributes.
     *
     * @return mixed
     */
    public function save();
}
