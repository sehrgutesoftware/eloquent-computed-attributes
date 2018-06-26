<?php

namespace Tests\Unit;

use SehrGut\EloquentComputedAttributes\HasComputedAttributes;
use Tests\TestCase;

class HasComputedAttributesTest extends TestCase
{
    public function test_it_boots_the_trait()
    {
        $mock = new HasComputedAttributesMock();
        $mock->bootHasComputedAttributes();
        $closure = $mock::$savingArgument;

        // Assert that a closure is registered for the "saving"
        // event of the class (model) that uses the Trait
        $this->assertInstanceOf(\Closure::class, $closure);

        $model = $this->getMockBuilder(HasComputedAttributesMock::class)
            ->setMethods(['recomputeDirty'])
            ->getMock();

        // Assert that the `recomputeDirty()` method
        // on the Trait is called by that closure
        $model->expects($this->once())
            ->method('recomputeDirty');
        $closure($model);
    }
}
