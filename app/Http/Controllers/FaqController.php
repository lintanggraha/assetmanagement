<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Faq;
use Auth;


class FaqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $listfaq = Faq::where('user_id', Auth::id())->paginate(6);
        //return view('')
        //dd($listcast);
        //$isicast = DB::table('cast')->get();
        return view('faq.index', compact('listfaq'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('faq.create');
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
            return redirect()->back()->withErrors(['isi' => 'Jawaban wajib diisi.'])->withInput();
        }

        $user_id = Auth::id();

        Faq::create([
            'judul' => $request->judul,
            'isi' => $isi,
            'user_id' => $user_id
        ]);

        return redirect('/faq');
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
        $faq = Faq::where('user_id', Auth::id())->findOrFail($id);
        return view('faq.edit', compact('faq'));
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
            return redirect()->back()->withErrors(['isi' => 'Jawaban wajib diisi.'])->withInput();
        }

        $faq = Faq::where('user_id', Auth::id())->findOrFail($id);

        $faq_data = [
            'judul' => $request->judul,
            'isi' => $isi
        ];

        $faq->update($faq_data);

        return redirect ('/faq');
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
