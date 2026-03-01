<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Dokumentasi;
use Auth;

class DokumentasiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $listdokumen = Dokumentasi::where('user_id', Auth::id())->paginate(6);
        //return view('')
        //dd($listcast);
        //$isicast = DB::table('cast')->get();
        return view('dokumentasi.index', compact('listdokumen'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('dokumentasi.create');
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
            'judul' => 'required',
            'isi' => 'required'
        ]);

        $isi = trim(strip_tags($request->isi));
        if ($isi === '') {
            return redirect()->back()->withErrors(['isi' => 'Isi dokumentasi wajib diisi.'])->withInput();
        }

        $user_id = Auth::id();

        Dokumentasi::create([
            'judul' => $request->judul,
            'isi' => $isi,
            'user_id' => $user_id
        ]);

        return redirect('/dokumentasi');
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
        $dokumen = Dokumentasi::where('user_id', Auth::id())->findOrFail($id);
        return view('dokumentasi.edit', compact('dokumen'));
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
            'judul'=>'required',
            'isi' => 'required'
        ]);

        $isi = trim(strip_tags($request->isi));
        if ($isi === '') {
            return redirect()->back()->withErrors(['isi' => 'Isi dokumentasi wajib diisi.'])->withInput();
        }

        $dokumen = Dokumentasi::where('user_id', Auth::id())->findOrFail($id);

        $dokumen_data = [
            'judul' => $request->judul,
            'isi' => $isi
        ];

        $dokumen->update($dokumen_data);

        return redirect ('/dokumentasi');
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
