<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // Chỉ xóa card thuộc deck của chính user
        $card = Card::whereHas('deck', function ($q) {
            $q->where('user_id', auth()->id())
              ->where('status', '!=', 'deleted');
        })->find($id);

        if (!$card) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy thẻ'], 404);
        }

        $card->update(['status' => 'deleted']);

        Log::info('Card deleted', ['id' => $id]);

        return response()->json(['success' => true, 'message' => 'Đã xóa thẻ']);
    }
}
