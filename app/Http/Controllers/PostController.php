<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function searchss(Request $request){
        $posts = Post::where('title', 'LIKE', '%' . $request->search . '%')->get();
        if(empty ($request->search)){
            $posts=[];
        }
        return view('search', ['posts' => $posts]);
    }

    public function actuallyUpdate(Post $post, Request $request){
        $incomingFields = $request -> validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        $post->update($incomingFields);

        return back()->with('success', 'Post successfully update');
    }

    public function showEditForm(Post $post){
        return view('edit-post', ['post' => $post]);
    }

    public function delete(Post $post){
        $post->delete();

        return redirect('/profile/' . auth()->user()->username)-> with('success', 'Post successfully delete');
    }

    public function viewSinglePost(Post $post){
        return view('single-post', ['post' => $post]);
    }

    public function storeNewPost(Request $request){
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);
        $incomingFields['user_id'] = auth()->id();

        $newPost = Post::create($incomingFields);

        return redirect("/post/{$newPost->id}")->with('success', 'New Post Successfully created');
    }

    public function showCreateForm(){
        return view('create-post');
    }
}
