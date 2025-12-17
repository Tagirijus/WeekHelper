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
            'project_priority' => 0,

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
            'project_max_hours_day' => 8.0,

            // the max hours the projects tasks can be
            // planned for one consecutive work block.
            // means that after planning tasks of the same
            // project for this long, will force to break
            // the actual sorting by skipping tasks from the
            // same project and taking the next best task
            // of another project to be planned.
            'project_max_hours_block' => 8.0,

            // the hourly wage for the project
            'project_wage' => 0.0,

            // an individual project alias
            'project_alias' => '',
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
        $has_max_hours_block = false;
        foreach ($lines as $line) {

            if (str_starts_with($line, 'project_priority=')) {
                $data['project_priority'] = (int) str_replace('project_priority=', '', $line);

            } elseif (str_starts_with($line, 'project_type=')) {
                $data['project_type'] = str_replace('project_type=', '', $line);

            } elseif (str_starts_with($line, 'project_max_hours_day=')) {
                $data['project_max_hours_day'] = (float) str_replace('project_max_hours_day=', '', $line);

            } elseif (str_starts_with($line, 'project_max_hours_block=')) {
                $data['project_max_hours_block'] = (float) str_replace('project_max_hours_block=', '', $line);
                $has_max_hours_block = true;

            } elseif (str_starts_with($line, 'project_wage=')) {
                $data['project_wage'] = (float) str_replace('project_wage=', '', $line);

            } elseif (str_starts_with($line, 'project_alias=')) {
                $data['project_alias'] = str_replace('project_alias=', '', $line);

            }
        }

        // if no "project_max_hours_block" was set manually, this value should
        // always copy the "project_max_hours_day" value.
        if (!$has_max_hours_block) {
            $data['project_max_hours_block'] = $data['project_max_hours_day'];
        }
    }
}
