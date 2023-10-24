<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthDropdown extends Component
{
    public $isOpen = false;
    public $tab = 'login';
    protected $listeners = ['outsideClick' => 'closeDropdown'];

    public function closeDropdown()
    {
        $this->isOpen = false;
    }

    public $loginForm = [
        'email' => '',
        'password' => ''
    ];

    public $registerForm = [
        'name' => '',
        'email' => '',
        'password' => '',
        'password_confirmation' => ''
    ];

    public function render()
    {
        return view('livewire.auth-dropdown');
    }

    public function switchTab($tabName)
    {
        $this->tab = $tabName;
        $this->reset(['loginForm', 'registerForm']);
    }
    public function toggleOpen($action)
    {
        if ($this->isOpen && $this->tab == $action) {
            $this->isOpen = false;
        } else {
            $this->isOpen = true;
            $this->tab = $action;
        }
    }
    public function hydrated()
    {
        $this->dispatch('add-dropdown-listener');
    }

    public function submitLoginForm()
    {
        // Validate form data
        $validatedData = $this->validate([
            'loginForm.email' => 'required|email',
            'loginForm.password' => 'required|min:6',
        ]);

        // Attempt to authenticate the user
        if (Auth::attempt(['email' => $this->loginForm['email'], 'password' => $this->loginForm['password']])) {
            // Successful login
            $this->reset(['isOpen', 'loginForm']);
            session()->flash('message', 'Logged in successfully.');
            return redirect()->to('/');
        } else {
            // Failed login
            $this->addError('loginForm.email', 'The provided credentials do not match our records.');
        }
    }

    public function submitRegisterForm()
    {
        // Validate form data
        $validatedData = $this->validate(
            [
                'registerForm.name' => 'required|string|max:255',
                'registerForm.email' => 'required|email|unique:users,email',
                'registerForm.password' => 'required|string|min:6|confirmed',
            ],
            [],
            [
                'registerForm.email' => 'email',
                'registerForm.name' => 'name',
                'registerForm.password' => 'password',
            ]
        );

        // Create the user
        $user = User::create([
            'name' => $this->registerForm['name'],
            'email' => $this->registerForm['email'],
            'password' => Hash::make($this->registerForm['password']),
        ]);

        // Authenticate the user
        Auth::login($user);

        // Reset state and redirect with a success message
        $this->reset(['isOpen', 'registerForm']);
        session()->flash('message', 'Registered and logged in successfully.');
        return redirect()->to('/');
    }
}