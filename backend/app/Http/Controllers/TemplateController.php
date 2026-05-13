<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TemplateController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(DocumentTemplate::orderByDesc('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:docx|max:10240',
        ]);

        $path = $request->file('file')->store('templates', 'local');

        $template = DocumentTemplate::create([
            'name' => $request->name,
            'file_path' => $path,
            'is_active' => false,
        ]);

        // Birinchi shablon bo'lsa avtomatik faollashtirish
        if (DocumentTemplate::count() === 1) {
            $template->update(['is_active' => true]);
        }

        return response()->json($template, 201);
    }

    public function activate(DocumentTemplate $template): JsonResponse
    {
        DocumentTemplate::where('id', '!=', $template->id)->update(['is_active' => false]);
        $template->update(['is_active' => true]);

        return response()->json($template);
    }

    public function destroy(DocumentTemplate $template): JsonResponse
    {
        Storage::disk('local')->delete($template->file_path);
        $template->delete();

        return response()->json(['message' => 'Shablon o\'chirildi']);
    }
}
