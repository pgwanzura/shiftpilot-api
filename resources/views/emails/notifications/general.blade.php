<x-mail::message>
    # {{ $subject }}

    @isset($user_name)
    Hello {{ $user_name }},
    @endisset

    @switch($template_key)
    @case('shift.new_available')
    A new shift is available at {{ $location }} starting at {{ $start_time }}.
    <x-mail::button :url="url('/shifts')">
        View Shift Details
    </x-mail::button>
    @break

    @case('timesheet.submitted')
    Timesheet has been submitted for {{ $employee_name }} for shift on {{ $shift_date }}.
    Total hours worked: {{ $hours_worked }}
    <x-mail::button :url="url('/timesheets')">
        Review Timesheet
    </x-mail::button>
    @break

    @case('agency_response.submitted')
    New agency response received from {{ $agency_name }} for shift request.
    Proposed rate: £{{ $proposed_rate }}
    <x-mail::button :url="url('/agency-responses')">
        View Response
    </x-mail::button>
    @break

    @default
    You have a new notification from Staffing Platform.
    <x-mail::button :url="url('/notifications')">
        View Notification
    </x-mail::button>
    @endswitch

    Thanks,<br>
    The {{ config('app.name') }} Team
</x-mail::message>