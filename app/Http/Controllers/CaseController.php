<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CaseController extends Controller
{
    public function index(): View
    {
        $cases = LegalCase::where('user_id', Auth::id())
            ->with('documents')
            ->latest()
            ->get();

        $contacts = Contact::where('user_id', Auth::id())
            ->where('type', 'debtor')
            ->orderBy('name')
            ->get();

        return view('cases.index', compact('cases', 'contacts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'contact_id'     => 'nullable|exists:contacts,id',
            'debtor_name'    => 'required_without:contact_id|string|max:255',
            'debtor_contact' => 'nullable|string|max:255',
            'notes'          => 'nullable|string|max:2000',
        ]);

        // Determine debtor name
        if ($request->contact_id) {
            $contact = Contact::find($request->contact_id);
            $debtorName = $contact->name;
            $debtorContact = $request->debtor_contact ?: $contact->phone;
        } else {
            $debtorName = $request->debtor_name;
            $debtorContact = $request->debtor_contact;
        }

        $case = LegalCase::create([
            'user_id'        => Auth::id(),
            'reference'      => LegalCase::generateReference(),
            'debtor_name'    => $debtorName,
            'debtor_contact' => $debtorContact,
            'notes'          => $request->notes,
        ]);

        // Attach contact to case
        if ($request->contact_id) {
            $case->contacts()->attach($contact, ['role' => 'debtor']);
        } elseif ($request->debtor_name) {
            // Create a new contact from the manual entry
            $newContact = Contact::create([
                'user_id' => Auth::id(),
                'name'    => $request->debtor_name,
                'phone'   => $request->debtor_contact,
                'type'    => 'debtor',
            ]);
            $case->contacts()->attach($newContact, ['role' => 'debtor']);
        }

        return redirect()
            ->route('cases.show', $case)
            ->with('success', "Case {$case->reference} created.");
    }

    public function show(LegalCase $case): View
    {
        $this->authorize('view', $case);

        $case->load('documents.summaries', 'contacts');

        return view('cases.show', compact('case'));
    }
}