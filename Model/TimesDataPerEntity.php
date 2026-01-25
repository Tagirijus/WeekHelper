<?php

namespace Kanboard\Plugin\WeekHelper\Model;

use Kanboard\Plugin\WeekHelper\Model\TimesData;

/**
 * A TimesData for storing the times per entity.
 */
class TimesDataPerEntity
{
    /**
     * The internal enetities array, which holds
     * a TimeData per entity.
     *
     * @var TimesData[]
     */
    protected $entities = [];

    /**
     * Add the given floats to the internal core times data attribute.
     *
     * @param float $estimated
     * @param float $spent
     * @param float $remaining
     * @param float $overtime
     * @param mixed $entity
     */
    public function addTimes(
        $estimated,
        $spent,
        $remaining,
        $overtime,
        $entity
    )
    {
        if (!array_key_exists($entity, $this->entities)) {
            $this->entities[$entity] = new TimesData();
        }
        $this->entities[$entity]->addTimes(
            $estimated,
            $spent,
            $remaining,
            $overtime
        );
    }

    /**
     * Returns the entities, which are basically just the
     * internal arrays keys.
     *
     * At some point I will store project IDs as keys here
     * and also have some unique sorting logic internally.
     * So then I might want to get the project IDs in the
     * correct order.
     *
     * @return array
     */
    public function getEntities()
    {
        return array_keys($this->entities);
    }

    /**
     * Get the the estimated time for the entity. If the
     * entity does not exist, return the times for all of
     * the entities.
     *
     * @param mixed $entity
     * @param boolean $readable
     * @return float
     */
    public function getEstimated($entity = '', $readable = false)
    {
        if (array_key_exists($entity, $this->entities)) {
            return $this->entities[$entity]->getEstimated($readable);
        } else {
            if ($readable) {
                return TimesData::floatToHHMM(0.0);
            } else {
                return 0.0;
            }
        }
    }

    /**
     * Get the the estimated time for all entities.
     *
     * @param boolean $readable
     * @return float
     */
    public function getEstimatedAll($readable = false)
    {
        $out = 0.0;
        foreach ($this->entities as $entity) {
            $out += $entity->getEstimated();
        }
        if ($readable) {
            return TimesData::floatToHHMM($out);
        } else {
            return $out;
        }
    }

    /**
     * Get the the overtime time for the entity. If the
     * entity does not exist, return the times for all of
     * the entities.
     *
     * @param mixed $entity
     * @param boolean $readable
     * @return float
     */
    public function getOvertime($entity = '', $readable = false)
    {
        if (array_key_exists($entity, $this->entities)) {
            return $this->entities[$entity]->getOvertime($readable);
        } else {
            if ($readable) {
                return TimesData::floatToHHMM(0.0);
            } else {
                return 0.0;
            }
        }
    }

    /**
     * Get the the overtime time for all entities.
     *
     * @param boolean $readable
     * @return float
     */
    public function getOvertimeAll($readable = false)
    {
        $out = 0.0;
        foreach ($this->entities as $entity) {
            $out += $entity->getOvertime();
        }
        if ($readable) {
            return TimesData::floatToHHMM($out);
        } else {
            return $out;
        }
    }

    /**
     * Get the the done percentage for the entity. If the
     * entity does not exist, return the average percentage
     * of all the entities.
     *
     * @param mixed $entity
     * @param boolean $readable
     * @param string $suffix
     * @return float
     */
    public function getPercent($entity = '', $readable = false, $suffix = '%')
    {
        if (array_key_exists($entity, $this->entities)) {
            if ($readable) {
                return $this->entities[$entity]->getPercentAsString($suffix);
            } else {
                return $this->entities[$entity]->getPercent();
            }
        } else {
            $out = 0.0;
            foreach ($this->entities as $entity) {
                $out += $entity->getPercent();
                $out = $out / 2;
            }
            if ($readable) {
                return (string) round($out * 100) . $suffix;
            } else {
                return $out;
            }
        }
    }

    /**
     * Get the the remaining time for the entity. If the
     * entity does not exist, return the times for all of
     * the entities.
     *
     * @param mixed $entity
     * @param boolean $readable
     * @return float
     */
    public function getRemaining($entity = '', $readable = false)
    {
        if (array_key_exists($entity, $this->entities)) {
            return $this->entities[$entity]->getRemaining($readable);
        } else {
            if ($readable) {
                return TimesData::floatToHHMM(0.0);
            } else {
                return 0.0;
            }
        }
    }

    /**
     * Get the the remaining time for all entities.
     *
     * @param boolean $readable
     * @return float
     */
    public function getRemainingAll($readable = false)
    {
        $out = 0.0;
        foreach ($this->entities as $entity) {
            $out += $entity->getRemaining();
        }
        if ($readable) {
            return TimesData::floatToHHMM($out);
        } else {
            return $out;
        }
    }

    /**
     * Get the the spent time for the entity. If the
     * entity does not exist, return the times for all of
     * the entities.
     *
     * @param mixed $entity
     * @param boolean $readable
     * @return float
     */
    public function getSpent($entity = '', $readable = false)
    {
        if (array_key_exists($entity, $this->entities)) {
            return $this->entities[$entity]->getSpent($readable);
        } else {
            if ($readable) {
                return TimesData::floatToHHMM(0.0);
            } else {
                return 0.0;
            }
        }
    }

    /**
     * Get the the spent time for all entities.
     *
     * @param boolean $readable
     * @return float
     */
    public function getSpentAll($readable = false)
    {
        $out = 0.0;
        foreach ($this->entities as $entity) {
            $out += $entity->getSpent();
        }
        if ($readable) {
            return TimesData::floatToHHMM($out);
        } else {
            return $out;
        }
    }

    /**
     * Return, if there are times at all for the
     * specified entity. If non specified: for at
     * least one.
     *
     * @param string $entity
     * @return boolean
     */
    public function hasTimes($entity = '')
    {
        if (array_key_exists($entity, $this->entities)) {
            return $this->entities[$entity]->hasTimes();
        } else {
            return false;
        }
    }

    /**
     * Return, if any entity has times.
     *
     * @return boolean
     */
    public function hasTimesAny()
    {
        foreach ($this->entities as $value) {
            if ($value->hasTimes()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Reset the times for the given entity. If it does
     * not exist, reset it for all of the entities.
     *
     * @param  string $entity
     */
    public function resetTimes($entity = '')
    {
        if (array_key_exists($entity, $this->entities)) {
            $this->entities[$entity]->resetTimes();
        } else {
            foreach ($this->entities as &$value) {
                $value->resetTimes();
            }
            unset($value);
        }
    }

    /**
     * Sort the internal array according to the wanted method.
     *
     * Methods are:
     *     - entity
     *     - estimated
     *     - spent
     *     - remaining
     *     - overtime
     *
     * @param  string $method
     * @param  string $direction
     */
    public function sort($method = 'entity', $direction = 'asc')
    {
        if ($method == 'entity') {
            if ($direction == 'desc') {
                krsort($this->entities);
            } else {
                ksort($this->entities);
            }
        } elseif ($method == 'estimated') {
            self::sortByGetter($this->entities, 'getEstimated', $direction);
        } elseif ($method == 'spent') {
            self::sortByGetter($this->entities, 'getSpent', $direction);
        } elseif ($method == 'remaining') {
            self::sortByGetter($this->entities, 'getRemaining', $direction);
        } elseif ($method == 'overtime') {
            self::sortByGetter($this->entities, 'getOvertime', $direction);
        }

    }

    /**
     * Sort the given array with a getter method on its values.
     *
     * @param  array &$arr
     * @param  string $getter
     * @param  string $direction
     */
    protected static function sortByGetter(&$arr, $getter, $direction)
    {
        uasort($arr, function ($a, $b) use ($getter, $direction) {
            $va = $a->{$getter}();
            $vb = $b->{$getter}();
            return $direction === 'asc' ? ($va <=> $vb) : ($vb <=> $va);
        });
    }
}
