<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/TimesDataPerEntity.php';
require_once __DIR__ . '/../Model/TimesData.php';


use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\TimesDataPerEntity;


final class TimesDataPerEntityTest extends TestCase
{
    public function testBasicFunctionality()
    {
        $tdpe = new TimesDataPerEntity();
        $this->assertFalse(
            $tdpe->hasTimes(),
            'TimesDataPerEntity->hasTimes() should return false directly after init.'
        );

        // project 1 would have now
        //      10 estimated
        //      3  spent
        //      7  remaining
        //      0  overtime
        $tdpe->addTimes(5, 3, 2, 0, 1);
        $tdpe->addTimes(5, 0, 5, 0, 1);

        $this->assertTrue(
            $tdpe->hasTimes(1),
            'TimesDataPerEntity->hasTimes() should return true after adding times.'
        );

        // project 2 would have now
        //      8    estimated
        //      8.5  spent
        //      0    remaining
        //      0.5  overtime
        $tdpe->addTimes(5, 6, 0, 1, 2);
        $tdpe->addTimes(3, 2.5, 0, -0.5, 2);

        // in total
        //      18    estimated
        //      11.5  spent
        //      7    remaining
        //      0.5  overtime
        $msg = 'TimesDataPerEntity basic calculations not as intended.';

        // project 1 - float
        $this->assertSame(10.0, $tdpe->getEstimated(1), $msg);
        $this->assertSame(3.0, $tdpe->getSpent(1), $msg);
        $this->assertSame(7.0, $tdpe->getRemaining(1), $msg);
        $this->assertSame(0.0, $tdpe->getOvertime(1), $msg);

        // project 2 - float
        $this->assertSame(8.0, $tdpe->getEstimated(2), $msg);
        $this->assertSame(8.5, $tdpe->getSpent(2), $msg);
        $this->assertSame(0.0, $tdpe->getRemaining(2), $msg);
        $this->assertSame(0.5, $tdpe->getOvertime(2), $msg);

        // all - float
        $this->assertSame(18.0, $tdpe->getEstimated(), $msg);
        $this->assertSame(11.5, $tdpe->getSpent(), $msg);
        $this->assertSame(7.0, $tdpe->getRemaining(), $msg);
        $this->assertSame(0.5, $tdpe->getOvertime(), $msg);

        // project 1 - readable
        $this->assertSame('10:00', $tdpe->getEstimated(1, true), $msg);
        $this->assertSame('3:00', $tdpe->getSpent(1, true), $msg);
        $this->assertSame('7:00', $tdpe->getRemaining(1, true), $msg);
        $this->assertSame('0:00', $tdpe->getOvertime(1, true), $msg);

        // project 2 - readable
        $this->assertSame('8:00', $tdpe->getEstimated(2, true), $msg);
        $this->assertSame('8:30', $tdpe->getSpent(2, true), $msg);
        $this->assertSame('0:00', $tdpe->getRemaining(2, true), $msg);
        $this->assertSame('0:30', $tdpe->getOvertime(2, true), $msg);

        // all - readable
        $this->assertSame('18:00', $tdpe->getEstimated(-1, true), $msg);
        $this->assertSame('11:30', $tdpe->getSpent(-1, true), $msg);
        $this->assertSame('7:00', $tdpe->getRemaining(-1, true), $msg);
        $this->assertSame('0:30', $tdpe->getOvertime(-1, true), $msg);

        $this->assertTrue(
            $tdpe->hasTimes(),
            'TimesDataPerEntity->hasTimes() should return true after adding times.'
        );

        // resetting only one project should make it not have times anymore
        $tdpe->resetTimes(1);
        $this->assertFalse(
            $tdpe->hasTimes(1),
            'TimesDataPerEntity->hasTimes() should return false after resetting times.'
        );

        // yet in generall gthe whole TimesDataPerEntity wrapper should still
        // have times
        $this->assertTrue(
            $tdpe->hasTimes(),
            'TimesDataPerEntity->hasTimes() should still return true after resetting only one project.'
        );

        // but after resetting this as well, nothing should have times anymore
        $tdpe->resetTimes();
        $this->assertFalse(
            $tdpe->hasTimes(),
            'TimesDataPerEntity->hasTimes() should return false after resetting everything.'
        );
    }

    public function testSorting()
    {
        $tdpe = new TimesDataPerEntity();

        $tdpe->addTimes(6, 3, 2, 0, 2);
        $tdpe->addTimes(2, 0, 5, 0, 2);
        $tdpe->addTimes(5, 3, 2, 0, 1);
        $tdpe->addTimes(8, 2, 5, 1, 1);

        $msg = 'TimesDataPerEntity sorting went wrong.';

        $tdpe->sort();
        $this->assertSame(1, $tdpe->getEntities()[0], $msg);
        $this->assertSame(2, $tdpe->getEntities()[1], $msg);

        $tdpe->sort(direction: 'desc');
        $this->assertSame(2, $tdpe->getEntities()[0], $msg);
        $this->assertSame(1, $tdpe->getEntities()[1], $msg);

        $tdpe->sort('estimated', 'asc');
        $this->assertSame(2, $tdpe->getEntities()[0], $msg);
        $this->assertSame(1, $tdpe->getEntities()[1], $msg);

        // quick check, if the entity on that internal arrays position
        // really is the one with the expected estimated time
        $this->assertSame(13.0, $tdpe->getEstimated($tdpe->getEntities()[1]), $msg);

        // I won't test all sortin methods, but just one more
        $tdpe->sort('overtime', 'desc');
        $this->assertSame(5.0, $tdpe->getSpent($tdpe->getEntities()[0]), $msg);
        $this->assertSame(3.0, $tdpe->getSpent($tdpe->getEntities()[1]), $msg);
    }
}
