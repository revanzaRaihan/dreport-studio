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
        $recommendationDataset = \App\Models\RecommendationDataset::latest()->get();
        return view('dataset.index', compact('dataset', 'recommendationDataset'));
    }

    public function store(StoreDatasetRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $sectionType = $validated['section_type'] ?? 'overview';

        if ($sectionType === 'training_recommendation') {
            \App\Models\RecommendationDataset::create([
                'category' => $validated['category'] ?? 'coding_dasar',
                'body' => $validated['body'],
                'language' => $validated['language']
            ]);
        } else {
            DatasetEntry::create([
                'section_type' => $sectionType,
                'body' => $validated['body'],
                'language' => $validated['language']
            ]);
        }

        return redirect()->route('dataset.index')
            ->with('success', 'Contoh laporan berhasil ditambahkan ke dataset.');
    }

    /**
     * Remove the specified dataset entry from storage.
     */
    public function destroy(string $id): RedirectResponse
    {
        $deleted = false;
        
        $entry = DatasetEntry::find($id);
        if ($entry) {
            $entry->delete();
            $deleted = true;
        } else {
            $recEntry = \App\Models\RecommendationDataset::find($id);
            if ($recEntry) {
                $recEntry->delete();
                $deleted = true;
            }
        }

        if (!$deleted) {
            return redirect()->route('dataset.index')
                ->with('error', 'Contoh laporan tidak ditemukan.');
        }

        return redirect()->route('dataset.index')
            ->with('success', 'Contoh laporan berhasil dihapus dari dataset.');
    }
}
