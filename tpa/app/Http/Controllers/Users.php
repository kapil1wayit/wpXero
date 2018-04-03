<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use DB;

class Users extends Controller
{
    /**
     * Fetch Data from APIs.
     *
     * @param  int  $id
     * @return Response
     */
    public function show()
    {        
        //return view('user.profile', ['user' => User::findOrFail($id)]);
        return view('user.profile');
    }
    
    /**
     * Create new user.
     *
     * @param  int  $id
     * @return Response
     */
    public function create(){
        /*
        $id = DB::table('user')->insertGetId(
            ['name' => 'Ravinder', 'email' => 'ravi@example.com', 'password' => md5('12345')]
        );
        echo $id;
        */
        
        $user = new User;

        $user->name = 'Gurdeep';
        $user->email = 'Singh';
        $user->password = md5('12345');
        $user->save();

        
        
        
        die('================');
    }
    
}