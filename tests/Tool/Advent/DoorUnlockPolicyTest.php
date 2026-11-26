<?php

declare(strict_types=1);

namespace App\Tests\Tool\Advent;

use App\Tool\Advent\Entity\AdventCalendar;
use App\Tool\Advent\Service\DoorUnlockPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class DoorUnlockPolicyTest extends TestCase
{
    private static function calendar(string $start = '2026-12-01', string $timezone = 'Europe/Berlin'): AdventCalendar
    {
        return new AdventCalendar('Test', (int) substr($start, 0, 4), new \DateTimeImmutable($start))->setTimezone($timezone);
    }

    private static function policy(string $now): DoorUnlockPolicy
    {
        return new DoorUnlockPolicy(new MockClock(new \DateTimeImmutable($now)));
    }

    public function testNothingIsOpenBeforeTheStart(): void
    {
        $policy = self::policy('2026-11-30T22:59:59Z'); // 30.11. 23:59:59 in Berlin

        self::assertFalse($policy->isUnlocked(self::calendar(), 1));
        self::assertSame(0, $policy->unlockedCount(self::calendar()));
        self::assertEquals(new \DateTimeImmutable('2026-12-01T00:00:00+01:00'), $policy->nextUnlock(self::calendar()));
    }

    public function testFirstDoorOpensAtMidnightBerlinTime(): void
    {
        // 23:00 UTC am 30.11. ist schon 00:00 am 1.12. in Berlin
        $policy = self::policy('2026-11-30T23:00:00Z');

        self::assertTrue($policy->isUnlocked(self::calendar(), 1));
        self::assertFalse($policy->isUnlocked(self::calendar(), 2));
        self::assertSame(1, $policy->unlockedCount(self::calendar()));
    }

    public function testDoorsOpenDayByDay(): void
    {
        $policy = self::policy('2026-12-13T12:00:00+01:00');

        self::assertSame(13, $policy->unlockedCount(self::calendar()));
        self::assertTrue($policy->isUnlocked(self::calendar(), 13));
        self::assertFalse($policy->isUnlocked(self::calendar(), 14));
        self::assertEquals(new \DateTimeImmutable('2026-12-14T00:00:00+01:00'), $policy->nextUnlock(self::calendar()));
    }

    /** Der alte Kalender ließ nach Dezember nichts mehr öffnen ($currentMonth > 12 war nie wahr). */
    #[DataProvider('afterChristmas')]
    public function testAllDoorsStayOpenAfterTheTwentyFourth(string $now): void
    {
        $policy = self::policy($now);

        self::assertSame(24, $policy->unlockedCount(self::calendar()));
        self::assertTrue($policy->isUnlocked(self::calendar(), 24));
        self::assertNull($policy->nextUnlock(self::calendar()));
    }

    public static function afterChristmas(): iterable
    {
        yield 'Heiligabend 00:00' => ['2026-12-24T00:00:00+01:00'];
        yield 'Silvester' => ['2026-12-31T20:00:00+01:00'];
        yield 'Januar' => ['2027-01-15T09:00:00+01:00'];
        yield 'nächster Sommer' => ['2027-07-01T12:00:00+02:00'];
        yield 'nächster Dezember vor dem 1.' => ['2027-11-30T12:00:00+01:00'];
    }

    public function testStartDateIsConfigurable(): void
    {
        $calendar = self::calendar('2026-11-29'); // erster Advent
        $policy = self::policy('2026-12-01T08:00:00+01:00');

        self::assertSame(3, $policy->unlockedCount($calendar));
        self::assertEquals(new \DateTimeImmutable('2026-12-22T00:00:00+01:00'), $policy->unlocksAt($calendar, 24));
    }

    public function testOtherTimezone(): void
    {
        $calendar = self::calendar('2026-12-01', 'America/New_York');

        // In Berlin ist schon der 1.12., in New York noch der 30.11.
        self::assertFalse(self::policy('2026-12-01T03:00:00Z')->isUnlocked($calendar, 1));
        self::assertTrue(self::policy('2026-12-01T05:00:00Z')->isUnlocked($calendar, 1));
    }

    public function testDaylightSavingTimeDoesNotShiftDoors(): void
    {
        // Start kurz vor der Umstellung (28.03.2027): Tür 3 öffnet trotzdem um 00:00 Ortszeit am 29.03.
        $calendar = self::calendar('2027-03-27');
        $policy = self::policy('2027-03-29T00:00:00+02:00');

        self::assertEquals(new \DateTimeImmutable('2027-03-29T00:00:00+02:00'), $policy->unlocksAt($calendar, 3));
        self::assertTrue($policy->isUnlocked($calendar, 3));
        self::assertFalse(self::policy('2027-03-28T23:59:59+02:00')->isUnlocked($calendar, 3));
    }

    #[DataProvider('invalidDoors')]
    public function testDoorsOutsideOneToTwentyFourAreNeverOpen(int $number): void
    {
        self::assertFalse(self::policy('2030-01-01T00:00:00Z')->isUnlocked(self::calendar(), $number));
    }

    public static function invalidDoors(): iterable
    {
        yield [0];
        yield [-1];
        yield [25];
    }

    public function testUnlocksAtRejectsInvalidDoor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        self::policy('2026-12-01T00:00:00Z')->unlocksAt(self::calendar(), 25);
    }
}
