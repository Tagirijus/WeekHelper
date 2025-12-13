# WeekHelper

#### _Plugin for [Kanboard](https://github.com/fguillot/kanboard "Kanboard - Kanban Project Management Software")_

With this plugin you get some helpers for planning the week. E.g. quickly name a task with e.g. the week number. Also it shows the actual date in the header (and sticky on the bottom right). And you get the hours calculated and shown in a more convenient style on various places of the app.

In a task title you can enter "w" and trigger the week replacement feature, which will give you this week, the next week and the overnext week and replace the choice with the pattern set up in the config. By default it is "Y{YEAR_SHORT}-W{WEEK}". In the year 2023 and the week 39 of the year it would be translated to "Y23-W39", for example. These are the possible replacement values:

- YEAR: the four digit actual year
- YEAR_SHORT: the two digit actual year
- WEEK: the actual week number

## Additional

Also there now is a new automatic action, which will use the pattern you can set up in the settings, convert it to a regex internally and use it to generate a new such string but for the next week. Then you can set up the automatic action to be executed if e.g. you move a task into the backlog or so. Workflow-wise this could mean: put this task onto next week. And this automatic action helps you quickly automatically rename the tasks title to "update" the week number.

## Non-Time Mode

You can enable the non-time mode by entering a minutes number above 0 in the settings. This will basically disable the time calculation with the spent time and estimated time fields and use the complexity instead. You will set how many minutes one (1) complexity sill stand for. With this subtasks of a task will be spread in this time.

Example:
You set "30" for 1 complexity. So a task with complexity of 4 will have an estimated time of "2 hours". Now this task might have 2 subtasks. If you mark only the first as "done", it will interprete it as "50% done", thus saying you spent 1 hour. If a task is "in progress" it will use its half. So if only the first subtask would be in progress, it would say "30 minutes spent".

**Hidden features (yes, I am a nightmare when it comes to UX concepts ...):**

1. Override
You can give the last subtask a plain numeric. If this is a positive number (can also be a float), it will overwrite the remaining time with it as hours. Let's say the last subtask will be "0.5", but no subtask is marked at all, it will still say "30 minutes left". Marked "in progress", this subtask would make it say "15 minutes left". If "done" it would say "0 minutes left".

If the number is negative, it would overwrite the "spent time". So you could also enter "0.5" there for the example task above (with 2 horus estimated) and it would say "1.5 hours left", because "30 minutes spent". Marking it "in progress" or "done" would not change a thing here, though.

2. Percentage
A subtasks title may also start with a number and a percentage sign without whitespace. E.g. a subtask title could be "50% office work". This single subtask would then stand for 50% of the full estimated time.

Example again:
A task has the complexity of 6, with 30 min per point, thus 3 hours. It has 6 subtasks, but the first one has "50% anything" as title. If this one would be marked as "in progress", the remaining time would say "2:15 h", and marked as "done" it would say "1:30 h". More than one subtasks can have a percentage; it should sum up correctly though, because otherwise calculations could be wrong.

## Automatic Planner

This one is a new feature. I am writing this, while still thinking about the core logic of this feature. The idea is to have a sticky table on Kanboard and also a new route, which will be able to output plaintext, HTML and JSON. The content of all of these would be an automatic generated week plan.

Background: with Kanboard you can have cards in columns per project. Of course, you could use the columns for week days. I tend to use it in a more kanban style: having columns like backlog, in progress, blocked, done, etc. So I planned my weekdays in an additional system of mine. I did assign certain amounts of hours for different kinds of projects on certain days. So maybe I worked for a project every morning, but other projects from noon to evening or so. Then I would align the order of the projects in a todo list or similar.

With this feature I want to have such thing automatically. I do plan my week loosely by only putting cards with certain estimated times in the first column (which stand for my planned week). When the week actually starts I drag the cards into the second column and process the tasks, while they slowly move to the right. So I have a "planned week" and an "active week", basically. During the week I can put tasks back to the first column to plan them for the next week, in case I did not finish them.

### TL;DR - Automatic Planner

This feature lets the user assign certain info to a project:

	priority
	project_type
	max_hours

And then it is needed to tell the system which "levels" (are defined on the hours view config page) stand for the planned vs the active week. Also it is needed to assign "time slots" to days in combination with time spans and an optional "project type" (a project can have; only the substring should occur, while the string to search for is this stirng in the time slot config of the feature). With these information + priority and "max_hours" the system will use theese infos and also the tasks priorities and their column + position in the column to sort the tasks and assign them to the weekdays.

I am still in the concept phase. Maybe I will even forget to remove this line here or update the above text. But I still wanted to mention it already, in case I will forget it.


Screenshots
-------------

**Board view**

![HoursView board view](../master/Screenshots/HoursView_board.png)

**Dashboard view**

![HoursView dashboard view](../master/Screenshots/HoursView_dashboard.png)


Compatibility
-------------

- Requires [Kanboard](https://github.com/fguillot/kanboard "Kanboard - Kanban Project Management Software") ≥`1.2.27`
- Requires [KanboardTabs](https://github.com/Tagirijus/KanboardTabs) to properly work for showing the tooltip times for last months/weeks/etc.

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
