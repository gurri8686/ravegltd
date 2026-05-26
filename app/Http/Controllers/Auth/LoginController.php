<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    protected function credentials(\Illuminate\Http\Request $request)
    {
		$username = $request->email;
		$getEmail = User::where('email', $username)->orWhere('username', $username)->first();
        
		if(empty($getEmail)){
			throw ValidationException::withMessages([
				'email' => [trans('auth.failed')],
			]);
		}

		// Deactivated account: if the credentials are otherwise correct, tell them
		// clearly instead of a generic failure.
		if(!$getEmail->is_active && \Illuminate\Support\Facades\Hash::check($request->password, $getEmail->password)){
			throw ValidationException::withMessages([
				'email' => ['Your account has been deactivated. Please contact the administrator.'],
			]);
		}

		//return $request->only($this->username(), 'password');
        //return ['email' => $request->{$this->username()}, 'password' => $request->password, 'is_active' => 1];
		//return ['email' => $request->{$this->username()}, 'password' => $request->password, 'is_active' => 1];
		return ['email' => $getEmail->email, 'password' => $request->password, 'is_active' => 1];
    }

    /**
     * Stamp the login time so the Users list can show "Last login".
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        try {
            \DB::connection($user->getConnectionName())
                ->table('users')
                ->where('id', $user->id)
                ->update(['last_login_at' => now()]);
            \Log::info('LAST_LOGIN_STAMPED', ['user_id' => $user->id, 'at' => now()->toDateTimeString()]);
        } catch (\Throwable $e) {
            \Log::warning('LAST_LOGIN_STAMP_FAILED', ['user_id' => $user->id ?? null, 'msg' => $e->getMessage()]);
        }
    }
}
