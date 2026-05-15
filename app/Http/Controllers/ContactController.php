<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        $contacts = Contact::where('user_id', Auth::id())
            ->orderBy('name')
            ->get();

        return view('contacts.index', compact('contacts'));
    }
}