<?php

namespace App\Http\Controllers;

class VideoController extends Controller
{
    public function dashboard()
    {
        // TODO: implement dashboard view
        return view('dashboard');
    }

    public function create()
    {
        // TODO: implement create view
        return view('videos.create');
    }

    public function store()
    {
        // TODO: implement store logic
    }

    public function index()
    {
        // TODO: implement index view
        return view('videos.index');
    }

    public function show($video)
    {
        // TODO: implement show view
        return view('videos.show');
    }

    public function export($video)
    {
        // TODO: implement export logic
    }

    public function destroy($video)
    {
        // TODO: implement destroy logic
    }
}
