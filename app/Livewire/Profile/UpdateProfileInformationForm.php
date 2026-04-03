<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class UpdateProfileInformationForm extends Component
{
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public $photo = null;
    public ?string $currentProfilePhotoPath = null;
    public bool $removeCurrentPhoto = false;

    public function mount(): void
    {
        $user = Auth::user();

        $this->name = (string) $user->name;
        $this->email = (string) $user->email;
        $this->currentProfilePhotoPath = $user->profile_photo_path;
    }

    public function updateProfileInformation(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($this->removeCurrentPhoto && $user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
            $user->profile_photo_path = null;
        }

        if ($this->photo) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            $user->profile_photo_path = $this->photo->store('profile-photos', 'public');
        }

        $user->save();

        $this->currentProfilePhotoPath = $user->profile_photo_path;
        $this->photo = null;
        $this->removeCurrentPhoto = false;

        $this->dispatch('profile-updated', name: $user->name, photoUrl: $user->profile_photo_url);
    }

    public function removeProfilePhoto(): void
    {
        $this->photo = null;
        $this->currentProfilePhotoPath = null;
        $this->removeCurrentPhoto = true;
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && $user->hasVerifiedEmail()) {
            $path = session('url.intended', RouteServiceProvider::HOME);
            $this->redirect($path);

            return;
        }

        if ($user instanceof MustVerifyEmail) {
            $user->sendEmailVerificationNotification();
            Session::flash('status', 'verification-link-sent');
        }
    }

    public function getPhotoPreviewUrlProperty(): string
    {
        if ($this->photo && method_exists($this->photo, 'temporaryUrl')) {
            return $this->photo->temporaryUrl();
        }

        if ($this->currentProfilePhotoPath && !$this->removeCurrentPhoto) {
            return '/storage/' . ltrim($this->currentProfilePhotoPath, '/');
        }

        return asset('images/admin-icon.png');
    }

    public function render()
    {
        return view('livewire.profile.update-profile-information-form');
    }
}
