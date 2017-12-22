<?php

namespace Weblabltd\Component\Util\Tests\Collection;

use Weblabltd\Component\Util\Collection\PrioritizedObjectList;

use PHPUnit\Framework\TestCase;

class PrioritizedObjectListTest extends TestCase
{
    /**
     * @var PrioritizedObjectList
     */
    private $objectList;

    public function setUp()
    {
        $this->objectList = new PrioritizedObjectList();
    }

    public function testCount()
    {
        $this->assertCount(0, $this->objectList);
    }

    public function testValid()
    {
        $this->assertSame(false, $this->objectList->valid());
    }

    public function testCurrentEmpty()
    {
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('There is no object at the current position');

        $this->objectList->current();
    }

    public function testKeyEmpty()
    {
        $this->assertSame(0, $this->objectList->key());
    }

    public function testNextEmpty()
    {
        $this->objectList->next();

        $this->assertSame(1, $this->objectList->key());
    }

    /**
     * @depends testCount
     * @depends testValid
     * @depends testCurrentEmpty
     * @depends testKeyEmpty
     */
    public function testAdd()
    {
        $object = new \stdClass();

        $this->objectList->add($object);

        $this->assertCount(1, $this->objectList);
        $this->assertSame(true, $this->objectList->valid());
        $this->assertSame($object, $this->objectList->current());

        return $this->objectList;
    }

    /**
     * @depends testAdd
     */
    public function testIteratorPopulated(PrioritizedObjectList $objectList)
    {
        $object = new \stdClass();

        $this->assertSame(0, $objectList->key());
        $this->assertNotSame($object, $objectList->current(), 'Test subjects are identical');

        $objectList->add($object);
        $this->assertCount(2, $objectList);

        // iterate to the next object
        $objectList->next();

        $this->assertSame(1, $objectList->key());
        $this->assertSame($object, $objectList->current());

        // rewind iterator
        $objectList->rewind();

        $this->assertSame(0, $this->objectList->key());
        $this->assertNotSame($object, $objectList->current());
    }

    /**
     * @depends testIteratorPopulated
     */
    public function testAddPriorityOverride()
    {
        $subject1 = new \stdClass();
        $subject2 = new \stdClass();

        $this->assertNotSame($subject1, $subject2);

        $this->objectList->add($subject1);

        $this->assertCount(1, $this->objectList);
        $this->assertSame(0, $this->objectList->key());
        $this->assertNotSame($subject2, $this->objectList->current(), 'Test subjects are identical');

        // add the subject with default priority
        $this->objectList->add($subject2);

        // nothing changed so far
        $this->assertCount(2, $this->objectList);
        $this->assertSame(0, $this->objectList->key());
        $this->assertNotSame($subject2, $this->objectList->current(), 'Test subjects are identical');

        $this->objectList->add($subject2, 500);

        // not really added
        $this->assertCount(2, $this->objectList);
        // key is still at the head
        $this->assertSame(0, $this->objectList->key());
        // but our subject got priority pass
        $this->assertSame($subject2, $this->objectList->current());
    }

    public function listProvider(): array
    {
        // mini factory to save boilerplate
        $f = function(string $id, int $priority = null) {
            $o = new \stdClass();
            $o->id = $id;

            return [$o, $priority];
        };

        return [
            // priority ordered
            [[$f('a', 20), $f('b', 40), $f('c', 60)], ['c', 'b', 'a']],
            // reverse priority
            [[$f('a', 60), $f('b', 20), $f('c', 10)], ['a', 'b', 'c']],

            // all same priority
            [[$f('a'), $f('b'), $f('c')], ['a', 'b', 'c']],
            [[$f('z'), $f('y'), $f('x')], ['z', 'y', 'x']],

            // mixed
            [[$f('z'), $f('a', 200), $f('x', 0), $f('b', 300), $f('c')], ['b', 'a', 'z', 'c', 'x']],

            [
                [
                    $f('a', 150),
                    $f('b'),
                    $f('c'),
                    $f('d'),
                    $f('e'),
                    $f('a2', 110),
                    $f('f'),
                    $f('a3', 110),
                    $f('g'),
                    $f('a4', 110),
                    $f('a5', 110),
                ],

                ['a', 'a2', 'a3', 'a4', 'a5', 'b', 'c', 'd', 'e', 'f', 'g']
            ]
        ];
    }

    /**
     * @depends testIteratorPopulated
     * @dataProvider listProvider
     */
    public function testAddAndSort(array $objects, array $expectedOrder)
    {
        foreach ($objects as $object) {
            $this->objectList->add($object[0], $object[1]);
        }

        $this->assertSame(count($expectedOrder), count($this->objectList));

        $idx = 0;
        foreach ($this->objectList as $object) {
            $this->assertInstanceOf(\stdClass::class, $object);
            $this->assertAttributeSame($expectedOrder[$idx], 'id', $object);

            ++$idx;
        }
    }

    public function addInvalidProvider(): array
    {
        $subject = new \stdClass();

        return [
            [$subject, -12, 'The priority should be a positive integer or null'],
        ];
    }

    /**
     * @dataProvider addInvalidProvider
     */
    public function testAddInvalid($object, $priority, $message)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);


        $this->objectList->add($object, $priority);
    }
}
