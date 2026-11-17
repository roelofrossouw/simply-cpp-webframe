<?php

  namespace Chart;

  /**
   * A stretch of time to draw, and the step between its points: what a Prometheus range query asks for
   *
   *   Chart\TimeRange::Last('6h')                       // the last 6 hours, about 240 points
   *   Chart\TimeRange::Between($From, $To, Points: 100)
   */
  final class TimeRange
  {
    // Steps a range rounds to: lines of different queries then share their times
    private const array STEPS = [15, 30, 60, 120, 300, 600, 900, 1800, 3600, 7200, 10800, 21600, 43200, 86400];

    private function __construct(public readonly int $Start, public readonly int $End, public readonly int $Step)
    {
    }

    /**
     * The last $Duration ('90s', '15m', '6h', '2d', '1w'), to now, in steps that give about $Points points
     */
    public static function Last(string $Duration, int $Points = 240, ?int $Now = null): self
    {
      $Now ??= time();
      return self::Between($Now - self::Seconds($Duration), $Now, $Points);
    }

    /**
     * From $Start to $End (Unix seconds), about $Points points; the ends rounded to the step
     */
    public static function Between(int $Start, int $End, int $Points = 240): self
    {
      if ($End <= $Start)
      {
        throw new \InvalidArgumentException('a time range must end after it starts');
      }
      $Wanted = ($End - $Start) / max(2, $Points);
      $Step = self::STEPS[count(self::STEPS) - 1];
      foreach (self::STEPS as $Candidate)
      {
        if ($Candidate >= $Wanted)
        {
          $Step = $Candidate;
          break;
        }
      }
      return new self(intdiv($Start, $Step) * $Step, intdiv($End, $Step) * $Step, $Step);
    }

    /**
     * A duration in seconds: a number with s, m, h, d or w ('15m', '6h'), as Prometheus writes them
     */
    public static function Seconds(string $Duration): int
    {
      if (!preg_match('/^\s*(\d+)\s*([smhdw])\s*$/i', $Duration, $Match))
      {
        throw new \InvalidArgumentException("not a duration: '$Duration' (e.g. 15m, 6h, 2d)");
      }
      return (int)$Match[1] * ['s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400, 'w' => 604800][strtolower($Match[2])];
    }
  }
