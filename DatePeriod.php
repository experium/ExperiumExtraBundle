<?php

namespace Experium\ExtraBundle;

/**
 * В виду ограничений PHP сделать конструкторы с различными параметрами довольно сложно... А хотелось бы
 * запомнить интервал.
 *
 * @author Alexey Shockov <shokov@experium.ru>
 */
class DatePeriod extends \DatePeriod
{
    /**
     * @return \DateTime
     */
    public function getStart()
    {
        foreach ($this as $start) {
            return $start;
        }
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        $end = null;
        foreach ($this as $end) {

        }

        return $end;
    }

    /**
     * ISO period string.
     */
    public function __toString()
    {
        // FIXME Implement.
        throw new \Exception('Not implemented.');
    }

    /**
     * По умолчанию — для текущего месяца.
     *
     * @param \DateTime|null $month
     */
    public static function forMonth(\DateTime $month = null)
    {
        if (!$month) {
            $month = new \DateTime();
        }

        $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', $month->format('Y-m-01 00:00:00'));
        // Дата окончания включается исключительно, нужно сделать дополнительный день, чтобы получить её в периоде.
        $endDate   = \DateTime::createFromFormat('Y-m-d H:i:s', $month->format('Y-m-t 00:00:00'))->modify('+1 day');

        return new self($startDate, new \DateInterval('P1D'), $endDate);
    }

    /**
     * По умолчанию — для текущей недели.
     *
     * @param \DateTime|null $monday
     */
    public static function forWeek(\DateTime $monday = null)
    {
        if (!$monday) {
            $currentDay = new \DateTime();
            $monday     = $currentDay->modify('-'.($currentDay->format('N') - 1).' day');
        }

        $startDate = \DateTime::createFromFormat('Y-m-d H:i:s', $monday->format('Y-m-d 00:00:00'));
        $endDate   = clone $startDate;
        // Дата окончания включается исключительно, нужно сделать дополнительный день, чтобы получить её в периоде.
        $endDate   = $endDate->modify('+6 days')->modify('+1 day');

        return new self($startDate, new \DateInterval('P1D'), $endDate);
    }
}
