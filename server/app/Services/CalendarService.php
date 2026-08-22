<?php

namespace App\Services;

use App\Modules\Academic\Models\AcademicClass;
use App\Modules\Academic\Models\Exam;
use App\Modules\Academic\Models\Session;
use Illuminate\Support\Facades\Log;
use Spatie\GoogleCalendar\Event;
use Spatie\GoogleCalendar\Calendar;

/**
 * Calendar Service
 *
 * Provides calendar integration functionality.
 */
class CalendarService
{
    /**
     * Create a calendar event for a class session.
     */
    public function createSessionEvent(Session $session): ?string
    {
        try {
            $event = new Event();
            $event->name = $session->class->name . ' - ' . $session->topic;
            $event->description = $this->buildSessionDescription($session);
            $event->startDateTime = $session->start_time;
            $event->endDateTime = $session->end_time;
            $event->location = $session->class->room ?? 'Online';

            // Add attendees (students and teacher)
            $attendees = [];
            
            if ($session->class->teacher && $session->class->teacher->email) {
                $attendees[] = ['email' => $session->class->teacher->email];
            }

            foreach ($session->class->students as $student) {
                if ($student->email) {
                    $attendees[] = ['email' => $student->email];
                }
            }

            if (!empty($attendees)) {
                $event->attendees = $attendees;
            }

            $calendarEvent = $event->save();

            Log::info("Calendar event created for session", [
                'session_id' => $session->id,
                'event_id' => $calendarEvent->id,
            ]);

            return $calendarEvent->id;
        } catch (\Exception $e) {
            Log::error("Failed to create calendar event for session", [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create a calendar event for an exam.
     */
    public function createExamEvent(Exam $exam): ?string
    {
        try {
            $event = new Event();
            $event->name = $exam->title . ' - ' . $exam->exam_type;
            $event->description = $this->buildExamDescription($exam);
            $event->startDateTime = $exam->exam_date->setTimeFromTimeString($exam->start_time);
            $event->endDateTime = $exam->exam_date->setTimeFromTimeString($exam->end_time);
            $event->location = $exam->room ?? 'TBD';

            // Add attendees (students and teacher)
            $attendees = [];
            
            if ($exam->class->teacher && $exam->class->teacher->email) {
                $attendees[] = ['email' => $exam->class->teacher->email];
            }

            foreach ($exam->class->students as $student) {
                if ($student->email) {
                    $attendees[] = ['email' => $student->email];
                }
            }

            if (!empty($attendees)) {
                $event->attendees = $attendees;
            }

            $calendarEvent = $event->save();

            Log::info("Calendar event created for exam", [
                'exam_id' => $exam->id,
                'event_id' => $calendarEvent->id,
            ]);

            return $calendarEvent->id;
        } catch (\Exception $e) {
            Log::error("Failed to create calendar event for exam", [
                'exam_id' => $exam->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create calendar events for all sessions in a class.
     */
    public function createClassEvents(AcademicClass $class): array
    {
        $eventIds = [];

        foreach ($class->sessions as $session) {
            $eventId = $this->createSessionEvent($session);
            if ($eventId) {
                $eventIds[] = $eventId;
            }
        }

        return $eventIds;
    }

    /**
     * Update a calendar event.
     */
    public function updateEvent(string $eventId, array $data): bool
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                Log::warning("Calendar event not found", ['event_id' => $eventId]);
                return false;
            }

            if (isset($data['name'])) {
                $event->name = $data['name'];
            }

            if (isset($data['description'])) {
                $event->description = $data['description'];
            }

            if (isset($data['startDateTime'])) {
                $event->startDateTime = $data['startDateTime'];
            }

            if (isset($data['endDateTime'])) {
                $event->endDateTime = $data['endDateTime'];
            }

            if (isset($data['location'])) {
                $event->location = $data['location'];
            }

            $event->save();

            Log::info("Calendar event updated", ['event_id' => $eventId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update calendar event", [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Delete a calendar event.
     */
    public function deleteEvent(string $eventId): bool
    {
        try {
            $event = Event::find($eventId);

            if (!$event) {
                Log::warning("Calendar event not found", ['event_id' => $eventId]);
                return false;
            }

            $event->delete();

            Log::info("Calendar event deleted", ['event_id' => $eventId]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to delete calendar event", [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get calendar events for a user.
     */
    public function getUserEvents(string $userId, ?\DateTime $start = null, ?\DateTime $end = null): array
    {
        try {
            $user = \App\Modules\Iam\Models\User::findOrFail($userId);

            if (!$user->email) {
                return [];
            }

            $start = $start ?? now()->startOfMonth();
            $end = $end ?? now()->endOfMonth();

            $events = Event::get($start, $end);

            // Filter events where user is an attendee
            $userEvents = [];
            foreach ($events as $event) {
                if (isset($event->attendees)) {
                    foreach ($event->attendees as $attendee) {
                        if (isset($attendee['email']) && $attendee['email'] === $user->email) {
                            $userEvents[] = [
                                'id' => $event->id,
                                'name' => $event->name,
                                'description' => $event->description,
                                'start' => $event->startDateTime?->toIso8601String(),
                                'end' => $event->endDateTime?->toIso8601String(),
                                'location' => $event->location ?? null,
                            ];
                            break;
                        }
                    }
                }
            }

            return $userEvents;
        } catch (\Exception $e) {
            Log::error("Failed to get user calendar events", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Sync class schedule to calendar.
     */
    public function syncClassToCalendar(AcademicClass $class): array
    {
        // Delete existing events
        $existingEventIds = $class->sessions->pluck('calendar_event_id')->filter()->toArray();
        foreach ($existingEventIds as $eventId) {
            $this->deleteEvent($eventId);
        }

        // Create new events
        return $this->createClassEvents($class);
    }

    /**
     * Build session description.
     */
    protected function buildSessionDescription(Session $session): string
    {
        $description = "Class: {$session->class->name}\n";
        $description .= "Topic: {$session->topic}\n";
        $description .= "Teacher: {$session->class->teacher->full_name}\n";
        
        if ($session->class->room) {
            $description .= "Room: {$session->class->room}\n";
        }

        return $description;
    }

    /**
     * Build exam description.
     */
    protected function buildExamDescription(Exam $exam): string
    {
        $description = "Exam: {$exam->title}\n";
        $description .= "Type: {$exam->exam_type}\n";
        $description .= "Class: {$exam->class->name}\n";
        $description .= "Teacher: {$exam->class->teacher->full_name}\n";
        
        if ($exam->room) {
            $description .= "Room: {$exam->room}\n";
        }

        if ($exam->instructions) {
            $description .= "\nInstructions:\n{$exam->instructions}";
        }

        return $description;
    }

    /**
     * Export calendar to iCal format.
     */
    public function exportToICal(string $userId, ?\DateTime $start = null, ?\DateTime $end = null): string
    {
        $events = $this->getUserEvents($userId, $start, $end);

        $ical = "BEGIN:VCALENDAR\n";
        $ical .= "VERSION:2.0\n";
        $ical .= "PRODID:-//TOEFL House ERP//EN\n";
        $ical .= "CALSCALE:GREGORIAN\n";
        $ical .= "METHOD:PUBLISH\n";

        foreach ($events as $event) {
            $ical .= "BEGIN:VEVENT\n";
            $ical .= "UID:{$event['id']}@toeflhouse.af\n";
            $ical .= "DTSTART:" . date('Ymd\THis\Z', strtotime($event['start'])) . "\n";
            $ical .= "DTEND:" . date('Ymd\THis\Z', strtotime($event['end'])) . "\n";
            $ical .= "SUMMARY:{$event['name']}\n";
            
            if ($event['description']) {
                $ical .= "DESCRIPTION:" . str_replace("\n", "\\n", $event['description']) . "\n";
            }

            if ($event['location']) {
                $ical .= "LOCATION:{$event['location']}\n";
            }

            $ical .= "END:VEVENT\n";
        }

        $ical .= "END:VCALENDAR";

        return $ical;
    }
}
