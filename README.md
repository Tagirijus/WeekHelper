# WeekHelper

#### _Plugin for [Kanboard](https://github.com/fguillot/kanboard "Kanboard - Kanban Project Management Software")_

With this plugin you get some helpers for planning the week. E.g. quickly name a task with e.g. the week number. Also it shows the actual date in the header. And you get the hours calculated and shown in a more convenient style on various places of th app.

In a task title you can enter "w" and trigger the week replacement feature, which will give you this week, the next week and the overnext week and replace the choice with the pattern set up in the config. By default it is "Y{YEAR_SHORT}-W{WEEK}". In the year 2023 and the week 39 of the year it would be translated to "Y23-W39", for example. These are the possible replacement values:

- YEAR: the four digit actual year
- YEAR_SHORT: the two digit actual year
- WEEK: the actual week number

## Additional

Also there now is a new automatic action, which will use the pattern you can set up in the settings, convert it to a regex internally and use it to generate a new such string but for the next week. Then you can set up the automatic action to be executed if e.g. you move a task into the backlog or so. Workflow-wise this could mean: put this task onto next week. And this automatic action helps you quickly automatically rename the tasks title to "update" the week number.


Screenshots
-------------

**Board view**

![HoursView board view](../master/Screenshots/HoursView_board.png)

**Dashboard view**

![HoursView dashboard view](../master/Screenshots/HoursView_dashboard.png)


Compatibility
-------------

- Requires [Kanboard](https://github.com/fguillot/kanboard "Kanboard - Kanban Project Management Software") ≥`1.2.27`

#### Other Plugins & Action Plugins
- _No known issues_
#### Core Files & Templates
- `09` Template Overrides
- _No database changes_


Changelog
---------

Read the full [**Changelog**](../master/changelog.md "See changes")
 

Installation
------------

1. Go into Kanboards `plugins/` folder
2. `git clone https://github.com/Tagirijus/WeekHelper`


Translations
------------

- _Contributors welcome_
- _Starter template available_

Authors & Contributors
----------------------

- [@Tagirijus](https://github.com/Tagirijus) - Author
- _Contributors welcome_


License
-------
- This project is distributed under the [MIT License](../master/LICENSE "Read The MIT license")
