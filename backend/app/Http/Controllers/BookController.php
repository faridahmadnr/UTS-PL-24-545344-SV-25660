<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'author'      => 'required|string|max:255',
            'year'        => 'required|integer|min:1900|max:2100',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            // Log Terstruktur: Validasi Gagal (Warning)
            Log::warning('Validasi penambahan buku gagal: Atribut wajib tidak terpenuhi', [
                'actor'         => $request->user()?->id ?? 'guest',
                'client_ip'     => $request->ip(),
                'action'        => 'create_book_validation_failed',
                'status'        => 'rejected',
                'failed_fields' => array_keys($validator->errors()->toArray()),
                'reason'        => $validator->errors()->all(),
            ]);

            return response()->json([
                'message' => 'Validasi data buku gagal',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $book = Book::create($validator->validated());

        // Log Terstruktur: Berhasil Menyimpan Data (Info)
        Log::info('Berhasil menambahkan data buku baru ke katalog', [
            'actor'     => $request->user()?->id ?? 'authenticated_user',
            'client_ip' => $request->ip(),
            'action'    => 'create_book',
            'status'    => 'success',
            'book_id'   => $book->id,
            'title'     => $book->title,
            'author'    => $book->author,
            'year'      => $book->year,
        ]);

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
