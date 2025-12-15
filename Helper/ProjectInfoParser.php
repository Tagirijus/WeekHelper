<?php

namespace Kanboard\Plugin\WeekHelper\Helper;


class ProjectInfoParser
{

    /**
     * With this method I can get info for the given project
     * as variables. These info will be parsed from the project
     * descriptions texts. So the input should be a proper
     * project array, having at least the "description" key.
     *
     * @param  array $project
     * @return array
     */
    public function getProjectInfoByProject($project)
    {
        $data = [
            // the priority for the project. basically this
            // priority will always be added to the projects
            // tasks priority.
            'priority' => 0,

            // the project type can be any string with which
            // the user can assign this project a project type.
            // with this in the settings can be set "whitelist" for
            // times in which this project type can be planned.
            // it can include multiple strings; e.g. comma separated.
            // the logic later will just search the substring in this
            // big string
            'project_type' => '',

            // the max hours the projects tasks can be
            // planned for one day.
            'max_hours' => 8,
        ];

        $this->parseData($data, $project['description']);

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
        $lines = explode("\r\n", $description ?? '');
        foreach ($lines as $line) {

            if (str_starts_with($line, 'priority=')) {
                $data['priority'] = (int) str_replace('priority=', '', $line);

            } elseif (str_starts_with($line, 'project_type=')) {
                $data['project_type'] = (int) str_replace('project_type=', '', $line);

            } elseif (str_starts_with($line, 'max_hours=')) {
                $data['max_hours'] = (int) str_replace('max_hours=', '', $line);

            }
        }
    }
}
