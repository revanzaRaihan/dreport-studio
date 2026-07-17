<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Http\Requests\Student\StoreStudentRequest;
use App\Http\Requests\Student\UpdateStudentRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of the students.
     */
    public function index(\Illuminate\Http\Request $request): View
    {
        $search   = $request->query('search');
        $students = Student::where('user_id', auth()->id())
            ->orderBy('name')
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('subject', 'ilike', "%{$search}%");
            }))
            ->paginate(1000)
            ->withQueryString();

        // Distinct subjects for the subject combobox (includes soft-deleted rows, scoped to user)
        $subjects = Student::withTrashed()
            ->where('user_id', auth()->id())
            ->whereNotNull('subject')
            ->where('subject', '!=', '')
            ->distinct()
            ->orderBy('subject')
            ->pluck('subject');

        return view('students.index', compact('students', 'search', 'subjects'));
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();
        
        Student::create($validated);

        return redirect()->route('students.index')
            ->with('success', 'Murid berhasil ditambahkan.');
    }

    /**
     * Update the specified student in storage.
     */
    public function update(UpdateStudentRequest $request, Student $student): RedirectResponse
    {
        if ($student->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $student->update($request->validated());

        return redirect()->route('students.index')
            ->with('success', 'Data murid berhasil diperbarui.');
    }

    /**
     * Remove the specified student from storage.
     */
    public function destroy(Student $student): RedirectResponse
    {
        if ($student->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $student->delete();

        return redirect()->route('students.index')
            ->with('success', 'Murid berhasil dihapus.');
    }
}
