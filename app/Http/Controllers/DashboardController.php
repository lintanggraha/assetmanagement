<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Aplikasi;
use App\Database;
use App\Dokumentasi;
use App\Bank;
use App\Faq;
use App\User;
use Auth;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $userId = Auth::id();
        $listaplikasi = Aplikasi::where('user_id', $userId)->get();
        //$listpost = Post::orderBy('id', 'DESC')->get();
        $totaldb = Database::where('user_id', $userId)->count();
        $totaluser = User::all()->count();
        $totalaplikasi = Aplikasi::where('user_id', $userId)->count();
        $totalbank = Bank::all()->count();
        return view('dashboard', compact('listaplikasi','totaluser','totalaplikasi','totalbank','totaldb'));
    }

    public function faq(){
        $listfaq = Faq::where('user_id', Auth::id())->paginate(6);
        //return view('')
        //dd($listcast);
        //$isicast = DB::table('cast')->get();
        return view('dashboard-faq', compact('listfaq'));
    }

    public function dokumen(){
        $listdok = Dokumentasi::where('user_id', Auth::id())->paginate(6);
        //return view('')
        //dd($listcast);
        //$isicast = DB::table('cast')->get();
        return view('dashboard-dokumen', compact('listdok'));
    }

    public function db(){
        $listdb = Database::where('user_id', Auth::id())->get();
        //$listpost = Post::orderBy('id', 'DESC')->get();
        return view('dashboard-db', compact('listdb'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        //
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
        //
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
