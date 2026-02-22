# Changelog


## unreleased
### What's Changed

- Added priority of task in automatic plan list.


## v3.1
### What's Changed

- TimetaggerTranscriber won't overwrite tasks without them having an estimated time now.
- Added column board action for "clean" done tasks. This will delete done subtasks and in "non-time-mode" it will also reset the tasks score to 0.


## v3.0
### What's Changed

- Refactored the whole code base and (hopefully) improved time calculations.
- Added the **automatic planner** (huge thing to me).
- Added Timetagger integration for overwriting spent times (see README).
- Added CalDAV integration for the automatic planner (see README).
- Removed some features I did not use anyway (hovernig stats with the tabs, for example).


## v2.16

### What's Changed

- Added that in non-time mode the last subtask can not only overwrite "remaining time" with a positive number, but also overwrite "spent time" instead, if negative.
- Fixed some override calculation sub last subtask in non-time mode.
- Fixed some internal calculations which lead to buggy numbers and percentage bars.
- Changes: This little details for times wont' be shown, if non-time mode is enabled (it might not even makes sense, but mostly it's buggy anyway).


## v2.15

### What's Changed

- Last subtask's title in a task can override the remaining time, when it's a numeric value and the non-time-mode is enabled.
- Subtask can contain "N%" in title in non-time-mode, which can make them have a certain percentage of the whole estimated time.
- Fix: End of year weeks calculation was incorrect.
- Fix: Override the time with the last subtask being a numeric in non-time mode will now also calculate the spent time correctly.


## v2.14

### What's Changed

- Added non-time-mode feature.
- Hard coded removed some icons from task cards and list.


## v2.13

### What's Changed

- Icon for open tasks prepending in title added.


## v2.12

### What's Changed

- The blocking card icon is now more obvious.
- Also the blocking icon on the blocked card can be avoided with a config, if the blocking card is in specific columns.


## v2.11

### What's Changed

- Clicking on _"Start timer"_ for a subtask now will set the parent tasks started date, if there was none and change the subtask status as well.


## v2.10

### What's Changed

- Levels stuff can now not only be shown as a tooltip when hovering on the dashboard level thing, but also optionally as a separate dashboard page, linked through the sidebar.


## v2.9

### What's Changed

- Added feature with config to be able to sort the projects on the dashboard tooltip by remaining time.


## v2.8

### What's Changed

- Added block hours calculation on dashboard project tooltip. You can set up a _block\_hours_ in the config now. If it is >0, on the tooltip there will be shown a blocks calculation, how many blocks would be needed for the remaining time of the project.


## v2.7

### What's Changed

- Improved ignore subtask feature.
- Added tooltip for task detail times.
- Automatic action task title replacement for "[DUPICATE]" to "" got extended by the pattern, the user can set up in the config for the plugin [DuplicateMod](https://github.com/Tagirijus/DuplicateMod).
- Added automatic action for _TaskAutoAddWeek_ on task creation.


## v2.6

### What's Changed

- Added ignore subtask by title feature.


## v2.5

### What's Changed

- Added tooltips for time levels on dashboard.


## v2.4

### What's Changed

- Fixes that the automatic action would only replace "[DUPLICATE]" in the title with blank, thus the title got white space added.
- Automatic action gets new weeknumber without a leading zero now.
- More future weeks are not available in the "w"-replacer dropdown (which can be triggered in the title of a task).
- Added feature to show "blocked icon" in front of task title for tasks, which are blocked by other tasks.


## v2.3

### What's Changed

- Changed how _overtime_ is being displayed.


## v2.2

### What's Changed

- Added an alternative week-difference calculation, which will use the calendar weeks for the difference, instead of plain "7 days is a week" calculation. This better reflects the typical work week from monday to friday (or saturdax or even sunday), for example.


## v2.1

### What's Changed

- Added start date to card (on the bottom) and also moved due date to the bottom.
- Added config and feature to change style of remaining boxes according to the difference to today.
- Added option to show the week of the due date on the card.


## v2.0

### What's Changed

- I merged my [HoursView](https://github.com/Tagirijus/HoursView) plugin with this _WeekHelper_ plugin.


## v1.3

### What's Changed

- Start of the day is now monady
- Added option for a sticky time box on the bottom right


## v1.2

### What's Changed

- Fixed some trigger for week-replacer
- Added checkbox inserter

## v1.1

### What's Changed

- Added styling to the weekpattern on the cards and on the task list
- Changed default weekpattern


## v1.0

### What's Changed

- Initial release
- Translations starter template included


Read the full [**Changelog**](../master/changelog.md "See changes") or view the [**README**](../master/README.md "View README")
