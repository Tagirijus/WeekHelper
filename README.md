# WeekHelper

#### _Plugin for [Kanboard](https://github.com/fguillot/kanboard "Kanboard - Kanban Project Management Software")_

With this plugin you get some helpers for planning the week. This includes cool and intuitive time calculations and automatic planning of tasks according of pre-defined time slots and priorities logic.

## Levels

One core concept in this plugin is to have so called "levels". They act as some kind of filtering method for tasks depending on their swimlane and column. You can set up up to four levels and give them names. These levels can be shown in the header area (dashboard and project board). Also these levels will act as some core logic for the **Timetagger spent time overwriting** and the **automatic planning**, which needs and "active" and a "planned" week. More below.

## Time calculation

In Kanboard by default the times are stored as floats on a task or subtask. I found the output not that great, which is why I chose to change it. Besides that I even changed how the overall calculation of tasks and their subtasks could be handled (with a specific logic even). You can either have the times be used from the task (and subtasks), or a "non-time mode" which will use the score of a task and multiplies it with a pre-defined value (minutes) to calculate the estimated automatically. Also it is possible to have [Timetagger](https://timetagger.app/) overwrite spent times of tasks, depending on the config and the set tags per task.

### Timetagger overwriting

In the config you have to set up things to connect to a Timetagger installation. With that this plugin might be able to fetch tasks for the set up time span and use these tracked time events to overwrite the spent times of the tasks, defined via the "level".

Example:
Maybe you want this feature to only overwrite the spent times for the tasks you are actually working on in this active week, then you might consider a swimlane and or columns to represent this and define one of the four levels to be that "active week". This level (e.g. "level_1") has to be entered into the Timetagger config page then. Also a task needs the special additional value `timetagger_tags=...` so that the internal logic knows which task belong to which Timetagger tags. E.g. maybe you track time for a specific task with the tags `#client #project #coding`, then the tasks description text should contain in a line for itself `timetagger_tags=client,project,coding`. With that the TimetaggerTranscriber should be able to overwrite this tasks spent time automatically.

### Non-Time Mode

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

This one is a big new feature, which lead to version 3 of this plugin. The core idea is to have a tasks be plannned throughout the week automatically. Not just the sorting, but also in specific available time slots, leading to a task could be planned on multiple days or times even.

### Active vs planned week

I did adapt my personal workflow here, sorry, if this might be to unflexible to you, but maybe it is okay after all.

The idea is that there is a "planned week", representing tasks I would do the next week and an "active week" with tasks, I am doing in the active week. Each of this week has to use a defined level (see above). So e.g. I have "level_1" for my active week and "level_2" for my planned week. Furthermore: maybe "level_1" has the filter logic "all tasks on swimlane ACTIVE and in the columns todo, next, blocked and done" (basically this actually is my filtering). And then I have "level_2" with the logic "all tasks on swimlane ACTIVE and in the columns planned and blocked" (again my actual logic for the "planned week" level). I doubled the "blocked" column here, but you can make it different, if needed. It is doubled, because I might have remaining tiem for tasks in the column "blocked" to be calculated as time "already planned for the next week". But it doesn't matter right now. If you get the idea of all of this, you can make it different.

### Sorting logic

Before planning tasks, a proper sorting logic is needed so that the correct order, depending on certain sorting priorities, is given for the tasks. Here you can take a look into the configs page for the "automatic planner" and scroll down to "Task Keys". These are the available values to sort (and later on filtering as well!) on. Technically these are the default Kanboard tasks keys, but extended by my plugins own parsing of the description texts of project and tasks.

**So yeah: you can extend some values for a task by adding certain lines into the project- or task-description!** At the moment this is quite bad UX, I know. But it does work and I spent way too much time already building all this ... maybe one day I might improve it and make it a better UX.

### Distribution logic

So now are the tasks sorted. Internally the next step is to distribute all these sorted task among certain time slots. You can set up time slots in the config with an easy to read syntax like so, for one day, for example:

	6:15-8:45 category_name:!Musik category_name:!Sound
	11:00-13:00
	14:00-16:30 project_type:office

Let's say this was entered in the config area for the day "Monday". It would mean for the distributor:

- I can plan tasks on Monday between 6:15 and 8:45, if they do not have the category name "Musik" nor "Sound".
- I can plan tasks on Monday between 11:00 and 13:00, without further restrictions.
- I can plan tasks on Monday between 14:00 and 16:30, if the project_type, defined in the project description text, is like `project_type=office`.

Another restriction is: such time slots will have some time left in some situations. For a task to be planned on such time slot, on the configs page the "minimum slot length" config is available. This is an additional restriction. E.g. if a time slot has less than this set minutes available, it will be depleted and the distributor will continue with the next slot (or even day) for the plan.

Extra info:
A task can have something like `plan_from=tue 12:00` in the description, which will make the distributor plan this task from this time point onwards only. So if a task has this and comes first in the sorted task list alread, it would not be planned on Monday (let's say time slots would exist there), but on Tuesday 12:00 onwards. You might consider to extend the sorting logic with `plan_from desc` in the first line, so that all tasks having this key set at all, will come first to distribute. Let's say: I do it this way and so far it worked nice.

### Blocking by CalDAV calendar

You now hopefully got a grasp what this time slots is about and that these slots are some kind of "whitelist" for the plan. You might not want to update these slots every week manually, if you might, for example, have certain events planned in your week, which aren't represented as Kanboard tasks.

For this you can enter blocking time slots in the `blocking` text areas for the active and the planned week. This for itself is something which basically will create pseudo-tasks, which will be able to "deplete" time slots. Also they will be visible as psuedo tasks in the "automatic plan GUI" on Kanboard, or (optionally) in the output plaintext plan (yes, this is a thing as well and at this point I feel like somebody screams "but wait, there is more!").

Ok, back to topic: psuedo tasks, depleting time slots. Why this? Well: the idea is to have a set up week plan, which might get changed due to other events, but normally you still want the set up week plan to be solid and consistent.

Oh, right ... the most important part here:

**You can have these blocking tasks be auto populated by a CalDAV calendar!!**

You have to enter the login info and each calendar, which contains events, which should be able to create blocking slots, in a newline in the calendars urls field.

With that set up you can either click on **Update from CalDAV** underneath the blocking text fiels ...

... or might even consider setting up a cron job with the Kanboard cli command `weekhelper:update-blocking-tasks`. I am doing this once a day. I might consider adding the "Update from CalDAV" button somwhere in the sticky div as well.

### Outputting the automatic plan

Now with everything set up correctly, hopefully (and hopefully I did not forget something here) you will be able to get a proper automatic planned week (active and planned). How? Here are the options:

- visit `/weekhelper/automaticplan`
- enable `Show sticky week plan` on the "automatic plan config page": this will enable the output of the above mentioned URL inside a tiny responsive DIV on the bottom right of the screen
- visit `/weekhelper/automaticplan?type=text` to get a plaintext output of the plan, which e.g. could be used in a [Termux Terminal Widget](https://codeberg.org/gardockt/termux-terminal-widget). Certain query params are possible to modify this plaintext and get more infos, if needed, etc.:
	- `week_only`: '' for both weeks, or 'active' or 'planned'
	- `days`: 'mon,tue' for just these two days. or maybe '1,ovr' for only "today" and the "overflow day".
	- `hide_times`: '1' hides the day times.
	- `hide_length`: '1' hides the task lengths.
	- `hide_task_title`: '1' hides the task title.
	- `prepend_project_name`: '1' will prepend the project name before the task title.
	- `prepend_project_alias`: '1' will prepend the project alias (write it into the project description like `project_alias=ProJ1` or so) before the task title.
	- `show_day_planned`: '1' will output time stats for each week day next to the weekday name (like planned and free time).
	- `show_week_times`: '1' will prepend the whole plan for the week with stats about planned, spent, free and overflow time.
	- `add_blocking`: '1' will add the blocking pseudo tasks onto the plan.
- visit `/weekhelper/automaticplan?type=json` will get you a JSON string from the internal whole weekplan. you could use this in your own app or so. you might consider looking into the code at *AutomaticPlanner->getAutomaticPlanAsArray()* to see how the array is structured. I guess at the time writing this it's something like (PHP array here only, though ... it will be converted into JSON later, of course):

	[
		'active' => [
			'mon' => [array with sorted tasks],
			'tue' => ...,
			...,
			'sun' => ...,
			'overflow' => ...
		],
		'planned' => [
			'mon' => [array with sorted tasks],
			'tue' => ...
		],
	]

### Overflow

Since I did not mention it: the "overflow" day is some kind of fallback day on which tasks will be put, if there is not enough time left inside the week itself. If you have tasks in "overflow" it probably means that you planend too much tasks, you was not able to catch up with the planned work, or you might have assigned not enough time slots correctly or so.

In either way: to me this overflow is a handy indicator during the week, whether I am getting the plan done or not.

## Additional

### Automatic actions

Also there is a new automatic action, which will use the pattern you can set up in the settings, convert it to a regex internally and use it to generate a new such string but for the next week. Then you can set up the automatic action to be executed if e.g. you move a task into the backlog or so. Workflow-wise this could mean: put this task onto next week. And this automatic action helps you quickly automatically rename the tasks title to "update" the week number.

With the automatic plan feature I also added another automatic action, which will move tasks from noe column to another column on Monday. This is because I sue the first column "planned" as the column representing the planned week. On every Monday I want this plan to be put into action, thus all tasks have to be moved into another column.

### Week numbers in titles

In a task title you can enter "w" and trigger the week replacement feature, which will give you this week, the next week and the overnext week and replace the choice with the pattern set up in the config. By default it is "Y{YEAR_SHORT}-W{WEEK}". In the year 2023 and the week 39 of the year it would be translated to "Y23-W39", for example. These are the possible replacement values:

- YEAR: the four digit actual year
- YEAR_SHORT: the two digit actual year
- WEEK: the actual week number

## Finally

I hope that I did not forget to explain anything important. Bare with me ... I spent way too much time into this. :D

First I thought I should consider adding some kind of DEVELOPEMTN.md for some kind of info about how my code is structured. But now after writing this README already I do not want to. I mean ... maybe all the time was spent nicely and the code is self-explanatory. I tried hard to structure and comment it well, so maybe it helps. But maybe I stay the only dev on this anyway. You can ask me as well, of course. Not sure, if I might have the answer in the future, though. (;


Screenshots
-------------

**Info:** these screenshots are old ones. No time at the moment to update them. Sorry. But let me say: things have improved a lot!

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
