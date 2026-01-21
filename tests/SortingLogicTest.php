<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/SortingLogic.php';

use PHPUnit\Framework\TestCase;
use Kanboard\Plugin\WeekHelper\Model\SortingLogic;


final class SortingLogicTest extends TestCase
{
    /**
     * This will just be very basic tasks. Most keys are missing,
     * since they aren't needed for the tests here.
     */
    private static array $tasks;

    /**
     * Some different sorting configs, to test different
     * kinds of sorting cases.
     */
    private static string $config_a;
    private static array $config_a_parsed;
    private static string $config_b;
    private static array $config_b_parsed;

    /**
     * The sorted tasks according to the given configs from above.
     */
    private static array $sorted_tasks_a;
    private static array $sorted_tasks_b;


    public static function setUpBeforeClass(): void
    {
        self::$tasks = [
            ['id' => 1, 'title' => 'one', 'priority' => '2'],
            ['id' => 2, 'title' => 'a two', 'priority' => '2'],
            ['id' => 3, 'title' => 'three', 'priority' => '3'],
            ['id' => 4, 'title' => 'four', 'priority' => '1'],
            ['id' => 5, 'title' => 'zzz', 'priority' => '1'],
            ['id' => 6, 'title' => 'zzz', 'priority' => '0'],
        ];
        self::$config_a = "priority desc\ntitle asc";
        self::$config_a_parsed = ['priority' => 'desc', 'title' => 'asc'];
        self::$config_b = "title desc\npriority asc";
        self::$config_b_parsed = ['title' => 'desc', 'priority' => 'asc'];
        self::$sorted_tasks_a = [
            ['id' => 3, 'title' => 'three', 'priority' => '3'],
            ['id' => 2, 'title' => 'a two', 'priority' => '2'],
            ['id' => 1, 'title' => 'one', 'priority' => '2'],
            ['id' => 4, 'title' => 'four', 'priority' => '1'],
            ['id' => 5, 'title' => 'zzz', 'priority' => '1'],
            ['id' => 6, 'title' => 'zzz', 'priority' => '0'],
        ];
        self::$sorted_tasks_b = [
            ['id' => 6, 'title' => 'zzz', 'priority' => '0'],
            ['id' => 5, 'title' => 'zzz', 'priority' => '1'],
            ['id' => 3, 'title' => 'three', 'priority' => '3'],
            ['id' => 1, 'title' => 'one', 'priority' => '2'],
            ['id' => 4, 'title' => 'four', 'priority' => '1'],
            ['id' => 2, 'title' => 'a two', 'priority' => '2'],
        ];
    }

    public function testSortingLogicConfigParsing()
    {
        $parsed_config_a = SortingLogic::parseSortLogic(self::$config_a);
        $parsed_config_b = SortingLogic::parseSortLogic(self::$config_b);

        $this->assertSame(
            self::$config_a_parsed,
            $parsed_config_a,
            'Could not parse config A in SortingLogic.'
        );
        $this->assertSame(
            self::$config_b_parsed,
            $parsed_config_b,
            'Could not parse config B in SortingLogic.'
        );
    }

    public function testSortingLogicSorting(): void
    {
        $sorted_a = SortingLogic::sortTasks(self::$tasks, self::$config_a);
        $sorted_b = SortingLogic::sortTasks(self::$tasks, self::$config_b);

        $this->assertSame(
            self::$sorted_tasks_a,
            $sorted_a,
            'SortingLogic could not sort case A correctly.'
        );
        $this->assertSame(
            self::$sorted_tasks_b,
            $sorted_b,
            'SortingLogic could not sort case B correctly.'
        );
    }

    public function testSortingByLevel()
    {
        $tasks = [
            ['id' => 1, 'title' => 'one', 'levels' => ['level_2']],
            ['id' => 2, 'title' => 'a two', 'levels' => ['level_1', 'level_2']],
            ['id' => 3, 'title' => 'three', 'levels' => []],
        ];
        $tasks_sorted = [
            ['id' => 2, 'title' => 'a two', 'levels' => ['level_1', 'level_2']],
            ['id' => 1, 'title' => 'one', 'levels' => ['level_2']],
            ['id' => 3, 'title' => 'three', 'levels' => []],
        ];
        $config = "levels asc";
        $sorted = SortingLogic::sortTasks($tasks, $config);
        $this->assertSame(
            $tasks_sorted,
            $sorted,
            'SortingLogic failed on sorting by tasks level.'
        );
    }
}
