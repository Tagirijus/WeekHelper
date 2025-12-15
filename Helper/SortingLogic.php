<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


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
function comparator(array $sort_spec) {
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


class SortingLogic
{
    /**
     * Sort the given tasks with the wanted logic and finally
     * return the sorted array again.
     *
     * @param  array $tasks
     * @return array
     */
    public function sortTasks(&$tasks)
    {
        // WEITER HIER
        // Sortier-Logik etablieren;
        // ggf. auch neue Config hinzufügen, sodass ich die Logik sogar dynamisch verändern kann!?
        //
        // Es könnte noch weitere custom "keys" geben.
        // Z.B. "position_absolute" berücksichtigt nicht nur "position" sondern auch
        // die Position in Kombination mit "column_position". Denn Position 1 in Column 2
        // ist höher gewichtet als Position 1 in Column 1.
        // Dafür oben im comparator so custom checks "if $key == 'position_abs'" oder so;
        // sodass ich dann intern im comparator() $va['column_pos'] * 100 + $va['position']
        // oder so ähnlich mache (muss mir noch etwas ausdenken, da niedrigere Position in
        // einer Spalte ja höher gewichtet ist, aber höhere Spalte hingegen höher).
        usort($tasks, comparator(['priority' => 'desc']));
        return $tasks;
    }
}
