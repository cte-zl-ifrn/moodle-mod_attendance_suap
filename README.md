# Moodle Attendance SUAP Module

## Overview

The Attendance SUAP module is a Moodle activity that tracks student attendance based on activity completion, integrated with SUAP (Sistema Unificado de Administração Pública). It dynamically calculates attendance via Moodle events, supports automatic notifications with trend analysis, and provides exportable reports.

## Features

- **Single instance per course**: One attendance instance tracks all course attendance
- **Date-based organization**: Days with configurable start and end dates
- **Lesson planning**: Optional lesson plans associated with days
- **Module tracking**: Links course modules/activities to lessons
- **Automatic progress calculation**: Based on activity completion
- **Trend analysis**: Notifications when students fall behind expected progress
- **Matrix reports**: Exportable attendance matrix (CSV, HTML)
- **Multi-language**: Portuguese (BR) and English support
- **Mobile compatible**: Responsive design

## Requirements

- Moodle 4.4 or higher
- Activity completion tracking enabled in the course
- PHP 7.4 or higher

## Installation

1. Copy the plugin directory to `{moodle-root}/mod/attendance_suap`
2. Visit Site administration → Notifications to install the plugin
3. Configure default settings at Site administration → Plugins → Activity modules → Attendance SUAP

## Usage

### For Teachers

1. **Add instance**: Add an Attendance SUAP activity to your course
2. **Configure days**: Add date ranges (days) for attendance tracking
3. **Add lessons**: Create lessons within each day
4. **Link modules**: Associate course activities/resources with lessons
5. **View matrix**: Monitor student progress via the attendance matrix
6. **Export reports**: Download CSV or HTML reports

### For Students

1. **View progress**: See your completion progress for each day and lesson
2. **Track modules**: Check which activities are completed
3. **Receive notifications**: Get alerts when falling behind expected progress

## Configuration

### Site Settings

- **Notification roles**: Roles that receive notifications (default: editingteacher,manager)

### Instance Settings

- **Trend threshold**: Notification threshold for trend analysis (default: 0.90)
  - Triggers notification if current progress < threshold × expected progress

## Capabilities

- `mod/attendance_suap:addinstance` - Add a new instance
- `mod/attendance_suap:manage` - Manage days, lessons, and modules
- `mod/attendance_suap:viewmatrix` - View attendance matrix
- `mod/attendance_suap:viewprogress` - View personal progress

## Scheduled Tasks

- **Notification task**: Runs daily at 2:00 AM to send attendance notifications
  - Analyzes student progress against expected progress
  - Sends notifications to students falling below threshold

## Database Structure

- `attendance_suap` - Main attendance instance
- `attendance_suap_days` - Date ranges
- `attendance_suap_lessons` - Lessons with optional plans
- `attendance_suap_lesson_modules` - Module associations
- `attendance_suap_user_progress` - User completion tracking

## Events

The module observes the following Moodle events:

- `course_module_completion_updated` - Updates user progress when activities are completed
- `course_module_deleted` - Cleans up module associations when activities are deleted

## Backup and Restore

The module supports Moodle's backup and restore functionality, preserving:
- Days and lessons
- Module associations
- User progress data

## License

GNU GPL v3 or later

## Copyright

Copyright 2024 IFRN

## Support

For issues and feature requests, please use the GitHub issue tracker.
