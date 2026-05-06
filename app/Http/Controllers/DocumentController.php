<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Models\Document;
use App\Services\PdfService;
use App\Jobs\SummarizeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function __construct(protected PdfService $pdfService)
    {
    }

    /**
     * Show the upload form and list of the user's documents.
     */
    public function index(): View
    {
        $documents = Auth::user()
            ->documents()
            ->with('summaries')
            ->latest()
            ->get();

        return view('documents.index', compact('documents'));
    }

    /**
     * Handle the PDF upload.
     */
    public function store(UploadDocumentRequest $request): RedirectResponse
    {
        // Store the file on disk
        $path = $request->file('document')->store('documents', 'local');

        // Create the database record
        $document = Document::create([
            'user_id'           => Auth::id(),
            'original_filename' => $request->file('document')->getClientOriginalName(),
            'storage_path'      => $path,
            'file_size_bytes'   => $request->file('document')->getSize(),
            'mime_type'         => $request->file('document')->getMimeType(),
        ]);

        // Create a pending summary record
        $summary = $document->summaries()->create([
            'criteria' => $request->input('criteria'),
            'status'   => 'pending',
        ]);

        // Dispatch the background job
        SummarizeDocument::dispatch($summary);

        return redirect()
            ->route('documents.show', $document)
            ->with('success', 'Your document has been uploaded and is being summarized.');
    }

    /**
     * Show a single document and its summary.
     */
    public function show(Document $document): View
    {
        // Authorization: make sure users can only see their own documents
        abort_if($document->user_id !== Auth::id(), 403);

        $document->load('summaries');

        return view('documents.show', compact('document'));
    }

    /**
     * Delete a document and its file from disk.
     */
    public function destroy(Document $document): RedirectResponse
    {
        abort_if($document->user_id !== Auth::id(), 403);

        // Delete file from storage
        \Storage::disk('local')->delete($document->storage_path);

        // Delete DB record (cascades to summaries automatically)
        $document->delete();

        return redirect()
            ->route('documents.index')
            ->with('success', 'Document deleted.');
    }
}