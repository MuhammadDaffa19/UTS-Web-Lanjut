<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Intervention\Image\ImageManager;
use Intervention\Image\Driver\Driver;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use App\Models\JobType;

class AccountController extends Controller
{
    public function registration() {
        return view('front.account.registration');
    }

    public function processRegistration(Request $request) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:5|same:confirm_password',
            'confirm_password' => 'required',
        ]);

        if ($validator->passes()) {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            session()->flash('success', 'You have registered successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function login() {
        return view('front.account.login');
    }

    public function authenticate(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->passes()) {
            if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
                return redirect()->route('account.profile');
            } else {
                return redirect()->route('account.login')->with('error', 'Either Email/Password is incorrect');
            }
        } else {
            return redirect()->route('account.login')
                ->withErrors($validator)
                ->withInput($request->only('email'));
        }
    }

    public function profile() {

        $user = Auth::user();
        return view('front.account.profile', compact('user'));
    }

    public function updateProfile(Request $request) {

        $id = Auth::id();

        $validator = Validator::make($request->all(),[
            'name' => 'required|min:5|max:20',
            'email' => 'required|email|unique:users,email,'.$id,
        ]);

        if ($validator->passes()) {
            $user = User::find($id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->mobile = $request->mobile;
            $user->designation = $request->designation;
            $user->save();

            session()->flash('success','Profile updated successfully.');

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

        public function logout() {
            Auth::logout();
            return redirect()->route('account.login');
        }

        public function updateProfilePic(Request $request) {
        $id = Auth::user()->id;

        $validator = Validator::make($request->all(),[
            'image' => 'required|image'
        ]);

        if ($validator->passes()) {
            $image = $request->image;
            $ext = $image->getClientOriginalExtension();
            $imageName = $id.'-'.time().'.'.$ext;
            $image->move(public_path('/profile_pic/'), $imageName); // <-- penyimpanan gambar

            // Creative a small thumbnail
            $sourcePath = public_path('/profile_pic/'.$imageName);
            $manager = new ImageManager(GdDriver::class);
            $image = $manager->read($sourcePath);

            $image->cover(150, 150);
            $image->toPng()->save(public_path('/profile_pic/thumb/'.Auth::user()->image));
            $image->toPng()->save(public_path('/profile_pic/'.Auth::user()->image));

            // Delete Old Profile
            File::delete(public_path('/profile_pic/thumb/'.$imageName));

            User::where('id', $id)->update(['image' => $imageName]);

            return response()->json([
                'status' => true,
                'errors' => []
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function createJob() {

        $categories = Category::orderBy('name', 'ASC')->where('status',1)->get();

        $jobTypes= JobType::orderBy('name', 'ASC')->where('status',1)->get();

        return view('front.account.job.create',[
            'categories' => $categories,
            'jobTypes' => $jobTypes,
        ]);
    }

    public function saveJob(Request $request){

        $rules = [
            'title' => 'required',
            'category' => 'required',
            'jobType' => 'required',
            'location' => 'required',
            'description' => 'required',
            'company_name' => 'required',
        ];

        $validator = Validator::make($request->all(),[

        ]);
    }
}
