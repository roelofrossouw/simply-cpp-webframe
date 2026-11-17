<?php

  namespace Chart;

  /**
   * Round times for a time axis: every minute, 5, 10, 15 or 30 minutes, hour, 2, 3, 6 or 12 hours, day, 2 days, week,
   * month, quarter or year, whichever gives about the number asked for, on the site's time zone (app.timezone), each
   * with its text: 14:30, Tue 17 Nov (at a day's start), 17 Nov, Nov 2026, 2026
   */
  final class TimeTicks
  {
    private const int MINUTE = 60;
    private const int HOUR = 3600;
    private const int DAY = 86400;
    private const int MONTH = 30 * self::DAY; // for choosing; months are stepped by the calendar
    private const array STEPS = [self::MINUTE, 5 * self::MINUTE, 10 * self::MINUTE, 15 * self::MINUTE, 30 * self::MINUTE, self::HOUR,
      2 * self::HOUR, 3 * self::HOUR, 6 * self::HOUR, 12 * self::HOUR, self::DAY, 2 * self::DAY, 7 * self::DAY, self::MONTH,
      3 * self::MONTH, 12 * self::MONTH];

    /**
     * About $Count ticks from $First to $Last (Unix seconds), each [time, text]
     *
     * @return array<int, array{int, string}>
     */
    public static function Between(int $First, int $Last, int $Count = 6): array
    {
      if ($Last <= $First)
      {
        return [[$First, date('H:i', $First)]];
      }
      $Span = $Last - $First;
      $Step = self::STEPS[count(self::STEPS) - 1];
      foreach (self::STEPS as $Candidate)
      {
        if ($Span / $Candidate <= max(1, $Count))
        {
          $Step = $Candidate;
          break;
        }
      }
      $Ticks = [];
      $Time = self::Start($First, $Step);
      $Before = null;
      while ($Time <= $Last && count($Ticks) < 500)
      {
        if ($Time >= $First)
        {
          $Ticks[] = [$Time, self::Text($Time, $Step, $Before)];
          $Before = $Time;
        }
        $Time = self::Next($Time, $Step);
      }
      return $Ticks;
    }

    /**
     * The usual step between times (the median), for formatting labels that aren't ticks
     *
     * @param int[] $Times
     */
    public static function Step(array $Times): int
    {
      sort($Times);
      $Steps = [];
      for ($Index = 1; $Index < count($Times); $Index++)
      {
        $Steps[] = $Times[$Index] - $Times[$Index - 1];
      }
      if (empty($Steps))
      {
        return self::HOUR;
      }
      sort($Steps);
      return max(1, $Steps[intdiv(count($Steps), 2)]);
    }

    /**
     * date()'s format for times $Step apart: H:i under a day, j M under a month, M Y under a year, else Y
     */
    public static function FormatFor(int $Step): string
    {
      return $Step < self::DAY ? 'H:i' : ($Step < 28 * self::DAY ? 'j M' : ($Step < 365 * self::DAY ? 'M Y' : 'Y'));
    }

    // The first round time at or before $Time, for $Step (local time: days start at local midnight)
    private static function Start(int $Time, int $Step): int
    {
      $Date = (new \DateTimeImmutable('@' . $Time))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
      if ($Step >= 12 * self::MONTH)
      {
        return $Date->setDate((int)$Date->format('Y'), 1, 1)->setTime(0, 0)->getTimestamp();
      }
      if ($Step >= self::MONTH)
      {
        $Months = intdiv($Step, self::MONTH);
        $Month = (int)$Date->format('n') - 1;
        return $Date->setDate((int)$Date->format('Y'), $Month - $Month % $Months + 1, 1)->setTime(0, 0)->getTimestamp();
      }
      if ($Step >= self::DAY)
      {
        $Midnight = $Date->setTime(0, 0);
        return $Step == 7 * self::DAY ? $Midnight->modify('monday this week')->getTimestamp() : $Midnight->getTimestamp();
      }
      // Under a day: round in local time, so 6-hourly ticks fall on 00:00, 06:00, ...
      $Offset = $Date->getOffset();
      return (int)(floor(($Time + $Offset) / $Step) * $Step) - $Offset;
    }

    private static function Next(int $Time, int $Step): int
    {
      if ($Step >= self::MONTH)
      {
        $Date = (new \DateTimeImmutable('@' . $Time))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $Date->modify('+' . intdiv($Step, self::MONTH) . ' months')->getTimestamp();
      }
      if ($Step >= self::DAY)
      {
        // By the calendar, so a daylight saving change doesn't shift midnight
        $Date = (new \DateTimeImmutable('@' . $Time))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        return $Date->modify('+' . intdiv($Step, self::DAY) . ' days')->getTimestamp();
      }
      return $Time + $Step;
    }

    // A tick's text: the time of day, with the date where a new day starts; dates for days; months; years
    private static function Text(int $Time, int $Step, ?int $Before): string
    {
      if ($Step >= 12 * self::MONTH)
      {
        return date('Y', $Time);
      }
      if ($Step >= self::MONTH)
      {
        return date($Before === null || date('Y', $Before) !== date('Y', $Time) ? 'M Y' : 'M', $Time);
      }
      if ($Step >= self::DAY)
      {
        return date('j M', $Time);
      }
      $NewDay = $Before !== null && date('Ymd', $Before) !== date('Ymd', $Time);
      return $NewDay && date('H:i', $Time) === '00:00' ? date('D j M', $Time) : date('H:i', $Time);
    }
  }
