<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\View;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function storeAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image'
        ]);
        $user = auth()->user();

        $filename = $user->id . '-' . uniqid() . '.jpg';

        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatars/' . $filename, $imgData);
        $oldAvatar = $user->avatar;
        $user->avatar = $filename;
        $user->save();

        if ($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar));
        }
        return back()->with('success', 'Congrats on new Avatar');
    }

    public function showAvatarForm()
    {
        return view('avatar-form');
    }

    private function getSharedData($user)
    {
        $currentlyFollowing = 0;

        if (auth()->check()) {
            $currentlyFollowing = Follow::where([['user_id', '=', auth()->user()->id], ['followeduser', '=', $user->id]])->count();

            View::share('sharedData', [
                'currentlyFollowing' => $currentlyFollowing, 'avatar' => $user->avatar,
                'username' => $user->username, 
                'posts' => $user->posts()->latest()->get(), 
                'postCount' => $user->posts()->count(),
                'followerCount' => $user->followers()->count(), 
                'followingCount' => $user->followingTheseUsers()->count()
            ]);
        }
    }
    // profile
    public function profile(User $user)
    {
        $this->getSharedData($user);
        return view('profile-posts', ['posts' => $user->posts()->latest()->get()]);
    }

    // profileFollowers
    public function profileFollowers(User $user)
    {
        $this->getSharedData($user);
        return view('profile-followers', ['followers' => $user->followers()->latest()->get()]);
    }

    // profileFollowing
    public function profileFollowing(User $user)
    {
        $this->getSharedData($user);
        return view('profile-following', ['following' => $user->followingTheseUsers()->latest()->get()]);
    }

    public function logout()
    {
        auth()->logout();
        return redirect('/')->with('success', 'You are now logged out :C');
    }

    public function showCorrectHomepage()
    {
        if (auth()->check()) {
            return view('homepage-feed', ['posts'=> auth()->user()->feedPosts()->latest()->paginate(4)]);
        } else {
            return view('homepage');
        }
    }

    public function login(Request $request)
    {
        $incommingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required'
        ]);
        if (auth()->attempt(['username' => $incommingFields['loginusername'], 'password' => $incommingFields['loginpassword']])) {
            $request->session()->regenerate();
            return redirect('/')->with('success', 'You have successfully logged in :>');
        } else {
            return redirect('/')->with('failure', 'Invalid login');
        }
    }

    public function register(Request $request)
    {
        $incommingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:8', 'confirmed']
        ]);
        $incommingFields['password'] = bcrypt($incommingFields['password']);

        $user = User::create($incommingFields);
        auth()->login($user);
        return redirect('/')->with('success', 'Thank you for creating account !!!');
    }
}
