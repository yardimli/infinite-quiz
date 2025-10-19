<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-base-content">
            {{ __('Delete Account') }}
        </h2>
        
        <p class="mt-1 text-sm text-base-content/80">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>
    
    <button class="btn btn-error" onclick="document.getElementById('confirm-user-deletion').showModal()">
        {{ __('Delete Account') }}
    </button>
    
    <dialog id="confirm-user-deletion" class="modal">
        <div class="modal-box">
            <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
                @csrf
                @method('delete')
                
                <h2 class="text-lg font-medium text-base-content">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>
                
                <p class="mt-1 text-sm text-base-content/80">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>
                
                <div class="mt-6 form-control w-full">
                    <label for="password_delete" class="label sr-only">
                        <span class="label-text">{{ __('Password') }}</span>
                    </label>
                    <input id="password_delete" name="password" type="password" class="input input-bordered w-full" placeholder="{{ __('Password') }}" />
                    @error('password', 'userDeletion')
                    <label class="label">
                        <span class="label-text-alt text-error">{{ $message }}</span>
                    </label>
                    @enderror
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="button" class="btn btn-ghost" onclick="document.getElementById('confirm-user-deletion').close()">
                        {{ __('Cancel') }}
                    </button>
                    
                    <button type="submit" class="btn btn-error ms-3">
                        {{ __('Delete Account') }}
                    </button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
    
    @if ($errors->userDeletion->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('confirm-user-deletion').showModal();
            });
        </script>
    @endif
</section>
