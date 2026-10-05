<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

/**
 * Hand-written login / registration (no starter kit), only used when AUTH_ENABLED=true.
 * Sessions are files in storage/framework/sessions — each server has its own copy.
 */
class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login', ['values' => []]);
    }

    public function login(Request $request): RedirectResponse|Response
    {
        $email = $this->normalizeEmail($request->input('email'));
        $credentials = ['email' => $email, 'password' => (string) $request->input('password')];

        // Auth::attempt finds the user by email and checks the bcrypt hash (column password_hash).
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate(); // new session id after login (prevents session fixation)

            return redirect()->intended('/');
        }

        // now() = a flash message for this response only (we render, not redirect).
        $request->session()->now('error', 'Invalid email or password.');

        return response()->view('auth.login', ['values' => ['email' => $email]], 422);
    }

    public function showRegister(): View
    {
        return view('auth.register', ['values' => []]);
    }

    public function register(Request $request): RedirectResponse|Response
    {
        $input = [
            'email' => $this->normalizeEmail($request->input('email')),
            'password' => $request->input('password'),
            'password_confirmation' => $request->input('password_confirmation'),
        ];

        $validator = Validator::make($input, [
            'email' => ['bail', 'required', 'email:filter', 'max:255', 'unique:users,email'],
            'password' => ['bail', 'required', 'string', 'min:8'],
            'password_confirmation' => ['bail', 'required', 'same:password'],
        ], [
            'email.required' => 'Enter a valid email address.',
            'email.email' => 'Enter a valid email address.',
            'email.max' => 'Enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password must be at least 8 characters.',
            'password.string' => 'Password must be at least 8 characters.',
            'password.min' => 'Password must be at least 8 characters.',
            'password_confirmation.required' => 'Passwords do not match.',
            'password_confirmation.same' => 'Passwords do not match.',
        ]);

        if ($validator->fails()) {
            return response()->view('auth.register', [
                'errors' => (new ViewErrorBag)->put('default', $validator->errors()),
                'values' => ['email' => $input['email']],
            ], 422);
        }

        $user = User::create([
            'email' => $input['email'],
            'password_hash' => Hash::make($input['password']), // bcrypt
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/')->with('success', 'Welcome! Your account is ready.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /** Emails are stored trimmed and in lower case, so "Anna@X.com " and "anna@x.com" are the same. */
    private function normalizeEmail(mixed $email): string
    {
        return Str::lower(trim((string) $email));
    }
}
