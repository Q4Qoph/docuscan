<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function index(): View
    {
        $cases = Auth::user()
            ->hasMany(LegalCase::class, 'user_id')
            ->with('documents')
            ->latest()
            ->get();

        // Fix: use proper relationship query
        $cases = LegalCase::where('user_id', Auth::id())
            ->with('documents')
            ->latest()
            ->get();

        return view('cases.index', compact('cases'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'debtor_name'    => 'required|string|max:255',
            'debtor_contact' => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:2000',
        ]);

        $case = LegalCase::create([
            'user_id'        => Auth::id(),
            'reference'      => LegalCase::generateReference(),
            'debtor_name'    => $request->debtor_name,
            'debtor_contact' => $request->debtor_contact,
            'notes'          => $request->notes,
        ]);

        return redirect()
            ->route('cases.show', $case)
            ->with('success', "Case {$case->reference} created.");
    }

    public function show(LegalCase $case): View
    {
        abort_if($case->user_id !== Auth::id(), 403);

        $case->load('documents.summaries');
        $completed = $case->completedStages();
        $next      = $case->nextMissingStage();

        return view('cases.show', compact('case', 'completed', 'next'));
    }
}