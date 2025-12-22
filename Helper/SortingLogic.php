<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


class SortingLogic
{
    /**
     * This function can be used in a usort() method, while getting an array, describing
     * on which key of the array (to sort) it shoul be sorted in which direction.
     * Ket's say we have this array:
     *
     *     [
     *         ['name' => 'abc', 'age' => 30],
     *         ['name' => 'abc', 'age' => 31],
     *         ['name' => 'abc', 'age' => 29],
     *         ['name' => 'zzz', 'age' => 50],
     *         ['name' => 'zzz', 'age' => 49],
     *         ['name' => 'def', 'age' => 30]
     *     ]
     *
     * We now could use this array as sort_spec:
     *
     *     [
     *         ['name' => 'asc'],
     *         ['age' => 'desc']
     *     ]
     *
     * And should get this result:
     *
     *     [
     *         ['name' => 'abc', 'age' => 31],
     *         ['name' => 'abc', 'age' => 30],
     *         ['name' => 'abc', 'age' => 29],
     *         ['name' => 'def', 'age' => 30]
     *         ['name' => 'zzz', 'age' => 50],
     *         ['name' => 'zzz', 'age' => 49],
     *     ]
     *
     * Example:
     *   usort($data, comparator($spec));
     *
     * @param  array  $sort_pec
     * @return callable
     */
    private static function comparator(array $sort_spec) {
        return function($a, $b) use ($sort_spec) {
            foreach ($sort_spec as $key => $dir) {
                $va = $a[$key] ?? null;
                $vb = $b[$key] ?? null;
                // handle numeric vs string naturally; use strcmp for strings if desired
                if ($va == $vb) continue;
                $cmp = ($va < $vb) ? -1 : 1;
                if (strtolower($dir) === 'desc') $cmp *= -1;
                return $cmp;
            }
            return 0;
        };
    }

    /**
     * Parse the given sort logic string from the config and convert
     * it into a sort_spec array.
     *
     * @param  string $sort_logic_config
     * @return array
     */
    private static function parseSortLogic($sort_logic_config)
    {
        $lines = explode("\r\n", $sort_logic_config ?? '');
        $sort_spec = [];
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', $line);
            if (count($parts) > 1) {
                $column = $parts[0];
                $direction = $parts[1];
                $sort_spec[$column] = $direction;
            }
        }
        return $sort_spec;
    }

    /**
     * Sort the given tasks with the wanted logic and finally
     * return the sorted array again.
     *
     * @param  array $tasks
     * @param  string $sort_logic_config
     * @return array
     */
    public static function sortTasks(&$tasks, $sort_logic_config)
    {
        $sort_spec = self::parseSortLogic($sort_logic_config);
        usort($tasks, self::comparator($sort_spec));
        return $tasks;
    }
}
