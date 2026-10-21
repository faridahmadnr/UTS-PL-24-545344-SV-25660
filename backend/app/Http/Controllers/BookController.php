<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * Display a listing of books (Status 200).
     */
    public function index()
    {
        $books = Book::latest()->get();

        return response()->json([
            'message' => 'Daftar buku berhasil diambil',
            'data'    => $books,
        ], 200);
    }

    /**
     * Store a newly created book (Status 201 Created, 422 Unprocessable Content on validation failure).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'year'        => 'required|integer|min:1900|max:2100',
            'description' => 'nullable|string',
        ]);

        $book = Book::create($validated);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan',
            'data'    => $book,
        ], 201);
    }

    /**
     * Display the specified book (Status 200 OK, or 404 Not Found).
     */
    public function show(string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'message' => 'Detail buku berhasil diambil',
            'data'    => $book,
        ], 200);
    }

    /**
     * Update the specified book (Status 200 OK, 404 Not Found, 422 Validation).
     */
    public function update(Request $request, string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan',
            ], 404);
        }

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'year'        => 'required|integer|min:1900|max:2100',
            'description' => 'nullable|string',
        ]);

        $book->update($validated);

        return response()->json([
            'message' => 'Buku berhasil diperbarui',
            'data'    => $book,
        ], 200);
    }

    /**
     * Remove the specified book (Status 200 OK, or 404 Not Found).
     */
    public function destroy(string $id)
    {
        $book = Book::find($id);

        if (!$book) {
            return response()->json([
                'message' => 'Buku tidak ditemukan',
            ], 404);
        }

        $book->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus',
        ], 200);
    }
}
