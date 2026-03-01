<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Aplikasi;
use App\Bank;
use Auth;

class AplikasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $listaplikasi = Aplikasi::where('user_id', Auth::id())->get();
        return view('aplikasi.index', compact('listaplikasi'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $listbank = Bank::all();
        return view('aplikasi.create', compact('listbank'));
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
            'jenis' => 'required',
            'nama' => 'required',
            'war' => 'required',
            'ip' => 'required',
            'port' => 'required',
            'bank_id' => 'required|exists:bank,id'
        ]);

        $user_id = Auth::id();

        Aplikasi::create([
            'jenis' => $request->jenis,
            'nama' => $request->nama,
            'war' => $request->war,
            'ip' => $request->ip,
            'port' => $request->port,
            'bank_id' => $request->bank_id,
            'user_id' => $user_id

        ]);

        return redirect('/aplikasi');
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
        $aplikasi = Aplikasi::where('user_id', Auth::id())->findOrFail($id);
        $listbank = Bank::all();
        return view('aplikasi.edit', compact('aplikasi','listbank'));
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
            'jenis' => 'required',
            'nama' => 'required',
            'war' => 'required',
            'ip' => 'required',
            'port' => 'required',
            'bank_id' => 'required|exists:bank,id'
        ]);

        $aplikasi = Aplikasi::where('user_id', Auth::id())->findOrFail($id);

        $aplikasi_data = [
            'jenis' => $request->jenis,
            'nama' => $request->nama,
            'war' => $request->war,
            'ip' => $request->ip,
            'port' => $request->port,
            'bank_id' => $request->bank_id
        ];

      $aplikasi->update($aplikasi_data);
      return redirect('/aplikasi');

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
