<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\Event;
use App\Models\Expense;
use App\Models\TimeEntry;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeCases = LegalCase::where('user_id', Auth::id())
            ->where('status', 'active')
            ->with('documents')
            ->latest()
            ->take(5)
            ->get();

        // Upcoming deadlines will come from events table later


        // Inside index()
        $upcomingDeadlines = Event::where('user_id', Auth::id())
            ->upcoming()
            ->with('legalCase')
            ->orderBy('date')
            ->take(10)
            ->get()
            ->map(function ($event) {
                return [
                    'title'       => $event->title,
                    'case_ref'    => $event->legalCase?->reference,
                    'date'        => $event->date,
                    'is_overdue'  => $event->date->isPast(), // just in case
                ];
            });

        $totalBillableHours = TimeEntry::where('user_id', Auth::id())
            ->where('billable', true)
            ->whereNotNull('ended_at')
            ->sum('hours');

        $hourlyRate = 5000; // default rate (KSh), later you can make this configurable
        $totalTimeValue = $totalBillableHours * $hourlyRate;

        $totalExpenses = Expense::where('user_id', Auth::id())
            ->where('billable', true)
            ->sum('amount');

        $outstanding = $totalTimeValue + $totalExpenses; // simplistic: all unbilled
        // For "Paid This Month", you'd need an invoice/payment table (future).

        $overdueCasesCount = LegalCase::where('user_id', Auth::id())
            ->where('status', 'active')
            ->get()
            ->filter(fn($c) => !empty($c->overdueStages()))
            ->count();

        $missingCasesCount = LegalCase::where('user_id', Auth::id())
            ->where('status', 'active')
            ->get()
            ->filter(fn($c) => $c->nextMissingStage() !== null)
            ->count();

        // Keep the existing outstanding calculation
        // ...

        return view('dashboard', compact('activeCases', 'upcomingDeadlines', 'outstanding', 'overdueCasesCount', 'missingCasesCount'));
    }
}
