<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Bank;
use App\Database;
use Auth;

class DatabaseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $listdb = Database::where('user_id', Auth::id())->get();
        return view('database.index', compact('listdb'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $listbank = Bank::all();
        return view('database.create', compact('listbank'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'sistemoperasi' => 'required',
            'nama' => 'required',
	    'deskripsi' => 'required',
            'jenis' => 'required',
            'ip' => 'required',
            'port' => 'required',
            'bank_id' => 'required|exists:bank,id'
        ]);

        $user_id = Auth::id();

        Database::create([
            'sistemoperasi' => $request->sistemoperasi,
            'nama' => $request->nama,
	    'deskripsi' => $request->deskripsi,
            'jenis' => $request->jenis,
            'ip' => $request->ip,
            'port' => $request->port,
            'bank_id' => $request->bank_id,
            'user_id' => $user_id

        ]);

        return redirect('/database');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $database = Database::where('user_id', Auth::id())->findOrFail($id);
        $listbank = Bank::all();
        return view('database.edit', compact('database','listbank'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'sistemoperasi' => 'required',
            'nama' => 'required',
	    'deskripsi' => 'required',
            'jenis' => 'required',
            'ip' => 'required',
            'port' => 'required',
            'bank_id' => 'required|exists:bank,id'
        ]);

        $database = Database::where('user_id', Auth::id())->findOrFail($id);

        $database_data = [
            'sistemoperasi' => $request->sistemoperasi,
            'nama' => $request->nama,
	    'deskripsi' => $request->deskripsi,
            'jenis' => $request->jenis,
            'ip' => $request->ip,
            'port' => $request->port,
            'bank_id' => $request->bank_id
        ];

      $database->update($database_data);
      return redirect('/database');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
