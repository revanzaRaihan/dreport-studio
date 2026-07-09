<?php

namespace App\Http\Controllers\Dataset;

use App\Http\Controllers\Controller;
use App\Models\DatasetEntry;
use App\Http\Requests\Dataset\StoreDatasetRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DatasetController extends Controller
{
    /**
     * Display a listing of the dataset entries.
     */
    public function index(): View
    {
        $dataset = DatasetEntry::latest()->get();
        return view('dataset.index', compact('dataset'));
    }

    /**
     * Store a newly created dataset entry in storage.
     */
    public function store(StoreDatasetRequest $request): RedirectResponse
    {
        DatasetEntry::create([
            'body' => $request->validated()['body']
        ]);

        return redirect()->route('dataset.index')
            ->with('success', 'Contoh laporan berhasil ditambahkan ke dataset.');
    }

    /**
     * Remove the specified dataset entry from storage.
     */
    public function destroy(DatasetEntry $dataset): RedirectResponse
    {
        $dataset->delete();

        return redirect()->route('dataset.index')
            ->with('success', 'Contoh laporan berhasil dihapus dari dataset.');
    }
}
