<?php

namespace Kanboard\Plugin\WeekHelper\Helper;

use Kanboard\Core\Base;


class ProjectInfoParser extends Base
{

    /**
     * With this method I can get info for projects as variables.
     * These info will be parsed from the project descriptions texts.
     *
     * @param  integer $projectId
     * @return array
     */
    public function getProjectInfoById($projectId)
    {
        $data = [
            // the priority for the project. basically this
            // priority will always be added to the projects
            // tasks priority.
            'priority' => 0,

            // the max hours the projects tasks can be
            // planned for one day.
            'max_hours' => 8,
        ];

        $project = $this->projectModel->getById($projectId);
        if ($project) {
            $this->parseData($data, $project['description']);
        }

        return $data;
    }

    /**
     * Parse the wanted data from the given description string
     * into the internal data class attribute.
     *
     * @param array   &$data
     * @param  string $description
     */
    public function parseData(&$data, $description)
    {
        $lines = explode("\r\n", $description);
        foreach ($lines as $line) {

            if (str_starts_with($line, 'priority=')) {
                $data['priority'] = (int) str_replace('priority=', '', $line);

            } elseif (str_starts_with($line, 'max_hours=')) {
                $data['max_hours'] = (int) str_replace('max_hours=', '', $line);

            }
        }
    }
}
